<?php

namespace App\Services;

use App\Models\CurrentStock;
use App\Models\Product;
use App\Models\StockTransfer;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;

class StockTransferService
{
    /**
     * Execute transfer and immediately complete it.
     */
    public function transferNow(
        int $fromWarehouseId,
        int $toWarehouseId,
        int $productId,
        int $quantity,
        ?string $notes = null,
        ?int $userId = null
    ): StockTransfer {
        return DB::transaction(function () use ($fromWarehouseId, $toWarehouseId, $productId, $quantity, $notes, $userId) {
            $sourceStock = CurrentStock::where('warehouse_id', $fromWarehouseId)
                ->where('product_id', $productId)
                ->lockForUpdate()
                ->first();

            if (!$sourceStock || $sourceStock->quantity < $quantity) {
                $available = $sourceStock ? $sourceStock->quantity : 0;
                throw new \Exception("Insufficient stock in source warehouse. Available: {$available}");
            }

            $transfer = StockTransfer::create([
                'from_warehouse_id' => $fromWarehouseId,
                'to_warehouse_id' => $toWarehouseId,
                'product_id' => $productId,
                'quantity' => $quantity,
                'status' => StockTransfer::STATUS_COMPLETED,
                'notes' => $notes,
                'transferred_by' => $userId,
                'received_by' => $userId,
                'transferred_at' => now(),
                'received_at' => now(),
            ]);

            // Deduct from source
            $sourceStock->quantity -= $quantity;
            $sourceStock->last_updated = now();
            $sourceStock->save();

            // Add to destination
            $destStock = CurrentStock::firstOrCreate(
                ['warehouse_id' => $toWarehouseId, 'product_id' => $productId],
                ['quantity' => 0, 'last_updated' => now()]
            );
            $destStock->quantity += $quantity;
            $destStock->last_updated = now();
            $destStock->save();

            // Sync product stock if default warehouse is involved
            $this->syncProductStockIfDefaultWarehouse($productId, $fromWarehouseId, -$quantity);
            $this->syncProductStockIfDefaultWarehouse($productId, $toWarehouseId, $quantity);

            return $transfer;
        });
    }

    protected function syncProductStockIfDefaultWarehouse(int $productId, int $warehouseId, int $quantityChange): void
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
