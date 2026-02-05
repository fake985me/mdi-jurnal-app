<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProductsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding products from data file...');
        
        // Load products data
        $productsFile = database_path('seeders/data/products.php');
        
        if (!file_exists($productsFile)) {
            $this->command->error('Products data file not found!');
            return;
        }
        
        $products = include $productsFile;
        
        if (empty($products)) {
            $this->command->warn('No products found in data file!');
            return;
        }

        // Get all categories and subcategories for mapping
        $categories = DB::table('categories')->pluck('id', 'name')->toArray();
        $subCategories = DB::table('sub_categories')
            ->select('id', 'name', 'category_id')
            ->get()
            ->groupBy('category_id');
        
        $totalInserted = 0;
        $totalUpdated = 0;
        $categoryLinksCount = 0;
        
        foreach ($products as $product) {
            $now = Carbon::now();
            
            // Get categories array (new format) or convert from single value (old format)
            $categoryNames = $product['categories'] ?? [$product['category'] ?? null];
            $subCategoryNames = $product['sub_categories'] ?? [$product['sub_category'] ?? null];
            
            // Filter out null/empty values
            $categoryNames = array_filter($categoryNames);
            $subCategoryNames = array_values($subCategoryNames); // Re-index
            
            // Primary category for backward compatibility
            $primaryCategory = $categoryNames[0] ?? null;
            $primarySubCategory = $subCategoryNames[0] ?? null;
            
            // Product data
            $productData = [
                'sku' => $product['sku'],
                'title' => $product['title'],
                'brand' => $product['brand'],
                'category' => $primaryCategory,       // Store primary for backward compatibility
                'sub_category' => $primarySubCategory, // Store primary for backward compatibility
                'price' => $product['price'] ?? 0,
                'stock' => $product['stock'] ?? 0,
                'minimum_stock' => $product['minimum_stock'] ?? 0,
                'is_asset' => $product['is_asset'] ?? false,
                'updated_at' => $now,
            ];
            
            $existingProduct = DB::table('products')->where('sku', $product['sku'])->first();
            
            if ($existingProduct) {
                DB::table('products')->where('id', $existingProduct->id)->update($productData);
                $productId = $existingProduct->id;
                $totalUpdated++;
            } else {
                $productData['created_at'] = $now;
                $productId = DB::table('products')->insertGetId($productData);
                $totalInserted++;
            }
            
            // Clear existing category links for this product
            DB::table('product_category')->where('product_id', $productId)->delete();
            
            // Create multi-category links
            $maxCount = max(count($categoryNames), count($subCategoryNames));
            
            for ($i = 0; $i < $maxCount; $i++) {
                $catName = $categoryNames[$i] ?? null;
                $subCatName = $subCategoryNames[$i] ?? null;
                
                if ($catName && isset($categories[$catName])) {
                    $categoryId = $categories[$catName];
                    $subCategoryId = null;
                    
                    // Find subcategory ID
                    if ($subCatName && isset($subCategories[$categoryId])) {
                        $subCat = $subCategories[$categoryId]->firstWhere('name', $subCatName);
                        $subCategoryId = $subCat ? $subCat->id : null;
                    }
                    
                    DB::table('product_category')->insert([
                        'product_id' => $productId,
                        'category_id' => $categoryId,
                        'sub_category_id' => $subCategoryId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                    $categoryLinksCount++;
                }
            }
        }
        
        $this->command->info("✓ Products seeded: {$totalInserted} new, {$totalUpdated} updated");
        $this->command->info("✓ Category links created: {$categoryLinksCount}");
    }
}
