<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SubCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get category IDs
        $xgspon = DB::table('categories')->where('name', 'XGSPON')->first();
        $gpon = DB::table('categories')->where('name', 'GPON')->first();
        $switch = DB::table('categories')->where('name', 'SWITCH')->first();
        $router = DB::table('categories')->where('name', 'ROUTER')->first();
        $wireless = DB::table('categories')->where('name', 'WIRELESS')->first();
        $sfp = DB::table('categories')->where('name', 'SFP')->first();

        // Check if categories exist
        if (!$xgspon || !$gpon || !$switch) {
            $this->command->error('Categories must be seeded first! Run CategorySeeder.');
            return;
        }

        $subCategories = [];
        
        // XGSPON subcategories
        if ($xgspon) {
            $subCategories = array_merge($subCategories, [
                ['category_id' => $xgspon->id, 'name' => 'OLT', 'description' => 'Optical Line Terminal'],
                ['category_id' => $xgspon->id, 'name' => 'ONT', 'description' => 'Optical Network Terminal'],
                ['category_id' => $xgspon->id, 'name' => 'ONU', 'description' => 'Optical Network Unit'],
                ['category_id' => $xgspon->id, 'name' => 'ONU PoE', 'description' => 'Optical Network Unit with PoE'],
                ['category_id' => $xgspon->id, 'name' => 'XGSPON STICK', 'description' => 'XGS-PON SFP Module'],
            ]);
        }
        
        // GPON subcategories
        if ($gpon) {
            $subCategories = array_merge($subCategories, [
                ['category_id' => $gpon->id, 'name' => 'OLT', 'description' => 'Optical Line Terminal'],
                ['category_id' => $gpon->id, 'name' => 'ONT', 'description' => 'Optical Network Terminal'],
                ['category_id' => $gpon->id, 'name' => 'ONU', 'description' => 'Optical Network Unit'],
                ['category_id' => $gpon->id, 'name' => 'ONU PoE', 'description' => 'Optical Network Unit with PoE'],
                ['category_id' => $gpon->id, 'name' => 'GPON STICK', 'description' => 'GPON SFP Module'],
            ]);
        }
        
        // SWITCH subcategories
        if ($switch) {
            $subCategories = array_merge($subCategories, [
                ['category_id' => $switch->id, 'name' => 'BACKBONE', 'description' => 'Backbone Switch'],
                ['category_id' => $switch->id, 'name' => 'Backbone Switch', 'description' => 'Backbone Switch'],
                ['category_id' => $switch->id, 'name' => 'L2 Switch', 'description' => 'Layer 2 Switch'],
                ['category_id' => $switch->id, 'name' => 'L2 SWITCH', 'description' => 'Layer 2 Switch'],
                ['category_id' => $switch->id, 'name' => 'L2 POE Switch', 'description' => 'Layer 2 Switch with PoE'],
                ['category_id' => $switch->id, 'name' => 'L3 Switch', 'description' => 'Layer 3 Switch'],
                ['category_id' => $switch->id, 'name' => 'L3 SWITCH', 'description' => 'Layer 3 Switch'],
                ['category_id' => $switch->id, 'name' => 'PoE SWITCH', 'description' => 'Power over Ethernet Switch'],
            ]);
        }
        
        // ROUTER subcategories
        if ($router) {
            $subCategories = array_merge($subCategories, [
                ['category_id' => $router->id, 'name' => 'ENTERPRISE ROUTER', 'description' => 'Enterprise Router'],
            ]);
        }
        
        // WIRELESS subcategories
        if ($wireless) {
            $subCategories = array_merge($subCategories, [
                ['category_id' => $wireless->id, 'name' => 'INDOOR AP', 'description' => 'Indoor Access Point'],
                ['category_id' => $wireless->id, 'name' => 'OUTDOOR AP', 'description' => 'Outdoor Access Point'],
                ['category_id' => $wireless->id, 'name' => 'CONTROLLER', 'description' => 'Wireless Controller'],
                ['category_id' => $wireless->id, 'name' => 'Access Point', 'description' => 'Wireless Access Point'],
            ]);
        }
        
        // SFP subcategories
        if ($sfp) {
            $subCategories = array_merge($subCategories, [
                ['category_id' => $sfp->id, 'name' => 'SFP', 'description' => 'Small Form-factor Pluggable'],
                ['category_id' => $sfp->id, 'name' => 'SFP+', 'description' => 'Enhanced Small Form-factor Pluggable'],
                ['category_id' => $sfp->id, 'name' => 'XFP', 'description' => '10 Gigabit Small Form Factor Pluggable'],
            ]);
        }

        foreach ($subCategories as $subCategory) {
            // Use updateOrCreate to avoid duplicates
            DB::table('sub_categories')->updateOrInsert(
                ['category_id' => $subCategory['category_id'], 'name' => $subCategory['name']],
                $subCategory
            );
        }

        $this->command->info('✅ Sub-categories seeded successfully!');
    }
}
