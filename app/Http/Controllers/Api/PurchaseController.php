<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Product;
use App\Models\CurrentStock;
use App\Models\StockTransaction;
use App\Services\StockSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = Purchase::with(['user', 'items.product']);

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
                      ->orWhere('supplier_name', 'like', "%{$search}%");
                });
            }

            $purchases = $query->orderBy('created_at', 'desc')
                ->paginate($request->per_page ?? 15);

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

    public function store(Request $request)
    {
        $validated = $request->validate([
            'po_number' => 'required|unique:purchases,po_number',
            'supplier_name' => 'required|string|max:255',
            'supplier_address' => 'nullable|string',
            'supplier_phone' => 'nullable|string',
            'order_date' => 'required|date',
            'status' => 'required|in:pending,received,cancelled',
            'is_for_asset' => 'boolean',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.is_for_asset' => 'boolean',
        ]);

        $isForAsset = $validated['is_for_asset'] ?? false;

        DB::beginTransaction();
        try {
            // Create purchase
            $purchase = Purchase::create([
                'po_number' => $validated['po_number'],
                'supplier_name' => $validated['supplier_name'],
                'supplier_address' => $validated['supplier_address'] ?? null,
                'supplier_phone' => $validated['supplier_phone'] ?? null,
                'order_date' => $validated['order_date'],
                'status' => $validated['status'],
                'is_for_asset' => $isForAsset,
                'notes' => $validated['notes'] ?? null,
                'user_id' => auth()->id(),
                'total_amount' => 0,
                'received_date' => $validated['status'] === 'received' ? now() : null,
            ]);

            $totalAmount = 0;

            // Create purchase items
            foreach ($validated['items'] as $item) {
                $subtotal = $item['quantity'] * $item['unit_price'];
                $totalAmount += $subtotal;
                $itemIsForAsset = $item['is_for_asset'] ?? $isForAsset;

                $purchaseItem = PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $subtotal,
                    'is_for_asset' => $itemIsForAsset,
                ]);

                // If status is received
                if ($validated['status'] === 'received') {
                    if ($itemIsForAsset) {
                        // Create assets for each quantity
                        $product = Product::find($item['product_id']);
                        for ($i = 0; $i < $item['quantity']; $i++) {
                            \App\Models\Asset::create([
                                'asset_code' => \App\Models\Asset::generateAssetCode(),
                                'name' => $product->title ?? $product->name ?? 'Asset',
                                'product_id' => $item['product_id'],
                                'purchase_item_id' => $purchaseItem->id,
                                'category' => $product->category ?? null,
                                'brand' => $product->brand ?? null,
                                'model' => $product->model ?? null,
                                'condition' => 'good',
                                'status' => 'active',
                                'purchase_date' => $validated['order_date'],
                                'purchase_price' => $item['unit_price'],
                                'current_value' => $item['unit_price'],
                            ]);
                        }
                    } else {
                        // Add to stock (for non-asset items only)
                        $stockService = new StockSyncService();
                        $stockService->addStock(
                            $item['product_id'],
                            $item['quantity'],
                            'purchase',
                            $purchase->id,
                            "Purchase #{$purchase->po_number}"
                        );
                    }
                }
            }

            // Update total amount
            $purchase->update(['total_amount' => $totalAmount]);

            DB::commit();
            return response()->json($purchase->load(['items.product', 'user']), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function show($id)
    {
        $purchase = Purchase::with(['items.product', 'user'])->findOrFail($id);
        return response()->json($purchase);
    }

    public function update(Request $request, $id)
    {
        $purchase = Purchase::with('items')->findOrFail($id);
        $oldStatus = $purchase->status;

        $validated = $request->validate([
            'status' => 'required|in:pending,received,cancelled',
            'notes' => 'nullable|string',
        ]);

        $newStatus = $validated['status'];

        DB::beginTransaction();
        try {
            $stockService = new StockSyncService();

            // Handle status change: pending -> received (add stock)
            if ($oldStatus === 'pending' && $newStatus === 'received') {
                foreach ($purchase->items as $item) {
                    $stockService->addStock(
                        $item->product_id,
                        $item->quantity,
                        'purchase',
                        $purchase->id,
                        "Purchase #{$purchase->po_number} received"
                    );
                }
                $purchase->received_date = now();
            }

            // Handle status change: received -> cancelled (deduct stock)
            if ($oldStatus === 'received' && $newStatus === 'cancelled') {
                foreach ($purchase->items as $item) {
                    $stockService->deductStock(
                        $item->product_id,
                        $item->quantity,
                        'purchase_cancelled',
                        $purchase->id,
                        "Purchase #{$purchase->po_number} cancelled"
                    );
                }
            }

            $purchase->update($validated);
            DB::commit();

            return response()->json($purchase->load(['items.product', 'user']));
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function destroy($id)
    {
        $purchase = Purchase::with('items')->findOrFail($id);

        DB::beginTransaction();
        try {
            // If purchase was received, deduct stock before deletion
            if ($purchase->status === 'received') {
                $stockService = new StockSyncService();
                foreach ($purchase->items as $item) {
                    $stockService->deductStock(
                        $item->product_id,
                        $item->quantity,
                        'purchase_deleted',
                        $purchase->id,
                        "Purchase #{$purchase->po_number} deleted - stock deducted"
                    );
                }
            }

            $purchase->delete();
            DB::commit();
            return response()->json(['message' => 'Purchase deleted successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
