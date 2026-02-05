<?php

namespace App\Services;

use App\Models\Product;
use App\Models\CurrentStock;
use App\Models\StockTransaction;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;

class StockSyncService
{
    /**
     * Get the default warehouse ID
     */
    protected function getDefaultWarehouseId()
    {
        $defaultWarehouse = Warehouse::getDefault();
        return $defaultWarehouse ? $defaultWarehouse->id : null;
    }

    /**
     * Deduct stock from both Product and CurrentStock tables
     * 
     * @param int $productId
     * @param int $quantity
     * @param string $referenceType (sale, lending, etc.)
     * @param int $referenceId
     * @param string|null $notes
     * @param int|null $warehouseId Optional warehouse ID, uses default if not provided
     * @return bool
     * @throws \Exception
     */
    public function deductStock($productId, $quantity, $referenceType, $referenceId, $notes = null, $warehouseId = null)
    {
        DB::beginTransaction();
        try {
            // Get product
            $product = Product::findOrFail($productId);
            
            // Use provided warehouse or default
            $warehouseId = $warehouseId ?? $this->getDefaultWarehouseId();
            
            // Get or create current stock for this warehouse
            $currentStock = CurrentStock::firstOrCreate(
                ['product_id' => $productId, 'warehouse_id' => $warehouseId],
                ['quantity' => $product->stock ?? 0, 'last_updated' => now()]
            );
            
            // Check if enough stock available
            if ($currentStock->quantity < $quantity) {
                throw new \Exception("Insufficient stock for {$product->title}. Available: {$currentStock->quantity}, Requested: {$quantity}");
            }
            
            // Deduct from both tables
            $product->decrement('stock', $quantity);
            $currentStock->quantity -= $quantity;
            $currentStock->last_updated = now();
            $currentStock->save();
            
            // Create stock transaction
            StockTransaction::create([
                'product_id' => $productId,
                'transaction_type' => 'out',
                'quantity' => $quantity,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'user_id' => auth()->id(),
                'notes' => $notes,
            ]);
            
            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    /**
     * Add stock to both Product and CurrentStock tables
     * 
     * @param int $productId
     * @param int $quantity
     * @param string $referenceType (purchase, lending_return, etc.)
     * @param int $referenceId
     * @param string|null $notes
     * @param int|null $warehouseId Optional warehouse ID, uses default if not provided
     * @return bool
     */
    public function addStock($productId, $quantity, $referenceType, $referenceId, $notes = null, $warehouseId = null)
    {
        DB::beginTransaction();
        try {
            // Get product
            $product = Product::findOrFail($productId);
            
            // Use provided warehouse or default
            $warehouseId = $warehouseId ?? $this->getDefaultWarehouseId();
            
            // Get or create current stock for this warehouse
            $currentStock = CurrentStock::firstOrCreate(
                ['product_id' => $productId, 'warehouse_id' => $warehouseId],
                ['quantity' => 0, 'last_updated' => now()]
            );
            
            // Add to both tables
            $product->increment('stock', $quantity);
            $currentStock->quantity += $quantity;
            $currentStock->last_updated = now();
            $currentStock->save();
            
            // Create stock transaction
            StockTransaction::create([
                'product_id' => $productId,
                'transaction_type' => 'in',
                'quantity' => $quantity,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'user_id' => auth()->id(),
                'notes' => $notes,
            ]);
            
            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    /**
     * Sync Product stock with CurrentStock
     * Useful after data imports or when tables get out of sync
     * 
     * @param int|null $productId If null, sync all products
     * @return int Number of products synced
     */
    public function syncProductStock($productId = null)
    {
        $query = Product::query();
        
        if ($productId) {
            $query->where('id', $productId);
        }
        
        $products = $query->get();
        $synced = 0;
        
        foreach ($products as $product) {
            $currentStock = CurrentStock::firstOrCreate(
                ['product_id' => $product->id],
                ['quantity' => 0, 'last_updated' => now()]
            );
            
            // Sync CurrentStock quantity with Product stock
            $currentStock->quantity = $product->stock ?? 0;
            $currentStock->last_updated = now();
            $currentStock->save();
            
            $synced++;
        }
        
        return $synced;
    }
    
    /**
     * Check if product has sufficient stock
     * 
     * @param int $productId
     * @param int $quantity
     * @return bool
     */
    public function hasStock($productId, $quantity)
    {
        $currentStock = CurrentStock::where('product_id', $productId)->first();
        
        if (!$currentStock) {
            return false;
        }
        
        return $currentStock->quantity >= $quantity;
    }
    
    /**
     * Get current stock quantity for a product
     * 
     * @param int $productId
     * @return int
     */
    public function getStockQuantity($productId)
    {
        $currentStock = CurrentStock::where('product_id', $productId)->first();
        return $currentStock ? $currentStock->quantity : 0;
    }
    
    /**
     * Adjust stock with reason tracking
     * 
     * @param int $productId
     * @param string $adjustmentType 'in' or 'out'
     * @param int $quantity
     * @param string $reason (damaged, expired, lost, found, correction, audit, etc.)
     * @param string|null $relatedType (asset, lending, warranty, etc.)
     * @param int|null $relatedId
     * @param string|null $notes
     * @return \App\Models\StockAdjustment
     * @throws \Exception
     */
    public function adjustStock($productId, $adjustmentType, $quantity, $reason, $relatedType = null, $relatedId = null, $notes = null, $warehouseId = null)
    {
        DB::beginTransaction();
        try {
            // Get product
            $product = Product::findOrFail($productId);
            
            // Use provided warehouse or default
            $warehouseId = $warehouseId ?? $this->getDefaultWarehouseId();
            
            // Get or create current stock for this warehouse
            $currentStock = CurrentStock::firstOrCreate(
                ['product_id' => $productId, 'warehouse_id' => $warehouseId],
                ['quantity' => $product->stock ?? 0, 'last_updated' => now()]
            );
            
            $beforeQty = $currentStock->quantity;
            
            // Validate stock for 'out' adjustments
            if ($adjustmentType === 'out' && $currentStock->quantity < $quantity) {
                throw new \Exception("Insufficient stock for {$product->title}. Available: {$currentStock->quantity}, Requested: {$quantity}");
            }
            
            // Calculate after quantity
            $afterQty = $adjustmentType === 'in' 
                ? $beforeQty + $quantity 
                : $beforeQty - $quantity;
            
            // Update stock
            if ($adjustmentType === 'in') {
                $product->increment('stock', $quantity);
                $currentStock->quantity += $quantity;
            } else {
                $product->decrement('stock', $quantity);
                $currentStock->quantity -= $quantity;
            }
            $currentStock->last_updated = now();
            $currentStock->save();
            
            // Create stock adjustment record
            $adjustment = \App\Models\StockAdjustment::create([
                'adjustment_code' => \App\Models\StockAdjustment::generateCode(),
                'product_id' => $productId,
                'adjustment_type' => $adjustmentType,
                'reason' => $reason,
                'quantity' => $quantity,
                'before_qty' => $beforeQty,
                'after_qty' => $afterQty,
                'related_type' => $relatedType,
                'related_id' => $relatedId,
                'notes' => $notes,
                'user_id' => auth()->id(),
            ]);
            
            // Also create stock transaction for audit trail
            StockTransaction::create([
                'product_id' => $productId,
                'transaction_type' => $adjustmentType,
                'quantity' => $quantity,
                'reference_type' => 'adjustment',
                'reference_id' => $adjustment->id,
                'user_id' => auth()->id(),
                'notes' => "[{$reason}] " . ($notes ?? ''),
            ]);
            
            DB::commit();
            return $adjustment;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}

