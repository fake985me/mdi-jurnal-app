<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;

class GuestController extends Controller
{
    public function randomProducts()
    {
        $products = Product::with('category')
            ->inRandomOrder()
            ->limit(6)
            ->get();

        return response()->json($products);
    }

    /**
     * Get products for public product
     */
    public function products(Request $request)
    {
        $query = Product::query()
            ->select('products.*')
            ->leftJoin('current_stocks', 'products.id', '=', 'current_stocks.product_id')
            ->addSelect([
                'current_stocks.quantity as stock_quantity',
                'current_stocks.last_updated as stock_last_updated'
            ]);

        // Search
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('products.title', 'like', "%{$search}%")
                    ->orWhere('products.subtitle', 'like', "%{$search}%")
                    ->orWhere('products.brand', 'like', "%{$search}%")
                    ->orWhere('products.category', 'like', "%{$search}%")
                    ->orWhere('products.descriptions', 'like', "%{$search}%");
            });
        }

        // Filter by category
        if ($request->has('category') && $request->category) {
            $query->where('products.category', $request->category);
        }

        // Filter by brand
        if ($request->has('brand') && $request->brand) {
            $query->where('products.brand', $request->brand);
        }

        // Filter by module
        if ($request->has('module') && $request->module) {
            $query->where('products.module', $request->module);
        }

        $products = $query->paginate($request->per_page ?? 12);

        // Add current_stock object to each product for compatibility
        /** @var \Illuminate\Pagination\LengthAwarePaginator $products */
        $products->through(function ($product) {
            $product->setAttribute('current_stock', (object) [
                'quantity' => $product->stock_quantity ?? 0,
                'last_updated' => $product->stock_last_updated
            ]);
            unset($product->stock_quantity, $product->stock_last_updated);
            return $product;
        });

        return response()->json($products);
    }

    /**
     * Get categories for public product (synced with database Category & SubCategory models)
     * Returns sub_categories as array of objects { id, name } for frontend compatibility.
     */
    public function categories()
    {
        $dbCategories = \App\Models\Category::with(['subCategories' => function ($q) {
            $q->orderBy('name');
        }])->orderBy('name')->get();

        $result = [];

        foreach ($dbCategories as $cat) {
            $subsObjects = $cat->subCategories->map(function ($sub) {
                return ['id' => $sub->id, 'name' => $sub->name];
            })->filter(fn($s) => !empty($s['name']))->values()->toArray();

            $result[$cat->name] = [
                'id'             => $cat->id,
                'name'           => $cat->name,
                'title'          => $cat->name,
                'sub_categories' => $subsObjects,
            ];
        }

        // Also ensure categories from active PublicProduct (including multi-category with |) are included
        $publicProducts = \App\Models\PublicProduct::where('is_active', true)->get(['category', 'sub_category']);
        foreach ($publicProducts as $p) {
            $catNames = array_map('trim', explode('|', $p->category ?? ''));
            $subNames = array_map('trim', explode('|', $p->sub_category ?? ''));

            foreach ($catNames as $idx => $cName) {
                if (empty($cName)) continue;
                if (!isset($result[$cName])) {
                    $result[$cName] = [
                        'id'             => null,
                        'name'           => $cName,
                        'title'          => $cName,
                        'sub_categories' => [],
                    ];
                }
                $sName = $subNames[$idx] ?? ($subNames[0] ?? null);
                $existingNames = array_column($result[$cName]['sub_categories'], 'name');
                if ($sName && !in_array($sName, $existingNames)) {
                    $result[$cName]['sub_categories'][] = ['id' => null, 'name' => $sName];
                }
            }
        }

        return response()->json(array_values($result));
    }

    /**
     * Get single product detail for public
     */
    public function show($id)
    {
        $product = Product::query()
            ->select('products.*')
            ->leftJoin('current_stocks', 'products.id', '=', 'current_stocks.product_id')
            ->addSelect([
                'current_stocks.quantity as stock_quantity',
                'current_stocks.last_updated as stock_last_updated'
            ])
            ->where('products.id', $id)
            ->first();

        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        // Add current_stock object for compatibility
        $product->current_stock = (object) [
            'quantity' => $product->stock_quantity ?? 0,
            'last_updated' => $product->stock_last_updated
        ];
        unset($product->stock_quantity, $product->stock_last_updated);

        return response()->json($product);
    }
}
