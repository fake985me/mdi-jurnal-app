<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\CurrentStock;
use App\Models\Warehouse;
use Illuminate\Console\Command;

class SyncProductStocksToWarehouse extends Command
{
    protected $signature = 'sync:product-stocks-warehouse {--dry-run : Show what would be synced without making changes}';
    protected $description = 'Sync product stock values to the default warehouse current_stocks table';

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
        
        // Get all products with stock
        $products = Product::where('is_asset', false)
            ->orWhereNull('is_asset')
            ->get();

        $this->info("Found {$products->count()} products to sync.");
        
        $synced = 0;
        $created = 0;
        $updated = 0;
        $skipped = 0;

        $progressBar = $this->output->createProgressBar($products->count());
        $progressBar->start();

        foreach ($products as $product) {
            // Check if current_stock entry exists for this warehouse
            $currentStock = CurrentStock::where('product_id', $product->id)
                ->where('warehouse_id', $defaultWarehouse->id)
                ->first();

            if (!$currentStock) {
                // Create new entry
                if (!$dryRun) {
                    CurrentStock::create([
                        'product_id' => $product->id,
                        'warehouse_id' => $defaultWarehouse->id,
                        'quantity' => $product->stock ?? 0,
                        'last_updated' => now(),
                    ]);
                }
                $created++;
            } else {
                // Update if different
                if ($currentStock->quantity != $product->stock) {
                    if (!$dryRun) {
                        $currentStock->update([
                            'quantity' => $product->stock ?? 0,
                            'last_updated' => now(),
                        ]);
                    }
                    $updated++;
                } else {
                    $skipped++;
                }
            }

            $synced++;
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);
        
        $mode = $dryRun ? '[DRY RUN] ' : '';
        $this->info("{$mode}Sync completed!");
        $this->table(
            ['Action', 'Count'],
            [
                ['Total Processed', $synced],
                ['Created', $created],
                ['Updated', $updated],
                ['Skipped (same value)', $skipped],
            ]
        );

        if ($dryRun) {
            $this->warn('This was a dry run. No changes were made. Remove --dry-run to apply changes.');
        }

        return 0;
    }
}
