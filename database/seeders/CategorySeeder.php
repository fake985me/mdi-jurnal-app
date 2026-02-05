<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'XGSPON', 'description' => 'XGS-PON networking products'],
            ['name' => 'GPON', 'description' => 'GPON networking products'],
            ['name' => 'SWITCH', 'description' => 'Network switches'],
            ['name' => 'ROUTER', 'description' => 'Network routers'],
            ['name' => 'WIRELESS', 'description' => 'Wireless networking products'],
            ['name' => 'SFP', 'description' => 'Small Form Factor Pluggable'],
        ];

        foreach ($categories as $category) {
            DB::table('categories')->updateOrInsert(
                ['name' => $category['name']],
                $category
            );
        }

        $this->command->info('✅ Categories seeded successfully!');
    }
}
