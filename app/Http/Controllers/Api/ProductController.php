<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\SubCategory;
use App\Models\CurrentStock;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ProductsExport;
use App\Exports\ProductsTemplateExport;
use App\Imports\ProductsImport;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with(['productCategories.category', 'productCategories.subCategory']);

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('brand', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%")
                    ->orWhere('descriptions', 'like', "%{$search}%");
            });
        }

        // Filter by category (supports both legacy string and new pivot table)
        if ($request->has('category_id') && $request->category_id) {
            $query->whereHas('productCategories', function ($q) use ($request) {
                $q->where('category_id', $request->category_id);
            });
        } elseif ($request->has('category') && $request->category) {
            // Legacy support for string category
            $query->where('category', $request->category);
        }

        // Filter by sub_category (supports both legacy string and new pivot table)
        if ($request->has('sub_category_id') && $request->sub_category_id) {
            $query->whereHas('productCategories', function ($q) use ($request) {
                $q->where('sub_category_id', $request->sub_category_id);
            });
        } elseif ($request->has('sub_category') && $request->sub_category) {
            // Legacy support
            $query->where('sub_category', $request->sub_category);
        }

        // Filter by brand
        if ($request->has('brand') && $request->brand) {
            $query->where('brand', $request->brand);
        }

        // Filter by stock status
        if ($request->has('stock_status')) {
            switch ($request->stock_status) {
                case 'low':
                    $query->lowStock();
                    break;
                case 'out':
                    $query->outOfStock();
                    break;
                case 'in':
                    $query->inStock();
                    break;
            }
        }

        $products = $query->paginate($request->per_page ?? 15);

        // Append category_pairs to each product
        $products->getCollection()->transform(function ($product) {
            $product->category_pairs = $product->categoryPairs;
            return $product;
        });

        return response()->json($products);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'sku' => 'nullable|string|max:100',
            'title' => 'required|string|max:255',
            'category' => 'nullable|string|max:100',
            'sub_category' => 'nullable|string|max:100',
            'brand' => 'nullable|string|max:100',
            'image' => 'nullable|string',
            'descriptions' => 'nullable|string',
            'stock' => 'nullable|integer|min:0',
            'minimum_stock' => 'nullable|integer|min:0',
            // New categories array
            'categories' => 'nullable|array',
            'categories.*.category_id' => 'required_with:categories|exists:categories,id',
            'categories.*.sub_category_id' => 'nullable|exists:sub_categories,id',
        ]);

        // Remove categories from validated data before creating product
        $categoriesData = $validated['categories'] ?? [];
        unset($validated['categories']);

        $product = Product::create($validated);

        // Sync categories if provided
        if (!empty($categoriesData)) {
            $product->syncCategories($categoriesData);
        }

        // Sync stock to default warehouse
        $this->syncStockToDefaultWarehouse($product);

        // Load relationships for response
        $product->load(['productCategories.category', 'productCategories.subCategory']);
        $product->category_pairs = $product->categoryPairs;

        return response()->json($product, 201);
    }

    public function show($id)
    {
        $product = Product::with(['productCategories.category', 'productCategories.subCategory'])
            ->findOrFail($id);
        
        $product->category_pairs = $product->categoryPairs;

        return response()->json($product);
    }

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'sku' => 'nullable|string|max:100',
            'title' => 'required|string|max:255',
            'category' => 'nullable|string|max:100',
            'sub_category' => 'nullable|string|max:100',
            'brand' => 'nullable|string|max:100',
            'image' => 'nullable|string',
            'descriptions' => 'nullable|string',
            'stock' => 'nullable|integer|min:0',
            'minimum_stock' => 'nullable|integer|min:0',
            // New categories array
            'categories' => 'nullable|array',
            'categories.*.category_id' => 'required_with:categories|exists:categories,id',
            'categories.*.sub_category_id' => 'nullable|exists:sub_categories,id',
        ]);

        // Remove categories from validated data before updating product
        $categoriesData = $validated['categories'] ?? null;
        unset($validated['categories']);

        $product->update($validated);

        // Sync categories if provided
        if ($categoriesData !== null) {
            $product->syncCategories($categoriesData);
        }

        // Sync stock to default warehouse
        $this->syncStockToDefaultWarehouse($product);

        // Load relationships for response
        $product->load(['productCategories.category', 'productCategories.subCategory']);
        $product->category_pairs = $product->categoryPairs;

        return response()->json($product);
    }

    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        $product->delete();

        return response()->json(['message' => 'Product deleted successfully']);
    }

    public function uploadImages(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $request->validate([
            'images.*' => 'required|image|max:2048',
        ]);

        $images = [];
        foreach ($request->file('images') as $image) {
            $path = $image->store('products', 'public');
            $productImage = $product->images()->create([
                'image_path' => $path,
            ]);
            $images[] = $productImage;
        }

        return response()->json($images);
    }

    public function filterOptions()
    {
        // Get categories from Category model
        $categoriesList = Category::with('subCategories')
            ->orderBy('name')
            ->get()
            ->map(function ($cat) {
                return [
                    'id' => $cat->id,
                    'name' => $cat->name,
                    'sub_categories' => $cat->subCategories->map(function ($sub) {
                        return [
                            'id' => $sub->id,
                            'name' => $sub->name,
                        ];
                    }),
                ];
            });

        // Legacy: also get unique categories from products table
        $legacyCategories = Product::whereNotNull('category')
            ->distinct()
            ->pluck('category')
            ->filter()
            ->sort()
            ->values();

        $brands = Product::whereNotNull('brand')
            ->distinct()
            ->pluck('brand')
            ->filter()
            ->sort()
            ->values();

        return response()->json([
            'categories' => $categoriesList,
            'legacy_categories' => $legacyCategories,
            'brands' => $brands,
        ]);
    }

    /**
     * Export all products to Excel
     */
    public function exportProducts()
    {
        return Excel::download(new ProductsExport, 'products_' . date('Y-m-d') . '.xlsx');
    }

    /**
     * Export empty Excel template
     */
    public function exportTemplate()
    {
        return Excel::download(new ProductsTemplateExport, 'products_template.xlsx');
    }

    /**
     * Import products from Excel
     */
    public function importProducts(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240', // Max 10MB
        ]);

        try {
            $file = $request->file('file');

            $import = new ProductsImport();
            Excel::import($import, $file);

            $failures = $import->failures();
            $failureCount = count($failures);
            $successCount = Product::count(); // This is approximate

            if ($failureCount > 0) {
                $errors = [];
                foreach ($failures as $failure) {
                    $errors[] = [
                        'row' => $failure->row(),
                        'attribute' => $failure->attribute(),
                        'errors' => $failure->errors(),
                        'values' => $failure->values(),
                    ];
                }

                return response()->json([
                    'success' => true,
                    'message' => "Import completed with {$failureCount} errors",
                    'failures' => $errors,
                    'failure_count' => $failureCount,
                ], 200);
            }

            return response()->json([
                'success' => true,
                'message' => 'Products imported successfully!',
                'failure_count' => 0,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Sync product stock to default warehouse current_stocks
     */
    protected function syncStockToDefaultWarehouse(Product $product)
    {
        $defaultWarehouse = Warehouse::getDefault();
        
        if (!$defaultWarehouse) {
            return;
        }

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
    }
}
