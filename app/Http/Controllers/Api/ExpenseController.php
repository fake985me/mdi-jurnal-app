<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{Expense, ExpenseCategory};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Storage};
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ExpenseController extends Controller
{
    private const RELATIONS = ['items.category', 'payment.bankAccount', 'user'];

    public function index(Request $request)
    {
        $data = $request->validate([
            'search' => 'nullable|string|max:255', 'status' => 'nullable|in:unpaid,paid,cancelled',
            'category_id' => 'nullable|exists:expense_categories,id', 'start_date' => 'nullable|date',
            'end_date' => ['nullable', 'date', ...($request->filled('start_date') ? ['after_or_equal:start_date'] : [])],
            'per_page' => 'nullable|integer|min:1|max:100', 'page' => 'nullable|integer|min:1',
        ]);
        $query = Expense::query();
        if ($request->filled('search')) $query->where(function ($q) use ($data) {
            foreach (['expense_number', 'vendor_name', 'reference', 'notes'] as $field) $q->orWhere($field, 'like', '%'.$data['search'].'%');
        });
        if ($request->filled('status')) $query->where('status', $data['status']);
        if ($request->filled('category_id')) $query->whereHas('items', fn ($q) => $q->where('expense_category_id', $data['category_id']));
        if ($request->filled('start_date')) $query->whereDate('expense_date', '>=', $data['start_date']);
        if ($request->filled('end_date')) $query->whereDate('expense_date', '<=', $data['end_date']);
        $summary = [];
        foreach (['unpaid', 'paid', 'cancelled'] as $status) $summary[$status] = (float) (clone $query)->where('status', $status)->sum('total_amount');
        $summary['total'] = $summary['paid'] + $summary['unpaid'];
        $summary['count'] = (clone $query)->count();
        $summary['overdue'] = (float) (clone $query)->where('status', 'unpaid')->whereDate('due_date', '<', today())->sum('total_amount');
        $rows = $query->with(self::RELATIONS)->orderByDesc('expense_date')->orderByDesc('id')->paginate($data['per_page'] ?? 15);
        return response()->json(array_merge($rows->toArray(), ['summary' => $summary]));
    }

    public function categories() { return response()->json(ExpenseCategory::orderBy('name')->get()); }

    public function storeCategory(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:150|unique:expense_categories,name']);
        return response()->json(ExpenseCategory::create($data), 201);
    }

    public function show(Expense $expense) { return response()->json($expense->load(self::RELATIONS)); }
    public function store(Request $request) { return $this->save($request); }
    public function update(Request $request, Expense $expense) { return $this->save($request, $expense); }

    private function paymentRules(): array
    {
        return ['bank_account_id' => ['required', Rule::exists('bank_accounts', 'id')->where('is_active', true)->where('currency', 'IDR')],
            'payment_date' => 'required|date', 'method' => 'required|in:cash,transfer,debit,credit,other'];
    }

    private function save(Request $request, ?Expense $expense = null)
    {
        $data = $request->validate([
            'expense_date' => 'required|date', 'due_date' => 'nullable|date|after_or_equal:expense_date',
            'vendor_name' => 'nullable|string|max:255', 'reference' => 'nullable|string|max:255',
            'status' => 'required|in:unpaid,paid', 'notes' => 'nullable|string|max:10000',
            'items' => 'required|array|min:1|max:100', 'items.*.expense_category_id' => 'required|exists:expense_categories,id',
            'items.*' => 'array:expense_category_id,description,amount,tax_rate',
            'items.*.description' => 'nullable|string|max:1000', 'items.*.amount' => 'required|numeric|min:0.01|max:9999999999.99|decimal:0,2',
            'items.*.tax_rate' => 'required|numeric|min:0|max:100|decimal:0,2',
            'attachments' => 'nullable|array|max:5', 'attachments.*' => 'required|file|mimes:jpg,jpeg,png,webp,pdf|max:10240',
            ...($request->status === 'paid' ? $this->paymentRules() : []),
        ]);
        $paths = [];
        try {
            $record = DB::transaction(function () use ($data, $request, $expense, &$paths) {
                if ($expense) {
                    $expense = Expense::lockForUpdate()->findOrFail($expense->id);
                    abort_unless($expense->status === 'unpaid', 422, 'Only unpaid expenses can be edited.');
                }
                $items = collect($data['items'])->map(function ($item) {
                    $amount = round((float) $item['amount'], 2);
                    $tax = round($amount * (float) $item['tax_rate'] / 100, 2);
                    return array_merge($item, ['amount' => $amount, 'tax_amount' => $tax, 'total' => round($amount + $tax, 2)]);
                });
                $fields = collect($data)->only(['expense_date', 'due_date', 'vendor_name', 'reference', 'notes', 'status'])->all();
                $fields += ['subtotal' => $items->sum('amount'), 'tax_amount' => $items->sum('tax_amount'), 'total_amount' => $items->sum('total')];
                if (!$expense) $expense = Expense::create($fields + [
                    'expense_number' => 'EXP-'.now()->format('Ymd').'-'.strtoupper(Str::random(8)), 'user_id' => auth()->id(),
                ]);
                else $expense->update($fields);
                $files = $expense->attachments ?? [];
                abort_if(count($files) + count($request->file('attachments', [])) > 5, 422, 'Maximum 5 attachments per expense.');
                foreach ($request->file('attachments', []) as $file) {
                    $path = $file->store('expense-receipts/'.$expense->id, 'local');
                    throw_unless($path, new \RuntimeException('Unable to save receipt.'));
                    $paths[] = $path;
                    $files[] = ['id' => (string) Str::uuid(), 'path' => $path, 'name' => $file->getClientOriginalName(), 'size' => $file->getSize()];
                }
                $expense->update(['attachments' => $files]);
                $expense->items()->delete();
                $expense->items()->createMany($items->all());
                $expense->payment()->updateOrCreate([], [
                    'amount' => $expense->total_amount, 'tax_amount' => $expense->tax_amount, 'payment_type' => 'full',
                    'status' => $data['status'], 'payment_date' => $data['payment_date'] ?? $data['expense_date'],
                    'bank_account_id' => $data['bank_account_id'] ?? null, 'method' => $data['method'] ?? null,
                    'reference_number' => $expense->expense_number, 'notes' => $expense->notes, 'user_id' => auth()->id(),
                ]);
                return $expense->fresh(self::RELATIONS);
            });
            return response()->json($record, $expense ? 200 : 201);
        } catch (\Throwable $error) {
            Storage::disk('local')->delete($paths);
            throw $error;
        }
    }

    public function pay(Request $request, Expense $expense)
    {
        $data = $request->validate($this->paymentRules());
        return DB::transaction(function () use ($data, $expense) {
            $expense = Expense::lockForUpdate()->findOrFail($expense->id);
            abort_unless($expense->status === 'unpaid', 422, 'Expense is already paid or cancelled.');
            $expense->payment()->updateOrCreate([], $data + ['status' => 'paid', 'amount' => $expense->total_amount,
                'tax_amount' => $expense->tax_amount, 'payment_type' => 'full', 'reference_number' => $expense->expense_number, 'user_id' => auth()->id()]);
            $expense->update(['status' => 'paid']);
            return response()->json($expense->fresh(self::RELATIONS));
        });
    }

    public function cancel(Expense $expense)
    {
        return DB::transaction(function () use ($expense) {
            $expense = Expense::lockForUpdate()->findOrFail($expense->id);
            $expense->payment()->update(['status' => 'cancelled']);
            $expense->update(['status' => 'cancelled']);
            return response()->json($expense->fresh(self::RELATIONS));
        });
    }

    public function destroy(Expense $expense)
    {
        $paths = DB::transaction(function () use ($expense) {
            $expense = Expense::lockForUpdate()->findOrFail($expense->id);
            abort_unless($expense->status === 'unpaid', 422, 'Only unpaid expenses can be deleted.');
            $paths = collect($expense->attachments ?? [])->pluck('path')->all();
            $expense->payment()->delete();
            $expense->delete();
            return $paths;
        });
        Storage::disk('local')->delete($paths);
        return response()->json(['message' => 'Expense deleted.']);
    }

    public function attachment(Expense $expense, string $attachment)
    {
        $file = collect($expense->attachments ?? [])->firstWhere('id', $attachment);
        abort_unless($file && Storage::disk('local')->exists($file['path']), 404);
        return Storage::disk('local')->download($file['path'], $file['name']);
    }
}
