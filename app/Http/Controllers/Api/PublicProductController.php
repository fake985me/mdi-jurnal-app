<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PublicProduct;
use App\Models\Category;
use App\Models\SubCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PublicProductController extends Controller
{
    /**
     * Display a listing of public products
     */
    public function index(Request $request)
    {
        $query = PublicProduct::with(['productCategories.category', 'productCategories.subCategory']);

        // Filter by category (supports both legacy string and new pivot table)
        if ($request->filled('category_id')) {
            $query->whereHas('productCategories', function ($q) use ($request) {
                $q->where('category_id', $request->category_id);
            });
        } elseif ($request->filled('category')) {
            // Legacy support for string category
            $query->where('category', $request->category);
        }

        // Filter by sub_category (supports both legacy string and new pivot table)
        if ($request->filled('sub_category_id')) {
            $query->whereHas('productCategories', function ($q) use ($request) {
                $q->where('sub_category_id', $request->sub_category_id);
            });
        } elseif ($request->filled('sub_category')) {
            // Legacy support
            $query->where('sub_category', $request->sub_category);
        }

        // Filter by brand
        if ($request->filled('brand')) {
            $query->where('brand', $request->brand);
        }

        // Filter by active status
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        // Search by title or description
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('subtitle', 'like', "%{$search}%")
                  ->orWhere('descriptions', 'like', "%{$search}%");
            });
        }

        // Order by sort_order and created_at
        $query->ordered();

        // Paginate results
        $perPage = $request->get('per_page', 15);
        $products = $query->paginate($perPage);

        // Append category_pairs to each product
        $products->getCollection()->transform(function ($product) {
            $product->category_pairs = $product->categoryPairs;
            return $product;
        });

        return response()->json($products);
    }

    /**
     * Store a newly created public product
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|unique:public_products,slug',
            'category' => 'nullable|string|max:100',
            'sub_category' => 'nullable|string|max:100',
            'brand' => 'nullable|string|max:100',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            // New categories array
            'categories' => 'nullable|array',
            'categories.*.category_id' => 'required_with:categories|exists:categories,id',
            'categories.*.sub_category_id' => 'nullable|exists:sub_categories,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->except(['categories']);
        $product = PublicProduct::create($data);

        // Sync categories if provided
        if ($request->has('categories') && is_array($request->categories)) {
            $product->syncCategories($request->categories);
        }

        // Load relationships for response
        $product->load(['productCategories.category', 'productCategories.subCategory']);
        $product->category_pairs = $product->categoryPairs;

        return response()->json([
            'message' => 'Product created successfully',
            'product' => $product
        ], 201);
    }

    /**
     * Display the specified public product
     */
    public function show($id)
    {
        $product = PublicProduct::with(['productCategories.category', 'productCategories.subCategory'])
            ->find($id);

        if (!$product) {
            return response()->json([
                'message' => 'Product not found'
            ], 404);
        }

        $product->category_pairs = $product->categoryPairs;

        return response()->json($product);
    }

    /**
     * Update the specified public product
     */
    public function update(Request $request, $id)
    {
        $product = PublicProduct::find($id);

        if (!$product) {
            return response()->json([
                'message' => 'Product not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'slug' => 'nullable|string|unique:public_products,slug,' . $id,
            'category' => 'nullable|string|max:100',
            'sub_category' => 'nullable|string|max:100',
            'brand' => 'nullable|string|max:100',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            // New categories array
            'categories' => 'nullable|array',
            'categories.*.category_id' => 'required_with:categories|exists:categories,id',
            'categories.*.sub_category_id' => 'nullable|exists:sub_categories,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->except(['categories']);
        $product->update($data);

        // Sync categories if provided
        if ($request->has('categories')) {
            $product->syncCategories($request->categories ?? []);
        }

        // Load relationships for response
        $product->load(['productCategories.category', 'productCategories.subCategory']);
        $product->category_pairs = $product->categoryPairs;

        return response()->json([
            'message' => 'Product updated successfully',
            'product' => $product
        ]);
    }

    /**
     * Remove the specified public product
     */
    public function destroy($id)
    {
        $product = PublicProduct::find($id);

        if (!$product) {
            return response()->json([
                'message' => 'Product not found'
            ], 404);
        }

        $product->delete();

        return response()->json([
            'message' => 'Product deleted successfully'
        ]);
    }

    /**
     * Get categories for filters (now returns structured data)
     */
    public function categories()
    {
        // Get categories from Category model with subcategories
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

        // Also get legacy categories from products table
        $legacyCategories = PublicProduct::select('category')
            ->distinct()
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->pluck('category');

        return response()->json([
            'categories' => $categoriesList,
            'legacy_categories' => $legacyCategories,
        ]);
    }

    /**
     * Get subcategories for filters
     */
    public function subCategories(Request $request)
    {
        // If category_id is provided, filter by it
        if ($request->filled('category_id')) {
            $subCategories = SubCategory::where('category_id', $request->category_id)
                ->orderBy('name')
                ->get()
                ->map(function ($sub) {
                    return [
                        'id' => $sub->id,
                        'name' => $sub->name,
                    ];
                });
        } else {
            // Return all subcategories
            $subCategories = SubCategory::orderBy('name')
                ->get()
                ->map(function ($sub) {
                    return [
                        'id' => $sub->id,
                        'name' => $sub->name,
                        'category_id' => $sub->category_id,
                    ];
                });
        }

        return response()->json($subCategories);
    }

    /**
     * Get brands for filters
     */
    public function brands()
    {
        $brands = PublicProduct::select('brand')
            ->distinct()
            ->whereNotNull('brand')
            ->where('brand', '!=', '')
            ->pluck('brand');

        return response()->json($brands);
    }

    /**
     * Toggle product active status
     */
    public function toggleActive($id)
    {
        $product = PublicProduct::find($id);

        if (!$product) {
            return response()->json([
                'message' => 'Product not found'
            ], 404);
        }

        $product->is_active = !$product->is_active;
        $product->save();

        return response()->json([
            'message' => 'Product status updated',
            'product' => $product
        ]);
    }

    /**
     * Export all public products to Excel/CSV
     */
    public function exportProducts()
    {
        $products = PublicProduct::ordered()->get();

        // Create CSV content
        $csv = fopen('php://temp', 'w+');

        // Add BOM for UTF-8
        fprintf($csv, chr(0xEF).chr(0xBB).chr(0xBF));

        // Headers
        $headers = [
            'id', 'sku', 'module', 'slug', 'image', 'category', 'sub_category', 'brand',
            'title', 'subtitle', 
            'spec1', 'spec2', 'spec3', 'spec4', 'spec5', 'spec6', 'spec7',
            'descriptions',
            'fitur1', 'fitur2', 'fitur3', 'fitur4', 'fitur5', 
            'fitur6', 'fitur7', 'fitur8', 'fitur9', 'fitur10',
            'fitur11', 'fitur12', 'fitur13', 'fitur14', 'fitur15',
            'wireless_standard', 'wireless_stream', 'wireless_stream1', 'wireless_stream2',
            'wireless_stream3', 'wireless_stream4', 'wireless_stream5',
            'manageable_aps', 'ap_number', 'number_of_clients', 'capacity',
            'ip_rating', 'switching', 'throughput',
            'flash_memory', 'sdram_memory',
            'interface_main', 'interface1', 'interface2', 'interface3', 'interface4', 'interface5',
            'antena', 'cu', 'cu1', 'cu2', 'cu3', 'cu4',
            'additional_interface1', 'additional_interface2', 'additional_interface3', 'additional_interface4',
            'mac_address', 'routing_table',
            'dustproof_waterproof', 'noise', 'mtbf',
            'operating_temperature', 'storage_temperature', 'operating_humidity',
            'power1', 'power2', 'power3', 'power_consumptions',
            'dimensions', 'diagram', 'network_diagram',
            'is_active', 'sort_order'
        ];

        fputcsv($csv, $headers);

        // Data rows
        foreach ($products as $product) {
            $row = [];
            foreach ($headers as $header) {
                $row[] = $product->$header ?? '';
            }
            fputcsv($csv, $row);
        }

        rewind($csv);
        $content = stream_get_contents($csv);
        fclose($csv);

        return response($content)
            ->header('Content-Type', 'text/csv; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="public_products_' . date('Y-m-d_His') . '.csv"');
    }

    /**
     * Export template for importing products
     */
    public function exportTemplate()
    {
        $csv = fopen('php://temp', 'w+');

        // Add BOM for UTF-8
        fprintf($csv, chr(0xEF).chr(0xBB).chr(0xBF));

        // Headers
        $headers = [
            'id', 'sku', 'module', 'slug', 'image', 'category', 'sub_category', 'brand',
            'title', 'subtitle', 
            'spec1', 'spec2', 'spec3', 'spec4', 'spec5', 'spec6', 'spec7',
            'descriptions',
            'fitur1', 'fitur2', 'fitur3', 'fitur4', 'fitur5', 
            'fitur6', 'fitur7', 'fitur8', 'fitur9', 'fitur10',
            'fitur11', 'fitur12', 'fitur13', 'fitur14', 'fitur15',
            'wireless_standard', 'wireless_stream', 'wireless_stream1', 'wireless_stream2',
            'wireless_stream3', 'wireless_stream4', 'wireless_stream5',
            'manageable_aps', 'ap_number', 'number_of_clients', 'capacity',
            'ip_rating', 'switching', 'throughput',
            'flash_memory', 'sdram_memory',
            'interface_main', 'interface1', 'interface2', 'interface3', 'interface4', 'interface5',
            'antena', 'cu', 'cu1', 'cu2', 'cu3', 'cu4',
            'additional_interface1', 'additional_interface2', 'additional_interface3', 'additional_interface4',
            'mac_address', 'routing_table',
            'dustproof_waterproof', 'noise', 'mtbf',
            'operating_temperature', 'storage_temperature', 'operating_humidity',
            'power1', 'power2', 'power3', 'power_consumptions',
            'dimensions', 'diagram', 'network_diagram',
            'is_active', 'sort_order'
        ];

        fputcsv($csv, $headers);

        // Add one example row
        $exampleRow = array_fill(0, count($headers), '');
        $exampleRow[0] = ''; // id (leave empty for new products)
        $exampleRow[8] = 'Example Product Title'; // title
        $exampleRow[9] = 'Example Subtitle'; // subtitle
        $exampleRow[5] = 'XGSPON'; // category
        $exampleRow[7] = 'Example Brand'; // brand
        $exampleRow[73] = '1'; // is_active (1 = active, 0 = inactive)
        $exampleRow[74] = '0'; // sort_order

        fputcsv($csv, $exampleRow);

        rewind($csv);
        $content = stream_get_contents($csv);
        fclose($csv);

        return response($content)
            ->header('Content-Type', 'text/csv; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="public_products_template.csv"');
    }

    /**
     * Import products from Excel/CSV
     */
    public function importProducts(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt'
        ]);

        $file = $request->file('file');
        $handle = fopen($file->path(), 'r');

        // Skip BOM if present
        $bom = fread($handle, 3);
        if ($bom !== chr(0xEF).chr(0xBB).chr(0xBF)) {
            rewind($handle);
        }

        // Read headers
        $headers = fgetcsv($handle);

        $imported = 0;
        $updated = 0;
        $errors = [];

        while (($row = fgetcsv($handle)) !== false) {
            try {
                $data = array_combine($headers, $row);

                // Convert empty strings to null
                foreach ($data as $key => $value) {
                    if ($value === '') {
                        $data[$key] = null;
                    }
                }

                // Handle boolean and integer fields
                $data['is_active'] = filter_var($data['is_active'] ?? true, FILTER_VALIDATE_BOOLEAN);
                $data['sort_order'] = (int) ($data['sort_order'] ?? 0);

                // If ID exists, update; otherwise create
                if (!empty($data['id']) && PublicProduct::find($data['id'])) {
                    $product = PublicProduct::find($data['id']);
                    unset($data['id']); // Don't update ID
                    $product->update($data);
                    $updated++;
                } else {
                    unset($data['id']); // Let database auto-generate ID
                    PublicProduct::create($data);
                    $imported++;
                }
            } catch (\Exception $e) {
                $errors[] = [
                    'row' => $data['title'] ?? 'Unknown',
                    'error' => $e->getMessage()
                ];
            }
        }

        fclose($handle);

        return response()->json([
            'message' => 'Import completed',
            'imported' => $imported,
            'updated' => $updated,
            'errors' => $errors
        ]);
    }
}
