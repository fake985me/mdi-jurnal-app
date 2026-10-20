<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StockTransfer;
use App\Models\Warehouse;
use App\Models\CurrentStock;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockTransferController extends Controller
{
    /**
     * Display a listing of stock transfers.
     */
    public function index(Request $request)
    {
        $query = StockTransfer::with(['fromWarehouse', 'toWarehouse', 'product', 'transferredBy', 'receivedBy']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('transfer_code', 'like', "%{$search}%")
                    ->orWhereHas('product', function ($pq) use ($search) {
                        $pq->where('title', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('from_warehouse_id')) {
            $query->where('from_warehouse_id', $request->from_warehouse_id);
        }

        if ($request->filled('to_warehouse_id')) {
            $query->where('to_warehouse_id', $request->to_warehouse_id);
        }

        $transfers = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json($transfers);
    }

    /**
     * Store a newly created stock transfer.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'from_warehouse_id' => 'required|exists:warehouses,id',
            'to_warehouse_id' => 'required|exists:warehouses,id|different:from_warehouse_id',
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string',
        ]);

        // Check if source warehouse has enough stock
        $sourceStock = CurrentStock::where('warehouse_id', $validated['from_warehouse_id'])
            ->where('product_id', $validated['product_id'])
            ->first();

        if (!$sourceStock || $sourceStock->quantity < $validated['quantity']) {
            return response()->json([
                'message' => 'Insufficient stock in source warehouse. Available: ' . 
                    ($sourceStock ? $sourceStock->quantity : 0)
            ], 422);
        }

        $validated['transferred_by'] = auth()->id();
        $validated['status'] = StockTransfer::STATUS_PENDING;

        $transfer = StockTransfer::create($validated);
        $transfer->load(['fromWarehouse', 'toWarehouse', 'product', 'transferredBy']);

        return response()->json($transfer, 201);
    }

    /**
     * Display the specified stock transfer.
     */
    public function show(StockTransfer $stockTransfer)
    {
        $stockTransfer->load(['fromWarehouse', 'toWarehouse', 'product', 'transferredBy', 'receivedBy']);
        
        return response()->json($stockTransfer);
    }

    public function serials(Request $request, StockTransfer $stockTransfer)
    {
        $data = $request->validate(['serial_numbers' => 'required|array|min:1',
            'serial_numbers.*' => 'required|string|max:255']);
        $serials = array_map('trim', $data['serial_numbers']);
        try {
            return DB::transaction(function () use ($stockTransfer, $serials) {
                $candidate = $stockTransfer->projectItem;
                $project = $candidate?->project()->lockForUpdate()->firstOrFail();
                $transfer = StockTransfer::lockForUpdate()->findOrFail($stockTransfer->id);
                $item = $candidate ? \App\Models\ProjectInvestmentItem::lockForUpdate()->findOrFail($candidate->id) : null;
                $old = $transfer->serial_numbers ?? [];
                if (!in_array($transfer->status, ['pending', 'in_transit'], true) ||
                    ($transfer->status === 'in_transit' && $old && $old !== $serials)) {
                    throw new \RuntimeException('SN pengiriman yang sudah diproses tidak dapat diganti.');
                }
                $normalized = array_map('strtolower', $serials);
                if (count($serials) !== $transfer->quantity || in_array('', $normalized, true) || count(array_unique($normalized)) !== count($normalized)) {
                    throw new \RuntimeException('Isi satu SN unik untuk setiap unit pada transfer ini.');
                }
                if ($project) {
                    if (!in_array($project->status, ['approved', 'active'], true) && $transfer->project_action !== 'return') throw new \RuntimeException('Proyek tidak aktif untuk pencatatan SN.');
                    if ($transfer->project_action === 'allocation' && $transfer->quantity === $item->quantity) {
                        if ($item->returned_item_id && $serials !== ($item->serial_numbers ?? [])) throw new \RuntimeException('SN stok RMA harus sesuai unit asal.');
                        if ($transfer->status === 'in_transit' && $item->serial_numbers && $serials !== $item->serial_numbers) throw new \RuntimeException('SN harus sesuai barang proyek yang dikirim.');
                        foreach ($project->items()->where('product_id', $transfer->product_id)->where('id', '!=', $item->id)->get() as $other) {
                            if (array_intersect($normalized, array_map('strtolower', $other->serial_numbers ?? []))) throw new \RuntimeException('SN sudah digunakan pada barang proyek lainnya.');
                        }
                        $item->update(['serial_numbers' => $serials]);
                    } elseif ($serials !== $old) {
                        throw new \RuntimeException('Gunakan proses penggantian barang untuk mengubah SN retur atau pengganti.');
                    }
                    if ($serials !== $old) \App\Models\ProjectItemChange::create([
                        'project_investment_id' => $project->id, 'project_investment_item_id' => $item->id,
                        'type' => 'serials', 'quantity' => $transfer->quantity, 'old_serial_numbers' => $old,
                        'new_serial_numbers' => $serials, 'notes' => 'Pencatatan SN transfer '.$transfer->transfer_code,
                        'user_id' => auth()->id(), 'details' => ['transfer_id' => $transfer->id,
                            'product' => $item->product?->title, 'unit_price' => $item->unit_price, 'total_price' => $item->total_price],
                    ]);
                }
                $transfer->update(['serial_numbers' => $serials]);
                return response()->json($transfer->load(['product', 'fromWarehouse', 'toWarehouse']));
            });
        } catch (\Exception $error) {
            return response()->json(['message' => $error->getMessage()], 422);
        }
    }

    /**
     * Start the transfer (mark as in transit)
     */
    public function startTransfer(StockTransfer $stockTransfer)
    {
        if ($stockTransfer->project_investment_item_id) {
            try { return response()->json(['transfer' => app(\App\Services\ProjectStockTransferService::class)->transition($stockTransfer, 'in_transit')]); }
            catch (\Exception $error) { return response()->json(['message' => $error->getMessage()], 422); }
        }
        if (!$stockTransfer->canBeStarted()) {
            return response()->json([
                'message' => 'Transfer cannot be started. Current status: ' . $stockTransfer->status
            ], 422);
        }

        // Check stock availability again
        $sourceStock = CurrentStock::where('warehouse_id', $stockTransfer->from_warehouse_id)
            ->where('product_id', $stockTransfer->product_id)
            ->first();

        if (!$sourceStock || $sourceStock->quantity < $stockTransfer->quantity) {
            return response()->json([
                'message' => 'Insufficient stock in source warehouse'
            ], 422);
        }

        DB::transaction(function () use ($stockTransfer, $sourceStock) {
            // Deduct from source warehouse
            $sourceStock->quantity -= $stockTransfer->quantity;
            $sourceStock->last_updated = now();
            $sourceStock->save();

            // If source is default warehouse, also deduct from products.stock
            $this->syncProductStockIfDefaultWarehouse(
                $stockTransfer->product_id,
                $stockTransfer->from_warehouse_id,
                -$stockTransfer->quantity
            );

            // Update transfer status
            $stockTransfer->status = StockTransfer::STATUS_IN_TRANSIT;
            $stockTransfer->transferred_at = now();
            $stockTransfer->save();
        });

        $stockTransfer->load(['fromWarehouse', 'toWarehouse', 'product']);

        return response()->json([
            'message' => 'Transfer started successfully',
            'transfer' => $stockTransfer
        ]);
    }

    /**
     * Complete the transfer (receive at destination)
     */
    public function completeTransfer(Request $request, StockTransfer $stockTransfer)
    {
        if ($stockTransfer->project_investment_item_id) {
            try { return response()->json(['transfer' => app(\App\Services\ProjectStockTransferService::class)->transition($stockTransfer, 'completed')]); }
            catch (\Exception $error) { return response()->json(['message' => $error->getMessage()], 422); }
        }
        if (!$stockTransfer->canBeCompleted()) {
            return response()->json([
                'message' => 'Transfer cannot be completed. Current status: ' . $stockTransfer->status
            ], 422);
        }

        DB::transaction(function () use ($stockTransfer) {
            // Add to destination warehouse
            $destStock = CurrentStock::firstOrCreate(
                [
                    'warehouse_id' => $stockTransfer->to_warehouse_id,
                    'product_id' => $stockTransfer->product_id
                ],
                ['quantity' => 0, 'last_updated' => now()]
            );

            $destStock->quantity += $stockTransfer->quantity;
            $destStock->last_updated = now();
            $destStock->save();

            // If destination is default warehouse, also add to products.stock
            $this->syncProductStockIfDefaultWarehouse(
                $stockTransfer->product_id,
                $stockTransfer->to_warehouse_id,
                $stockTransfer->quantity
            );

            // Update transfer status
            $stockTransfer->status = StockTransfer::STATUS_COMPLETED;
            $stockTransfer->received_by = auth()->id();
            $stockTransfer->received_at = now();
            $stockTransfer->save();
        });

        $stockTransfer->load(['fromWarehouse', 'toWarehouse', 'product', 'receivedBy']);

        return response()->json([
            'message' => 'Transfer completed successfully',
            'transfer' => $stockTransfer
        ]);
    }

    /**
     * Cancel the transfer
     */
    public function cancelTransfer(Request $request, StockTransfer $stockTransfer)
    {
        if ($stockTransfer->project_investment_item_id) {
            try { return response()->json(['transfer' => app(\App\Services\ProjectStockTransferService::class)->transition($stockTransfer, 'cancelled')]); }
            catch (\Exception $error) { return response()->json(['message' => $error->getMessage()], 422); }
        }
        if (!$stockTransfer->canBeCancelled()) {
            return response()->json([
                'message' => 'Transfer cannot be cancelled. Current status: ' . $stockTransfer->status
            ], 422);
        }

        DB::transaction(function () use ($stockTransfer) {
            // If already in transit, return stock to source
            if ($stockTransfer->isInTransit()) {
                $sourceStock = CurrentStock::firstOrCreate(
                    [
                        'warehouse_id' => $stockTransfer->from_warehouse_id,
                        'product_id' => $stockTransfer->product_id
                    ],
                    ['quantity' => 0, 'last_updated' => now()]
                );

                $sourceStock->quantity += $stockTransfer->quantity;
                $sourceStock->last_updated = now();
                $sourceStock->save();

                // If source is default warehouse, also restore products.stock
                $this->syncProductStockIfDefaultWarehouse(
                    $stockTransfer->product_id,
                    $stockTransfer->from_warehouse_id,
                    $stockTransfer->quantity
                );
            }

            $stockTransfer->status = StockTransfer::STATUS_CANCELLED;
            $stockTransfer->save();
        });

        return response()->json([
            'message' => 'Transfer cancelled successfully',
            'transfer' => $stockTransfer
        ]);
    }

    /**
     * Get transfer statistics
     */
    public function statistics()
    {
        $stats = [
            'total_transfers' => StockTransfer::count(),
            'pending' => StockTransfer::where('status', StockTransfer::STATUS_PENDING)->count(),
            'in_transit' => StockTransfer::where('status', StockTransfer::STATUS_IN_TRANSIT)->count(),
            'completed' => StockTransfer::where('status', StockTransfer::STATUS_COMPLETED)->count(),
            'cancelled' => StockTransfer::where('status', StockTransfer::STATUS_CANCELLED)->count(),
            'recent_transfers' => StockTransfer::with(['fromWarehouse', 'toWarehouse', 'product'])
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get(),
        ];

        return response()->json($stats);
    }

    /**
     * Sync product stock if warehouse is the default warehouse
     * @param int $productId
     * @param int $warehouseId
     * @param int $quantityChange (positive to add, negative to deduct)
     */
    protected function syncProductStockIfDefaultWarehouse($productId, $warehouseId, $quantityChange)
    {
        $defaultWarehouse = Warehouse::getDefault();
        
        if (!$defaultWarehouse || $defaultWarehouse->id !== $warehouseId) {
            return;
        }

        $product = Product::find($productId);
        if ($product) {
            $product->stock = max(0, ($product->stock ?? 0) + $quantityChange);
            $product->save();
        }
    }
}
