<?php

namespace App\Console\Commands;

use App\Models\CurrentStock;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupDuplicateStocks extends Command
{
    protected $signature = 'cleanup:duplicate-stocks {--dry-run : Show duplicates without deleting}';
    protected $description = 'Cleanup duplicate entries in current_stocks table';

    public function handle()
    {
        $dryRun = $this->option('dry-run');

        $this->info('Checking for duplicate current_stocks entries...');

        // Find products with multiple current_stock entries (regardless of warehouse)
        $duplicates = DB::table('current_stocks')
            ->select('product_id', DB::raw('COUNT(*) as count'))
            ->groupBy('product_id')
            ->having(DB::raw('COUNT(*)'), '>', 1)
            ->get();

        if ($duplicates->isEmpty()) {
            $this->info('No duplicates found!');
            return 0;
        }

        $this->warn("Found {$duplicates->count()} products with duplicate stock entries.");

        $cleaned = 0;
        foreach ($duplicates as $dup) {
            $entries = CurrentStock::where('product_id', $dup->product_id)
                ->orderBy('updated_at', 'desc')
                ->get();

            $this->line("Product ID {$dup->product_id}: {$entries->count()} entries");
            
            // Keep the first entry (most recently updated), delete others
            $keep = $entries->first();
            $toDelete = $entries->slice(1);

            foreach ($toDelete as $entry) {
                $this->line("  - Deleting duplicate (ID: {$entry->id}, quantity: {$entry->quantity}, warehouse_id: {$entry->warehouse_id})");
                if (!$dryRun) {
                    $entry->delete();
                }
                $cleaned++;
            }
        }

        $mode = $dryRun ? '[DRY RUN] ' : '';
        $this->info("{$mode}Cleanup completed! Removed {$cleaned} duplicate entries.");

        if ($dryRun) {
            $this->warn('This was a dry run. No changes were made. Remove --dry-run to apply changes.');
        }

        return 0;
    }
}
