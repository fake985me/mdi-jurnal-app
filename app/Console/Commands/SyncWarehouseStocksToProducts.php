<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\CurrentStock;
use App\Models\Warehouse;
use Illuminate\Console\Command;

class SyncWarehouseStocksToProducts extends Command
{
    protected $signature = 'sync:warehouse-stocks-to-products {--dry-run : Show what would be synced without making changes}';
    protected $description = 'Sync current_stocks quantities from default warehouse back to products table';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        // Get default warehouse
        $defaultWarehouse = Warehouse::getDefault();
        
        if (!$defaultWarehouse) {
            $this->error('No default warehouse found. Please set a warehouse as default first.');
            return 1;
        }

        $this->info("Default warehouse: {$defaultWarehouse->name} (ID: {$defaultWarehouse->id})");
        
        // Get all current_stocks for default warehouse
        $currentStocks = CurrentStock::where('warehouse_id', $defaultWarehouse->id)
            ->where('quantity', '>', 0)
            ->get();

        $this->info("Found {$currentStocks->count()} products with stock in default warehouse.");
        
        $synced = 0;
        $updated = 0;

        foreach ($currentStocks as $stock) {
            $product = Product::find($stock->product_id);
            
            if ($product && $product->stock != $stock->quantity) {
                $this->line("Product #{$product->id} {$product->title}: {$product->stock} -> {$stock->quantity}");
                
                if (!$dryRun) {
                    $product->stock = $stock->quantity;
                    $product->save();
                }
                $updated++;
            }
            $synced++;
        }

        $mode = $dryRun ? '[DRY RUN] ' : '';
        $this->info("{$mode}Sync completed!");
        $this->table(
            ['Action', 'Count'],
            [
                ['Total Processed', $synced],
                ['Products Updated', $updated],
            ]
        );

        if ($dryRun) {
            $this->warn('This was a dry run. No changes were made. Remove --dry-run to apply changes.');
        }

        return 0;
    }
}
