<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BrandSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $brands = [
            ['name' => 'dasan'],
            ['name' => 'zaram'],
            ['name' => 'zyxel'],
            ['name' => 'tp-link'],
            ['name' => 'mikrotik'],
        ];

        foreach ($brands as $brand) {
            DB::table('brands')->updateOrInsert(
                ['name' => $brand['name']],
                $brand
            );
        }

        $this->command->info('✅ Brands seeded successfully!');
    }
}
