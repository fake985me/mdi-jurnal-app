<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\Category;
use App\Models\SubCategory;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterImport;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class ProductsImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure, WithBatchInserts, WithChunkReading, WithEvents
{
    use SkipsFailures;

    /**
     * Cache for categories and subcategories
     */
    protected $categories = [];
    protected $subCategories = [];
    protected $importedProducts = [];

    public function __construct()
    {
        // Pre-load categories and subcategories for lookup
        $this->categories = Category::pluck('id', 'name')->toArray();
        $this->subCategories = SubCategory::with('category')->get()->groupBy('category_id');
    }

    /**
     * @param array $row
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        // Check if product exists by SKU
        $product = null;
        if (!empty($row['sku'])) {
            $product = Product::where('sku', $row['sku'])->first();
        }

        // Generate slug from title if not exists
        $slug = !empty($row['title']) ? Str::slug($row['title']) : null;

        // Parse multi-category (pipe-separated)
        $categoryNames = $this->parseMultiValue($row['category'] ?? '');
        $subCategoryNames = $this->parseMultiValue($row['sub_category'] ?? '');
        
        // Store first category/subcategory for backward compatibility in single columns
        $primaryCategory = $categoryNames[0] ?? null;
        $primarySubCategory = $subCategoryNames[0] ?? null;

        $data = [
            'sku' => $row['sku'] ?? null,
            'title' => $row['title'] ?? null,
            'subtitle' => $row['subtitle'] ?? null,
            'category' => $primaryCategory,       // Keep for backward compatibility
            'sub_category' => $primarySubCategory, // Keep for backward compatibility
            'brand' => $row['brand'] ?? null,
            'module' => $row['module'] ?? 'A',
            'slug' => $slug,
            'descriptions' => $row['descriptions'] ?? null,
            // Specs
            'spec1' => $row['spec1'] ?? null,
            'spec2' => $row['spec2'] ?? null,
            'spec3' => $row['spec3'] ?? null,
            'spec4' => $row['spec4'] ?? null,
            'spec5' => $row['spec5'] ?? null,
            'spec6' => $row['spec6'] ?? null,
            'spec7' => $row['spec7'] ?? null,
            // Features
            'fitur1' => $row['fitur1'] ?? null,
            'fitur2' => $row['fitur2'] ?? null,
            'fitur3' => $row['fitur3'] ?? null,
            'fitur4' => $row['fitur4'] ?? null,
            'fitur5' => $row['fitur5'] ?? null,
            'fitur6' => $row['fitur6'] ?? null,
            'fitur7' => $row['fitur7'] ?? null,
            'fitur8' => $row['fitur8'] ?? null,
            'fitur9' => $row['fitur9'] ?? null,
            'fitur10' => $row['fitur10'] ?? null,
            'fitur11' => $row['fitur11'] ?? null,
            'fitur12' => $row['fitur12'] ?? null,
            'fitur13' => $row['fitur13'] ?? null,
            'fitur14' => $row['fitur14'] ?? null,
            'fitur15' => $row['fitur15'] ?? null,
            // Technical Specs
            'flash_memory' => $row['flash_memory'] ?? null,
            'sdram_memory' => $row['sdram_memory'] ?? null,
            'interface_main' => $row['interface_main'] ?? null,
            'interface1' => $row['interface1'] ?? null,
            'interface2' => $row['interface2'] ?? null,
            'interface3' => $row['interface3'] ?? null,
            'interface4' => $row['interface4'] ?? null,
            'interface5' => $row['interface5'] ?? null,
            'operating_temperature' => $row['operating_temperature'] ?? null,
            'storage_temperature' => $row['storage_temperature'] ?? null,
            'operating_humidity' => $row['operating_humidity'] ?? null,
            'power1' => $row['power1'] ?? null,
            'power2' => $row['power2'] ?? null,
            'power3' => $row['power3'] ?? null,
            'power_consumptions' => $row['power_consumptions'] ?? null,
            'dimensions' => $row['dimensions'] ?? null,
            'diagram' => $row['diagram'] ?? null,
            'network_diagram' => $row['network_diagram'] ?? null,
        ];

        if ($product) {
            // Update existing product
            $product->update($data);
        } else {
            // Create new product
            $product = Product::create($data);
        }

        // Store for category syncing after import
        if ($product) {
            $this->importedProducts[] = [
                'product' => $product,
                'categories' => $categoryNames,
                'sub_categories' => $subCategoryNames,
            ];
        }

        return $product;
    }

    /**
     * Parse pipe-separated values into array
     * @param string $value
     * @return array
     */
    protected function parseMultiValue($value): array
    {
        if (empty($value)) {
            return [];
        }
        
        // Split by pipe and trim whitespace
        return array_map('trim', explode('|', $value));
    }

    /**
     * Sync categories after all products are imported
     */
    public function registerEvents(): array
    {
        return [
            AfterImport::class => function(AfterImport $event) {
                $this->syncAllCategories();
            },
        ];
    }

    /**
     * Sync all imported products with their categories
     */
    protected function syncAllCategories()
    {
        foreach ($this->importedProducts as $item) {
            $product = $item['product'];
            $categoryNames = $item['categories'];
            $subCategoryNames = $item['sub_categories'];

            $categoryData = [];
            
            // Match categories with subcategories by index
            $maxCount = max(count($categoryNames), count($subCategoryNames));
            
            for ($i = 0; $i < $maxCount; $i++) {
                $catName = $categoryNames[$i] ?? null;
                $subCatName = $subCategoryNames[$i] ?? null;
                
                if ($catName && isset($this->categories[$catName])) {
                    $categoryId = $this->categories[$catName];
                    $subCategoryId = null;
                    
                    // Find subcategory ID
                    if ($subCatName && isset($this->subCategories[$categoryId])) {
                        $subCat = $this->subCategories[$categoryId]->firstWhere('name', $subCatName);
                        $subCategoryId = $subCat ? $subCat->id : null;
                    }
                    
                    $categoryData[] = [
                        'category_id' => $categoryId,
                        'sub_category_id' => $subCategoryId,
                    ];
                }
            }
            
            // Sync categories using the model method
            if (!empty($categoryData)) {
                $product->syncCategories($categoryData);
            }
        }
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'sku' => 'nullable|string|max:100',
            'category' => 'nullable|string|max:255',      // Allow longer for multi-category
            'sub_category' => 'nullable|string|max:255',  // Allow longer for multi-subcategory
            'brand' => 'nullable|string|max:100',
        ];
    }

    /**
     * @return array
     */
    public function customValidationMessages()
    {
        return [
            'title.required' => 'Product title is required',
        ];
    }

    /**
     * @return int
     */
    public function batchSize(): int
    {
        return 100;
    }

    /**
     * @return int
     */
    public function chunkSize(): int
    {
        return 100;
    }
}
