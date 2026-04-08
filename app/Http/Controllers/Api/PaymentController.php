<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\Purchase;
use App\Models\MsaContract;
use App\Models\ProjectContract;
use App\Models\Warehouse;
use App\Services\StockSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

    protected function reversePayableMap(): array
    {
        return array_flip($this->payableMap());
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

    /**
     * Sync parent transaction status based on payment status changes.
     * When payment is marked 'paid', update parent to 'paid' and handle stock.
     * When payment is cancelled, revert parent to 'unpaid'.
     */
    protected function syncParentStatus(Payment $payment, ?string $oldStatus = null)
    {
        $payable = $payment->payable;
        if (!$payable) return;

        $newStatus = $payment->status;

        // Purchase: sync status + handle stock
        if ($payable instanceof Purchase) {
            if ($oldStatus !== 'paid' && $newStatus === 'paid') {
                $payable->update(['status' => 'paid', 'received_date' => now()]);

                // Add stock when purchase is paid
                $warehouseId = $payable->warehouse_id ?? Warehouse::getDefault()?->id;
                $stockService = new StockSyncService();
                foreach ($payable->items as $item) {
                    $itemIsForAsset = $item->is_for_asset ?? $payable->is_for_asset ?? false;
                    if ($itemIsForAsset) {
                        $product = \App\Models\Product::find($item->product_id);
                        for ($i = 0; $i < $item->quantity; $i++) {
                            \App\Models\Asset::create([
                                'asset_code' => \App\Models\Asset::generateAssetCode(),
                                'name' => $product->title ?? $product->name ?? 'Asset',
                                'product_id' => $item->product_id,
                                'purchase_item_id' => $item->id,
                                'category' => $product->category ?? null,
                                'brand' => $product->brand ?? null,
                                'model' => $product->model ?? null,
                                'condition' => 'good',
                                'status' => 'active',
                                'purchase_date' => $payable->order_date,
                                'purchase_price' => $item->unit_price,
                                'current_value' => $item->unit_price,
                            ]);
                        }
                    } else {
                        $stockService->addStock(
                            $item->product_id,
                            $item->quantity,
                            'purchase',
                            $payable->id,
                            "Purchase #{$payable->po_number} paid",
                            $warehouseId
                        );
                    }
                }
            } elseif ($oldStatus === 'paid' && $newStatus === 'cancelled') {
                $payable->update(['status' => 'unpaid', 'received_date' => null]);

                // Deduct stock when purchase payment cancelled
                $warehouseId = $payable->warehouse_id ?? Warehouse::getDefault()?->id;
                $stockService = new StockSyncService();
                foreach ($payable->items as $item) {
                    $itemIsForAsset = $item->is_for_asset ?? $payable->is_for_asset ?? false;
                    if (!$itemIsForAsset) {
                        $stockService->deductStock(
                            $item->product_id,
                            $item->quantity,
                            'purchase_cancelled',
                            $payable->id,
                            "Purchase #{$payable->po_number} payment cancelled",
                            $warehouseId
                        );
                    }
                }
            } elseif ($newStatus === 'unpaid') {
                $payable->update(['status' => 'unpaid']);
            }
        }

        // Sale: sync status
        if ($payable instanceof Sale) {
            if ($oldStatus !== 'paid' && $newStatus === 'paid') {
                $payable->update(['status' => 'completed']);
            } elseif ($oldStatus === 'paid' && $newStatus === 'cancelled') {
                $payable->update(['status' => 'pending']);
            } elseif ($newStatus === 'unpaid') {
                $payable->update(['status' => 'pending']);
            }
        }

        // ProjectContract: sync status
        if ($payable instanceof ProjectContract) {
            if ($newStatus === 'paid') {
                $payable->update(['status' => 'completed']);
            } elseif ($newStatus === 'unpaid') {
                $payable->update(['status' => 'active']);
            }
        }
    }

    /**
     * Get a human-readable label for a payable type.
     */
    protected function getPayableLabel($payable): string
    {
        if ($payable instanceof Purchase) return $payable->po_number;
        if ($payable instanceof Sale) return $payable->invoice_number;
        if ($payable instanceof ProjectContract) return $payable->contract_number;
        if ($payable instanceof MsaContract) return $payable->msa_code;
        return '—';
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

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('reference_number', 'like', "%{$search}%")
                  ->orWhere('method', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        $payments = $query->paginate($request->per_page ?? 15);

        // Append reverse type label on each payment
        $reverseMap = $this->reversePayableMap();
        /** @var \Illuminate\Pagination\LengthAwarePaginator $payments */
        $payments->through(function ($payment) use ($reverseMap) {
            $payment->setAttribute('payable_type_label', $reverseMap[$payment->payable_type] ?? $payment->payable_type);
            $payment->setAttribute('payable_reference', $payment->payable ? $this->getPayableLabel($payment->payable) : '—');
            $payment->setAttribute('payable_party', $this->getPayableParty($payment->payable));
            return $payment;
        });

        return response()->json($payments);
    }

    protected function getPayableParty($payable): string
    {
        if (!$payable) return '—';
        if ($payable instanceof Purchase) return $payable->supplier_name ?? '—';
        if ($payable instanceof Sale) return $payable->customer_name ?? '—';
        if ($payable instanceof ProjectContract) return $payable->project?->project_name ?? '—';
        if ($payable instanceof MsaContract) return $payable->project?->project_name ?? '—';
        return '—';
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
            'status' => 'nullable|in:unpaid,paid,cancelled',
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        try {
            $payable = $this->resolvePayable($validated['payable_type'], $validated['payable_id']);
            $payableType = get_class($payable);
            $newStatus = $validated['status'] ?? 'unpaid';

            $limitError = $this->validatePaymentLimit(
                $payableType,
                $payable->id,
                (float) $validated['amount'],
                null,
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

            $payment = Payment::create([
                'payable_type' => $payableType,
                'payable_id' => $payable->id,
                'payment_type' => $validated['payment_type'],
                'amount' => $validated['amount'],
                'payment_date' => $validated['payment_date'] ?? now()->toDateString(),
                'method' => $validated['method'] ?? null,
                'status' => $newStatus,
                'reference_number' => $validated['reference_number'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'user_id' => auth()->id(),
            ]);

            // Sync parent status
            $this->syncParentStatus($payment, null);

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
            'status' => 'nullable|in:unpaid,paid,cancelled',
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $oldStatus = $payment->status;
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

        DB::beginTransaction();
        try {
            $payment->update($validated);

            // Sync parent status if status changed
            if ($oldStatus !== $newStatus) {
                $this->syncParentStatus($payment, $oldStatus);
            }

            DB::commit();
            return response()->json($payment->load(['payable', 'user']));
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function destroy(Payment $payment)
    {
        DB::beginTransaction();
        try {
            $oldStatus = $payment->status;
            
            // If was paid, need to revert stock changes
            if ($oldStatus === 'paid') {
                $payment->status = 'cancelled';
                $this->syncParentStatus($payment, $oldStatus);
            }

            $payment->delete();
            DB::commit();
            return response()->json(['message' => 'Payment deleted successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Get summary statistics for payments.
     */
    public function summary(Request $request)
    {
        $query = Payment::query();

        if ($request->filled('start_date')) {
            $query->whereDate('payment_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('payment_date', '<=', $request->end_date);
        }

        $totalUnpaid = (clone $query)->where('status', 'unpaid')->sum('amount');
        $totalPaid = (clone $query)->where('status', 'paid')->sum('amount');
        $totalCancelled = (clone $query)->where('status', 'cancelled')->sum('amount');
        $countUnpaid = (clone $query)->where('status', 'unpaid')->count();
        $countPaid = (clone $query)->where('status', 'paid')->count();
        $countAll = (clone $query)->count();

        // This month
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();
        $thisMonthPaid = Payment::where('status', 'paid')
            ->whereBetween('payment_date', [$monthStart, $monthEnd])
            ->sum('amount');

        return response()->json([
            'total_unpaid' => (float) $totalUnpaid,
            'total_paid' => (float) $totalPaid,
            'total_cancelled' => (float) $totalCancelled,
            'count_unpaid' => $countUnpaid,
            'count_paid' => $countPaid,
            'count_all' => $countAll,
            'this_month_paid' => (float) $thisMonthPaid,
        ]);
    }
}
