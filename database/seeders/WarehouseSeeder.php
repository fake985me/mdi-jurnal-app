<?php

namespace Database\Seeders;

use App\Models\Warehouse;
use App\Models\CurrentStock;
use App\Models\Product;
use Illuminate\Database\Seeder;

class WarehouseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding warehouses...');

        // Create default warehouse
        $defaultWarehouse = Warehouse::firstOrCreate(
            ['code' => 'WH-001'],
            [
                'name' => 'Gudang Utama',
                'address' => 'Jakarta, Indonesia',
                'phone' => '',
                'email' => '',
                'description' => 'Default main warehouse',
                'is_active' => true,
                'is_default' => true,
            ]
        );

        // Ensure it's set as default
        if (!$defaultWarehouse->is_default) {
            $defaultWarehouse->setAsDefault();
        }

        $this->command->info("✓ Default warehouse: {$defaultWarehouse->name} (ID: {$defaultWarehouse->id})");

        // Sync products stock to current_stocks for all products
        $products = Product::where('is_asset', false)->get();
        $synced = 0;

        foreach ($products as $product) {
            CurrentStock::updateOrCreate(
                [
                    'product_id' => $product->id,
                    'warehouse_id' => $defaultWarehouse->id,
                ],
                [
                    'quantity' => $product->stock ?? 0,
                    'last_updated' => now(),
                ]
            );
            $synced++;
        }

        $this->command->info("✓ Synced {$synced} product stocks to default warehouse");
    }
}
