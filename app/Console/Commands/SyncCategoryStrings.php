<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\PublicProduct;
use Illuminate\Console\Command;

class SyncCategoryStrings extends Command
{
    protected $signature = 'sync:category-strings
                            {--model=all : Model to sync (all, product, public-product)}';

    protected $description = 'Sinkronkan kolom string category/sub_category dari pivot table ke semua produk';

    public function handle(): int
    {
        $model = $this->option('model');

        if (in_array($model, ['all', 'public-product'])) {
            $this->syncPublicProducts();
        }

        if (in_array($model, ['all', 'product'])) {
            $this->syncInternalProducts();
        }

        return self::SUCCESS;
    }

    protected function syncPublicProducts(): void
    {
        $this->info('Menyinkronkan public_products...');

        $count = 0;
        PublicProduct::with(['productCategories.category', 'productCategories.subCategory'])
            ->chunk(100, function ($products) use (&$count) {
                foreach ($products as $product) {
                    $first = $product->productCategories->first();

                    if ($first && $first->category) {
                        $newCategory    = $first->category->name ?? '';
                        $newSubCategory = $first->subCategory?->name ?? '';

                        if ($product->category !== $newCategory || $product->sub_category !== $newSubCategory) {
                            $product->category     = $newCategory;
                            $product->sub_category = $newSubCategory;
                            $product->saveQuietly();
                            $this->line("  Fixed: [{$product->id}] {$product->title} => {$newCategory} / {$newSubCategory}");
                            $count++;
                        }
                    }
                }
            });

        $this->info("  {$count} public products diperbarui.");
    }

    protected function syncInternalProducts(): void
    {
        $this->info('Menyinkronkan internal products...');

        $count = 0;
        Product::with(['productCategories.category', 'productCategories.subCategory'])
            ->chunk(100, function ($products) use (&$count) {
                foreach ($products as $product) {
                    $first = $product->productCategories->first();

                    if ($first && $first->category) {
                        $newCategory    = $first->category->name ?? '';
                        $newSubCategory = $first->subCategory?->name ?? '';

                        if ($product->category !== $newCategory || $product->sub_category !== $newSubCategory) {
                            $product->category     = $newCategory;
                            $product->sub_category = $newSubCategory;
                            $product->saveQuietly();
                            $this->line("  Fixed: [{$product->id}] {$product->title} => {$newCategory} / {$newSubCategory}");
                            $count++;
                        }
                    }
                }
            });

        $this->info("  {$count} internal products diperbarui.");
    }
}
