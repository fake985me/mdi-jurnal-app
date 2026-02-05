<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Product;

class SyncProductCategories extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:product-categories {--force : Force sync even if pivot entries exist}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync existing products to product_category pivot table based on category/sub_category string fields';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting product category sync...');
        
        // Get all categories and subcategories for mapping
        $categories = DB::table('categories')->pluck('id', 'name')->toArray();
        $subCategories = DB::table('sub_categories')
            ->select('id', 'name', 'category_id')
            ->get()
            ->groupBy('category_id');
        
        if (empty($categories)) {
            $this->error('No categories found! Please run CategorySeeder first.');
            return 1;
        }
        
        $this->info('Found ' . count($categories) . ' categories');
        
        // Get all products with their category/sub_category strings
        $products = DB::table('products')
            ->select('id', 'category', 'sub_category')
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->get();
        
        $this->info('Found ' . $products->count() . ' products with categories');
        
        $created = 0;
        $skipped = 0;
        $notFound = 0;
        
        $this->withProgressBar($products, function ($product) use ($categories, $subCategories, &$created, &$skipped, &$notFound) {
            // Find category ID by name
            $categoryId = $categories[$product->category] ?? null;
            
            if (!$categoryId) {
                $notFound++;
                return;
            }
            
            // Find subcategory ID if exists
            $subCategoryId = null;
            if ($product->sub_category && isset($subCategories[$categoryId])) {
                $subCat = $subCategories[$categoryId]->firstWhere('name', $product->sub_category);
                $subCategoryId = $subCat ? $subCat->id : null;
            }
            
            // Check if pivot entry already exists
            $exists = DB::table('product_category')
                ->where('product_id', $product->id)
                ->where('category_id', $categoryId)
                ->where(function ($q) use ($subCategoryId) {
                    if ($subCategoryId) {
                        $q->where('sub_category_id', $subCategoryId);
                    } else {
                        $q->whereNull('sub_category_id');
                    }
                })
                ->exists();
            
            if ($exists && !$this->option('force')) {
                $skipped++;
                return;
            }
            
            // Delete existing entry if force mode
            if ($this->option('force')) {
                DB::table('product_category')
                    ->where('product_id', $product->id)
                    ->delete();
            }
            
            // Create pivot entry
            DB::table('product_category')->insert([
                'product_id' => $product->id,
                'category_id' => $categoryId,
                'sub_category_id' => $subCategoryId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            $created++;
        });
        
        $this->newLine(2);
        $this->info("✅ Sync completed!");
        $this->table(
            ['Status', 'Count'],
            [
                ['Created', $created],
                ['Skipped (exists)', $skipped],
                ['Category not found', $notFound],
            ]
        );
        
        // Show categories not found
        if ($notFound > 0) {
            $this->warn('Some products have categories that do not exist in the categories table:');
            $missingCategories = DB::table('products')
                ->select('category')
                ->whereNotNull('category')
                ->whereNotIn('category', array_keys($categories))
                ->distinct()
                ->pluck('category');
            foreach ($missingCategories as $cat) {
                $this->line("  - {$cat}");
            }
        }
        
        return 0;
    }
}
