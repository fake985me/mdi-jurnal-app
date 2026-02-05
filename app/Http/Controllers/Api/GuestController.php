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
        $transformedItems = $products->getCollection()->map(function ($product) {
            $product->current_stock = (object) [
                'quantity' => $product->stock_quantity ?? 0,
                'last_updated' => $product->stock_last_updated
            ];
            unset($product->stock_quantity, $product->stock_last_updated);
            return $product;
        });

        $products->setCollection($transformedItems);

        return response()->json($products);
    }

    /**
     * Get categories for public product
     */
    public function categories()
    {
        // Get unique categories from products
        $categories = Product::whereNotNull('category')
            ->distinct()
            ->pluck('category')
            ->filter()
            ->sort()
            ->values()
            ->map(function ($category) {
                return [
                    'name' => $category,
                    'value' => $category
                ];
            });

        return response()->json($categories);
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
