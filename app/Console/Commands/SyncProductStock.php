<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\StockSyncService;

class SyncProductStock extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stock:sync {--product= : Specific product ID to sync}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync product stock to current_stocks table';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting stock synchronization...');
        
        $stockService = new StockSyncService();
        $productId = $this->option('product');
        
        $synced = $stockService->syncProductStock($productId);

        $this->newLine();
        $this->info("✅ Stock synchronization completed!");
        $this->info("Synced: {$synced} " . ($synced === 1 ? 'product' : 'products'));

        return Command::SUCCESS;
    }
}
