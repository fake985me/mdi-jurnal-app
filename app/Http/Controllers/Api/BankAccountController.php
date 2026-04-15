<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\Payment;
use Illuminate\Http\Request;

class BankAccountController extends Controller
{
    public function index(Request $request)
    {
        $query = BankAccount::query();

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('bank_name', 'like', "%{$search}%")
                  ->orWhere('account_name', 'like', "%{$search}%")
                  ->orWhere('account_number', 'like', "%{$search}%");
            });
        }

        $accounts = $query->orderBy('is_default', 'desc')
            ->orderBy('bank_name')
            ->get();

        // Append current balance to each account
        $accounts->transform(function ($account) {
            $account->current_balance = $account->current_balance;
            $account->total_transactions = $account->payments()->where('status', 'paid')->count();
            return $account;
        });

        return response()->json($accounts);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'bank_name' => 'required|string|max:255',
            'account_name' => 'required|string|max:255',
            'account_number' => 'required|string|max:50|unique:bank_accounts,account_number',
            'branch' => 'nullable|string|max:255',
            'currency' => 'nullable|string|max:10',
            'initial_balance' => 'nullable|numeric|min:0',
            'is_active' => 'nullable|boolean',
            'is_default' => 'nullable|boolean',
            'notes' => 'nullable|string',
        ]);

        $account = BankAccount::create([
            'bank_name' => $validated['bank_name'],
            'account_name' => $validated['account_name'],
            'account_number' => $validated['account_number'],
            'branch' => $validated['branch'] ?? null,
            'currency' => $validated['currency'] ?? 'IDR',
            'initial_balance' => $validated['initial_balance'] ?? 0,
            'is_active' => $validated['is_active'] ?? true,
            'is_default' => $validated['is_default'] ?? false,
            'notes' => $validated['notes'] ?? null,
        ]);

        // If set as default, unset others
        if ($account->is_default) {
            $account->setAsDefault();
        }

        return response()->json($account, 201);
    }

    public function show(BankAccount $bankAccount)
    {
        $bankAccount->current_balance = $bankAccount->current_balance;
        $bankAccount->total_transactions = $bankAccount->payments()->where('status', 'paid')->count();

        return response()->json($bankAccount);
    }

    public function update(Request $request, BankAccount $bankAccount)
    {
        $validated = $request->validate([
            'bank_name' => 'nullable|string|max:255',
            'account_name' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:50|unique:bank_accounts,account_number,' . $bankAccount->id,
            'branch' => 'nullable|string|max:255',
            'currency' => 'nullable|string|max:10',
            'initial_balance' => 'nullable|numeric|min:0',
            'is_active' => 'nullable|boolean',
            'is_default' => 'nullable|boolean',
            'notes' => 'nullable|string',
        ]);

        $bankAccount->update($validated);

        // If set as default, unset others
        if (isset($validated['is_default']) && $validated['is_default']) {
            $bankAccount->setAsDefault();
        }

        return response()->json($bankAccount);
    }

    public function destroy(BankAccount $bankAccount)
    {
        // Check if bank account has payments
        if ($bankAccount->payments()->exists()) {
            return response()->json([
                'message' => 'Cannot delete bank account with existing payments. Deactivate it instead.'
            ], 422);
        }

        $bankAccount->delete();
        return response()->json(['message' => 'Bank account deleted successfully']);
    }

    /**
     * Set a bank account as default
     */
    public function setDefault(BankAccount $bankAccount)
    {
        $bankAccount->setAsDefault();
        return response()->json($bankAccount);
    }

    /**
     * Get transactions (payments) for a specific bank account
     */
    public function transactions(Request $request, BankAccount $bankAccount)
    {
        $query = $bankAccount->payments()
            ->with(['payable', 'user'])
            ->orderBy('payment_date', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('start_date')) {
            $query->whereDate('payment_date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('payment_date', '<=', $request->end_date);
        }

        $payments = $query->paginate($request->per_page ?? 15);

        return response()->json($payments);
    }

    /**
     * Get summary/statistics for all bank accounts
     */
    public function summary()
    {
        $accounts = BankAccount::active()->get();

        $summary = $accounts->map(function ($account) {
            $paidPayments = $account->payments()->where('status', 'paid');

            $totalIncoming = (clone $paidPayments)
                ->whereIn('payable_type', [
                    \App\Models\Sale::class,
                    \App\Models\ProjectContract::class,
                    \App\Models\MsaContract::class,
                ])
                ->sum('amount');

            $totalOutgoing = (clone $paidPayments)
                ->where('payable_type', \App\Models\Purchase::class)
                ->sum('amount');

            return [
                'id' => $account->id,
                'bank_name' => $account->bank_name,
                'account_name' => $account->account_name,
                'account_number' => $account->account_number,
                'currency' => $account->currency,
                'initial_balance' => (float) $account->initial_balance,
                'total_incoming' => (float) $totalIncoming,
                'total_outgoing' => (float) $totalOutgoing,
                'current_balance' => (float) $account->initial_balance + $totalIncoming - $totalOutgoing,
                'is_default' => $account->is_default,
            ];
        });

        return response()->json([
            'accounts' => $summary,
            'totals' => [
                'total_balance' => $summary->sum('current_balance'),
                'total_incoming' => $summary->sum('total_incoming'),
                'total_outgoing' => $summary->sum('total_outgoing'),
            ],
        ]);
    }
}
