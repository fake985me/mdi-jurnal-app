<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\Purchase;
use App\Models\MsaContract;
use App\Models\ProjectContract;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    protected function payableMap(): array
    {
        return [
            'sale' => Sale::class,
            'purchase' => Purchase::class,
            'msa_contract' => MsaContract::class,
            'project_contract' => ProjectContract::class,
        ];
    }

    protected function resolvePayable(string $type, int $id)
    {
        $map = $this->payableMap();
        if (!isset($map[$type])) {
            throw new \InvalidArgumentException('Invalid payable_type');
        }

        return $map[$type]::findOrFail($id);
    }

    protected function getPayableLimit($payable)
    {
        if ($payable instanceof Sale) {
            $grandTotal = (float) ($payable->grand_total ?? 0);
            $totalAmount = (float) ($payable->total_amount ?? 0);
            return $grandTotal > 0 ? $grandTotal : $totalAmount;
        }

        if ($payable instanceof Purchase) {
            return (float) ($payable->total_amount ?? 0);
        }

        if ($payable instanceof ProjectContract) {
            return (float) ($payable->contract_value ?? 0);
        }

        // MSA contract doesn't have a fixed total
        if ($payable instanceof MsaContract) {
            return null;
        }

        return null;
    }

    protected function validatePaymentLimit($payableType, $payableId, $amount, $excludePaymentId = null, $newStatus = 'paid')
    {
        $payable = $payableType::findOrFail($payableId);
        $limit = $this->getPayableLimit($payable);

        if ($limit === null || $limit <= 0) {
            return null;
        }

        if ($newStatus === 'cancelled') {
            return null;
        }

        $query = Payment::where('payable_type', $payableType)
            ->where('payable_id', $payableId)
            ->where('status', '!=', 'cancelled');

        if ($excludePaymentId) {
            $query->where('id', '!=', $excludePaymentId);
        }

        $paid = (float) $query->sum('amount');
        $remaining = $limit - $paid;

        if (($paid + $amount) > $limit + 0.0001) {
            return [
                'limit' => $limit,
                'paid' => $paid,
                'remaining' => max(0, $remaining),
            ];
        }

        return null;
    }

    public function index(Request $request)
    {
        $query = Payment::with(['payable', 'user'])->orderBy('created_at', 'desc');

        if ($request->filled('payable_type')) {
            $map = $this->payableMap();
            $type = $request->payable_type;
            if (isset($map[$type])) {
                $query->where('payable_type', $map[$type]);
            }
        }

        if ($request->filled('payable_id')) {
            $query->where('payable_id', $request->payable_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('payment_type')) {
            $query->where('payment_type', $request->payment_type);
        }

        if ($request->filled('start_date')) {
            $query->whereDate('payment_date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('payment_date', '<=', $request->end_date);
        }

        return response()->json($query->paginate($request->per_page ?? 15));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'payable_type' => 'required|in:sale,purchase,msa_contract,project_contract',
            'payable_id' => 'required|integer',
            'payment_type' => 'required|in:dp,full,termin,sharing_profit',
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'nullable|date',
            'method' => 'nullable|string|max:100',
            'status' => 'nullable|in:pending,paid,cancelled',
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        try {
            $payable = $this->resolvePayable($validated['payable_type'], $validated['payable_id']);
            $payableType = get_class($payable);
            $limitError = $this->validatePaymentLimit(
                $payableType,
                $payable->id,
                (float) $validated['amount'],
                null,
                $validated['status'] ?? 'paid'
            );

            if ($limitError) {
                return response()->json([
                    'message' => 'Payment exceeds remaining balance.',
                    'limit' => $limitError['limit'],
                    'paid' => $limitError['paid'],
                    'remaining' => $limitError['remaining'],
                ], 422);
            }

            $payment = Payment::create([
                'payable_type' => $payableType,
                'payable_id' => $payable->id,
                'payment_type' => $validated['payment_type'],
                'amount' => $validated['amount'],
                'payment_date' => $validated['payment_date'] ?? now()->toDateString(),
                'method' => $validated['method'] ?? null,
                'status' => $validated['status'] ?? 'paid',
                'reference_number' => $validated['reference_number'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'user_id' => auth()->id(),
            ]);

            return response()->json($payment->load(['payable', 'user']), 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function show(Payment $payment)
    {
        return response()->json($payment->load(['payable', 'user']));
    }

    public function update(Request $request, Payment $payment)
    {
        $validated = $request->validate([
            'payment_type' => 'nullable|in:dp,full,termin,sharing_profit',
            'amount' => 'nullable|numeric|min:0.01',
            'payment_date' => 'nullable|date',
            'method' => 'nullable|string|max:100',
            'status' => 'nullable|in:pending,paid,cancelled',
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $newAmount = (float) ($validated['amount'] ?? $payment->amount);
        $newStatus = $validated['status'] ?? $payment->status;
        $limitError = $this->validatePaymentLimit(
            $payment->payable_type,
            $payment->payable_id,
            $newAmount,
            $payment->id,
            $newStatus
        );

        if ($limitError) {
            return response()->json([
                'message' => 'Payment exceeds remaining balance.',
                'limit' => $limitError['limit'],
                'paid' => $limitError['paid'],
                'remaining' => $limitError['remaining'],
            ], 422);
        }

        $payment->update($validated);

        return response()->json($payment->load(['payable', 'user']));
    }

    public function destroy(Payment $payment)
    {
        $payment->delete();
        return response()->json(['message' => 'Payment deleted successfully']);
    }
}
