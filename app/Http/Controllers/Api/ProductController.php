<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\PublicProduct;
use App\Models\PublicProductCategory;
use App\Models\SaleItem;
use App\Models\PurchaseItem;
use App\Models\Category;
use App\Models\SubCategory;
use App\Models\CurrentStock;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ProductsExport;
use App\Exports\ProductsTemplateExport;
use App\Imports\ProductsImport;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with(['productCategories.category', 'productCategories.subCategory'])
            ->withSum(['currentStocks' => fn ($q) => $q
                ->whereNotNull('warehouse_id')
                // Gudang project (hasil alokasi project invest) bukan stok tersedia
                ->whereNotIn('warehouse_id', DB::table('project_investments')
                    ->whereNotNull('warehouse_id')
                    ->select('warehouse_id'))
            ], 'quantity');

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

        // Append category_pairs and set stock to total across all warehouses or product table stock
        /** @var \Illuminate\Pagination\LengthAwarePaginator $products */
        $products->through(function ($product) {
            $product->append('category_pairs');
            // Use warehouse stock sum (even if 0 - e.g. all allocated to project).
            // Only fall back to product.stock if there are no current_stock rows at all (null).
            $warehouseStock = $product->current_stocks_sum_quantity !== null
                ? (int) $product->current_stocks_sum_quantity
                : null;
            $product->stock = $warehouseStock ?? (int) ($product->stock ?? 0);
            return $product;
        });

        return response()->json($products);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'sku' => 'nullable|string|max:100',
            'category' => 'nullable|string|max:255',
            'sub_category' => 'nullable|string|max:255',
            'brand' => 'nullable|string|max:100',
            'image' => 'nullable|string',
            'descriptions' => 'nullable|string',
            'price' => 'nullable|numeric|min:0',
            'stock' => 'nullable|integer|min:0',
            'minimum_stock' => 'nullable|integer|min:0',
            'warranty_period_months' => 'nullable|integer|min:0',
            'is_asset' => 'nullable|boolean',
            // Categories array
            'categories' => 'nullable|array',
            'categories.*.category_id' => 'required_with:categories|exists:categories,id',
            'categories.*.sub_category_id' => 'nullable|exists:sub_categories,id',
        ]);

        $categoriesData = $request->input('categories', []);
        
        // Grab all fillable product attributes from request
        $fillable = (new Product)->getFillable();
        $productData = $request->only($fillable);
        if (!isset($productData['stock'])) {
            $productData['stock'] = 0;
        }
        if (!isset($productData['warranty_period_months'])) {
            $productData['warranty_period_months'] = 12;
        }

        $product = Product::create($productData);

        // Sync categories if provided
        if (!empty($categoriesData)) {
            $product->syncCategories($categoriesData);
        }

        // Sync stock to default warehouse
        $this->syncStockToDefaultWarehouse($product);

        // Auto-sync to public product catalog
        $this->syncToPublic($product);

        // Load relationships for response
        $product->load(['productCategories.category', 'productCategories.subCategory']);
        $product->append('category_pairs');

        return response()->json($product, 201);
    }

    public function show($id)
    {
        $product = Product::with(['productCategories.category', 'productCategories.subCategory'])
            ->findOrFail($id);
        
        $product->append('category_pairs');

        return response()->json($product);
    }

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $request->validate([
            'title' => 'required|string|max:255',
            'sku' => 'nullable|string|max:100',
            'warranty_period_months' => 'nullable|integer|min:0',
            'categories' => 'nullable|array',
            'categories.*.category_id' => 'required_with:categories|exists:categories,id',
            'categories.*.sub_category_id' => 'nullable|exists:sub_categories,id',
        ]);

        $categoriesData = $request->input('categories');

        // Grab all fillable product attributes from request
        $fillable = (new Product)->getFillable();
        $productData = $request->only($fillable);

        $product->update($productData);

        // Sync categories if provided
        if ($categoriesData !== null) {
            $product->syncCategories($categoriesData);
        }

        // Sync stock to default warehouse
        $this->syncStockToDefaultWarehouse($product);

        // Auto-sync to public product catalog
        $this->syncToPublic($product);

        // Load relationships for response
        $product->load(['productCategories.category', 'productCategories.subCategory']);
        $product->append('category_pairs');

        return response()->json($product);
    }

    public function destroy($id)
    {
        $product = Product::findOrFail($id);

        // Cek apakah product masih digunakan di transaksi lain
        $usedInSales = \App\Models\SaleItem::where('product_id', $id)->exists();
        $usedInPurchases = \App\Models\PurchaseItem::where('product_id', $id)->exists();

        if ($usedInSales || $usedInPurchases) {
            return response()->json([
                'message' => 'Produk tidak dapat dihapus karena masih terhubung ke data transaksi (penjualan atau pembelian). Nonaktifkan produk jika tidak ingin digunakan lagi.',
            ], 422);
        }

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

        $brands = Product::whereNotNull('brand')
            ->distinct()
            ->pluck('brand')
            ->filter()
            ->sort()
            ->values();

        return response()->json([
            'categories' => $categoriesList,
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

            // Hitung produk yang berhasil diimport berdasarkan $import->getImportedRowCount()
            // atau fallback ke estimate dari failures
            $importedCount = method_exists($import, 'getImportedRowCount')
                ? $import->getImportedRowCount()
                : 'unknown';

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
        } catch (\Throwable $e) {
            Log::error('Product Excel import failed.', [
                'exception' => $e,
            ]);

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

    /**
     * Sync satu produk internal ke produk publik (website catalog).
     *
     * Aturan sync:
     * - Jika belum ada di public_products → buat baru
     * - Jika sudah ada → update hanya field dasar (tidak menimpa subtitle/spec/fitur)
     * - Kategori dari product_category → disync ke public_product_category
     */
    /**
     * Get all attributes to sync from Product to PublicProduct
     */
    protected function getSyncableFields(Product $product): array
    {
        $fields = [
            'sku'                             => $product->sku,
            'module'                          => $product->module ?? 'A',
            'image'                           => $product->image,
            'category'                        => $product->category,
            'sub_category'                    => $product->sub_category,
            'brand'                           => $product->brand,
            'stock'                           => $product->stock ?? 0,
            'title'                           => $product->title,
            'subtitle'                        => $product->subtitle,
            'descriptions'                    => $product->descriptions,
            'wireless_standard'               => $product->wireless_standard,
            'wireless_stream'                 => $product->wireless_stream,
            'wireless_stream1'                => $product->wireless_stream1,
            'wireless_stream2'                => $product->wireless_stream2,
            'wireless_stream3'                => $product->wireless_stream3,
            'wireless_stream4'                => $product->wireless_stream4,
            'wireless_stream5'                => $product->wireless_stream5,
            'manageable_aps'                  => $product->manageable_aps,
            'ap_number'                       => $product->ap_number,
            'number_of_clients'               => $product->number_of_clients,
            'capacity'                        => $product->capacity,
            'ip_rating'                       => $product->ip_rating,
            'switching'                       => $product->switching,
            'throughput'                      => $product->throughput,
            'flash_memory'                    => $product->flash_memory,
            'sdram_memory'                    => $product->sdram_memory,
            'interface_main'                  => $product->interface_main,
            'interface1'                      => $product->interface1,
            'interface2'                      => $product->interface2,
            'interface3'                      => $product->interface3,
            'interface4'                      => $product->interface4,
            'interface5'                      => $product->interface5,
            'antena'                          => $product->antena,
            'cu'                              => $product->cu,
            'cu1'                             => $product->cu1,
            'cu2'                             => $product->cu2,
            'cu3'                             => $product->cu3,
            'cu4'                             => $product->cu4,
            'cu5'                             => $product->cu5,
            'additional_interface1'           => $product->additional_interface1,
            'additional_interface2'           => $product->additional_interface2,
            'additional_interface3'           => $product->additional_interface3,
            'additional_interface4'           => $product->additional_interface4,
            'additional_interface5'           => $product->additional_interface5,
            'mac_address'                     => $product->mac_address,
            'routing_table'                   => $product->routing_table,
            'dustproof_waterproof'            => $product->dustproof_waterproof,
            'noise'                           => $product->noise,
            'mtbf'                            => $product->mtbf,
            'operating_temperature'           => $product->operating_temperature,
            'storage_temperature'             => $product->storage_temperature,
            'operating_humidity'              => $product->operating_humidity,
            'power1'                          => $product->power1,
            'power2'                          => $product->power2,
            'power3'                          => $product->power3,
            'power4'                          => $product->power4,
            'power5'                          => $product->power5,
            'power_consumptions'              => $product->power_consumptions,
            'power_consumption_with_poe'      => $product->power_consumption_with_poe,
            'power_consumption_excluding_poe' => $product->power_consumption_excluding_poe,
            'poe_budget'                      => $product->poe_budget,
            'dimensions'                      => $product->dimensions,
            'diagram'                         => $product->diagram,
            'network_diagram'                 => $product->network_diagram,
        ];

        for ($i = 1; $i <= 20; $i++) {
            $fields["spec{$i}"] = $product->{"spec{$i}"};
            $fields["fitur{$i}"] = $product->{"fitur{$i}"};
        }

        return $fields;
    }

    /**
     * Sync satu produk internal ke produk publik (website catalog).
     */
    public function syncToPublic(Product $product)
    {
        DB::beginTransaction();
        try {
            $isNew = false;
            $publicProduct = null;

            if ($product->public_product_id) {
                $publicProduct = PublicProduct::find($product->public_product_id);
            }

            if (!$publicProduct && $product->sku) {
                $publicProduct = PublicProduct::where('sku', $product->sku)->first();
            }

            if (!$publicProduct && $product->title) {
                $publicProduct = PublicProduct::where('title', $product->title)->first();
            }

            $syncFields = $this->getSyncableFields($product);

            if ($publicProduct) {
                $publicProduct->update($syncFields);
            } else {
                $isNew = true;
                $slug = Str::slug($product->title) ?: 'product-' . $product->id;
                $baseSlug = $slug;
                $counter = 1;
                while (PublicProduct::where('slug', $slug)->exists()) {
                    $slug = $baseSlug . '-' . $counter++;
                }

                $publicProduct = PublicProduct::create(array_merge($syncFields, [
                    'slug'       => $slug,
                    'is_active'  => true,
                    'sort_order' => 0,
                ]));
            }

            // Simpan link FK ke produk internal
            $product->update(['public_product_id' => $publicProduct->id]);

            // Sync kategori dari product_category → public_product_category
            $this->syncCategoriesToPublic($product, $publicProduct);

            DB::commit();

            return response()->json([
                'success'        => true,
                'is_new'         => $isNew,
                'message'        => $isNew
                    ? 'Produk berhasil dipublikasikan ke website catalog.'
                    : 'Data produk publik berhasil diperbarui.',
                'public_product' => $publicProduct->fresh(),
                'product'        => $product->fresh(['publicProduct']),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal sync produk: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk sync beberapa produk internal ke produk publik.
     */
    public function bulkSyncToPublic(Request $request)
    {
        $validated = $request->validate([
            'product_ids'   => 'required|array|min:1',
            'product_ids.*' => 'required|integer|exists:products,id',
        ]);

        $results = [
            'success' => [],
            'failed'  => [],
        ];

        foreach ($validated['product_ids'] as $productId) {
            try {
                $product = Product::findOrFail($productId);
                $response = $this->syncToPublic($product);
                $data = $response->getData(true);

                $results['success'][] = [
                    'product_id'    => $productId,
                    'product_title' => $product->title,
                    'is_new'        => $data['is_new'] ?? false,
                ];
            } catch (\Exception $e) {
                $results['failed'][] = [
                    'product_id' => $productId,
                    'error'      => $e->getMessage(),
                ];
            }
        }

        $successCount = count($results['success']);
        $failedCount  = count($results['failed']);

        return response()->json([
            'success'       => $failedCount === 0,
            'message'       => "{$successCount} produk berhasil disync" . ($failedCount > 0 ? ", {$failedCount} gagal." : '.'),
            'results'       => $results,
            'success_count' => $successCount,
            'failed_count'  => $failedCount,
        ]);
    }

    /**
     * Sync semua produk internal ke publik
     */
    public function syncAllToPublic()
    {
        $products = Product::all();
        $synced = 0;
        $failed = 0;

        foreach ($products as $product) {
            try {
                $this->syncToPublic($product);
                $synced++;
            } catch (\Exception $e) {
                $failed++;
            }
        }

        return response()->json([
            'success'      => $failed === 0,
            'message'      => "{$synced} produk berhasil disinkronkan ke katalog publik" . ($failed > 0 ? ", {$failed} gagal." : '.'),
            'synced_count' => $synced,
            'failed_count' => $failed,
        ]);
    }

    /**
     * Sync kategori produk internal ke produk publik.
     * Builds category/sub_category strings from ALL pivot records using | delimiter.
     */
    protected function syncCategoriesToPublic(Product $product, PublicProduct $publicProduct): void
    {
        $internalCategories = $product->productCategories()->with(['category', 'subCategory'])->get();

        PublicProductCategory::where('public_product_id', $publicProduct->id)->delete();

        $catNames = [];
        $subNames = [];

        foreach ($internalCategories as $pc) {
            if (!$pc->category_id) continue;

            PublicProductCategory::create([
                'public_product_id' => $publicProduct->id,
                'category_id'       => $pc->category_id,
                'sub_category_id'   => $pc->sub_category_id,
            ]);

            if ($pc->category?->name && !in_array($pc->category->name, $catNames)) {
                $catNames[] = $pc->category->name;
            }
            if ($pc->subCategory?->name && !in_array($pc->subCategory->name, $subNames)) {
                $subNames[] = $pc->subCategory->name;
            }
        }

        // Sync flat string columns from ALL pivot records (joined with | for multi-category)
        if (!empty($catNames)) {
            $publicProduct->category     = implode(' | ', $catNames);
            $publicProduct->sub_category = implode(' | ', $subNames);
            $publicProduct->saveQuietly();

            // Also update the internal product's string columns to stay consistent
            $product->category     = implode(' | ', $catNames);
            $product->sub_category = implode(' | ', $subNames);
            $product->saveQuietly();
        }
    }
}
