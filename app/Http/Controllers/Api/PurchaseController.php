<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Product;
use App\Models\CurrentStock;
use App\Models\StockTransaction;
use App\Models\Payment;
use App\Models\Warehouse;
use App\Services\StockSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = Purchase::with(['user', 'items.product', 'warehouse', 'payments', 'supplier']);

            // Filter by status
            if ($request->has('status') && !empty($request->status)) {
                $query->where('status', $request->status);
            }

            // Filter by date range
            if ($request->has('start_date') && !empty($request->start_date)) {
                $query->whereDate('order_date', '>=', $request->start_date);
            }
            if ($request->has('end_date') && !empty($request->end_date)) {
                $query->whereDate('order_date', '<=', $request->end_date);
            }

            // Search
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('po_number', 'like', "%{$search}%")
                      ->orWhere('supplier_name', 'like', "%{$search}%")
                      ->orWhereHas('supplier', function ($sq) use ($search) {
                          $sq->where('name', 'like', "%{$search}%")
                            ->orWhere('company', 'like', "%{$search}%");
                      });
                });
            }

            $purchases = $query->orderBy('created_at', 'desc')
                ->paginate($request->per_page ?? 15);

            // Append payment summary to each purchase
            /** @var \Illuminate\Pagination\LengthAwarePaginator $purchases */
            $purchases->through(function ($purchase) {
                $paidAmount = $purchase->payments
                    ->where('status', 'paid')
                    ->sum('amount');
                $purchase->setAttribute('paid_amount', (float) $paidAmount);
                $purchase->setAttribute('payment_status', $this->getPaymentStatus($purchase));
                return $purchase;
            });

            return response()->json($purchases);
        } catch (\Exception $e) {
            \Log::error('Purchases API Error: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'message' => 'Error loading purchases',
                'error' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    }

    protected function getPaymentStatus($purchase): string
    {
        $total = (float) ($purchase->total_amount ?? 0);
        $paid = (float) ($purchase->paid_amount ?? 0);
        if ($total <= 0) return 'unpaid';
        if ($paid >= $total - 0.01) return 'paid';
        if ($paid > 0) return 'partial';
        return 'unpaid';
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'po_number' => 'required|unique:purchases,po_number',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'supplier_name' => 'required_without:supplier_id|string|max:255',
            'supplier_address' => 'nullable|string',
            'supplier_phone' => 'nullable|string',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'order_date' => 'required|date',
            'is_for_asset' => 'boolean',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.is_for_asset' => 'boolean',
        ]);

        $isForAsset = $validated['is_for_asset'] ?? false;
        $warehouseId = $validated['warehouse_id'] ?? Warehouse::getDefault()?->id;

        DB::beginTransaction();
        try {
            // If supplier_id provided, auto-fill supplier name from relationship
            $supplierName = $validated['supplier_name'] ?? null;
            if (!empty($validated['supplier_id'])) {
                $supplier = \App\Models\Supplier::find($validated['supplier_id']);
                if ($supplier) {
                    $supplierName = $supplierName ?: $supplier->display_name;
                }
            }

            // Create purchase with status 'unpaid' (payment determines status)
            $purchase = Purchase::create([
                'po_number' => $validated['po_number'],
                'supplier_id' => $validated['supplier_id'] ?? null,
                'supplier_name' => $supplierName,
                'supplier_address' => $validated['supplier_address'] ?? null,
                'supplier_phone' => $validated['supplier_phone'] ?? null,
                'warehouse_id' => $warehouseId,
                'order_date' => $validated['order_date'],
                'status' => 'unpaid',
                'is_for_asset' => $isForAsset,
                'notes' => $validated['notes'] ?? null,
                'user_id' => auth()->id(),
                'total_amount' => 0,
                'received_date' => null,
            ]);

            $totalAmount = 0;

            // Create purchase items (NO stock added here — stock added when payment is marked paid)
            foreach ($validated['items'] as $item) {
                $subtotal = $item['quantity'] * $item['unit_price'];
                $totalAmount += $subtotal;
                $itemIsForAsset = $item['is_for_asset'] ?? $isForAsset;

                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $subtotal,
                    'is_for_asset' => $itemIsForAsset,
                ]);
            }

            // Update total amount
            $purchase->update(['total_amount' => $totalAmount]);

            // Auto-create payment record (unpaid)
            Payment::create([
                'payable_type' => Purchase::class,
                'payable_id' => $purchase->id,
                'payment_type' => 'full',
                'amount' => $totalAmount,
                'payment_date' => $validated['order_date'],
                'method' => null,
                'status' => 'unpaid',
                'reference_number' => $purchase->po_number,
                'notes' => "Auto-created from Purchase #{$purchase->po_number}",
                'user_id' => auth()->id(),
            ]);

            DB::commit();
            return response()->json($purchase->load(['items.product', 'user']), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function show($id)
    {
        $purchase = Purchase::with(['items.product', 'user', 'warehouse', 'payments', 'supplier'])->findOrFail($id);
        return response()->json($purchase);
    }

    public function update(Request $request, $id)
    {
        $purchase = Purchase::with('items')->findOrFail($id);

        $validated = $request->validate([
            'notes' => 'nullable|string',
        ]);

        $purchase->update($validated);

        return response()->json($purchase->load(['items.product', 'user']));
    }

    public function destroy($id)
    {
        $purchase = Purchase::with('items')->findOrFail($id);

        DB::beginTransaction();
        try {
            // If purchase was paid, deduct stock before deletion
            if ($purchase->status === 'paid') {
                $stockService = new StockSyncService();
                foreach ($purchase->items as $item) {
                    $itemIsForAsset = $item->is_for_asset ?? $purchase->is_for_asset ?? false;
                    if (!$itemIsForAsset) {
                        $stockService->deductStock(
                            $item->product_id,
                            $item->quantity,
                            'purchase_deleted',
                            $purchase->id,
                            "Purchase #{$purchase->po_number} deleted - stock deducted",
                            $purchase->warehouse_id ?? Warehouse::getDefault()?->id
                        );
                    }
                }
            }

            // Delete associated payments
            $purchase->payments()->delete();
            $purchase->delete();
            DB::commit();
            return response()->json(['message' => 'Purchase deleted successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
