<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PublicProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Load products from exported file
        $productsFile = base_path('database/seeders/data/public_products.php');

        if (!file_exists($productsFile)) {
            $this->command->error('Products data file not found. Run: php artisan export:public_products');
            return;
        }

        $products = include $productsFile;

        if (empty($products)) {
            $this->command->error('No public_products found in data file');
            return;
        }

        $this->command->info('Importing ' . count($products) . ' public products...');

        foreach ($products as $productData) {
            // Remove timestamps and id to let database handle them
            unset($productData['created_at'], $productData['updated_at'], $productData['id']);

            // Add default values for public products
            $productData['is_active'] = true;
            $productData['sort_order'] = 0;

            try {
                // Use updateOrCreate to avoid duplicates
                // Match by slug since SKU is empty for most products
                \App\Models\PublicProduct::updateOrCreate(
                    ['slug' => $productData['slug']], // Match by slug (unique identifier)
                    $productData
                );
            } catch (\Exception $e) {
                $this->command->warn('Failed to import product ' . ($productData['title'] ?? 'Unknown') . ': ' . $e->getMessage());
            }
        }

        $count = \App\Models\PublicProduct::count();
        $this->command->info("✅ Public products seeded successfully! Total products: {$count}");
    }
}
