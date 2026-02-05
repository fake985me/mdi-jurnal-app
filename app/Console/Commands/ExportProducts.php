<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ExportProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'export:products {--output=database/seeders/data/products.php}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export all products to PHP array format for seeder';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Exporting products...');

        $products = \App\Models\Product::all()->map(function ($product) {
            return $product->toArray();
        })->toArray();

        $this->info('Found ' . count($products) . ' products');

        // Generate PHP code
        $output = "<?php\n\nreturn " . var_export($products, true) . ";\n";

        // Ensure directory exists
        $outputPath = base_path($this->option('output'));
        $dir = dirname($outputPath);

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // Write to file
        file_put_contents($outputPath, $output);

        $this->info("✅ Products exported to: {$outputPath}");
        $this->info("   Total: " . count($products) . " products");

        return 0;
    }
}
