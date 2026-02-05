<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = Category::with('subCategories');

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        $categories = $query->orderBy('name')->get();

        // Add product count for each category and subcategory
        $categories->each(function ($category) {
            $subCategoryIds = $category->subCategories
                ? $category->subCategories->pluck('id')->filter()->values()->all()
                : [];

            // Count products via pivot table (include subcategory links, avoid double-counting)
            $category->products_count = DB::table('product_category')
                ->when(!empty($subCategoryIds), function ($query) use ($category, $subCategoryIds) {
                    $query->where(function ($subQuery) use ($category, $subCategoryIds) {
                        $subQuery->where('category_id', $category->id)
                            ->orWhereIn('sub_category_id', $subCategoryIds);
                    });
                }, function ($query) use ($category) {
                    $query->where('category_id', $category->id);
                })
                ->distinct()
                ->count('product_id');
            
            // Also add for public products (include subcategory links, avoid double-counting)
            $category->public_products_count = DB::table('public_product_category')
                ->when(!empty($subCategoryIds), function ($query) use ($category, $subCategoryIds) {
                    $query->where(function ($subQuery) use ($category, $subCategoryIds) {
                        $subQuery->where('category_id', $category->id)
                            ->orWhereIn('sub_category_id', $subCategoryIds);
                    });
                }, function ($query) use ($category) {
                    $query->where('category_id', $category->id);
                })
                ->distinct()
                ->count('public_product_id');
            
            // Add products_count to each subcategory
            if ($category->subCategories) {
                $category->subCategories->each(function ($subCategory) {
                    $subCategory->products_count = DB::table('product_category')
                        ->where('sub_category_id', $subCategory->id)
                        ->count();
                    $subCategory->public_products_count = DB::table('public_product_category')
                        ->where('sub_category_id', $subCategory->id)
                        ->count();
                });
            }
        });

        return response()->json($categories);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:categories,name',
            'description' => 'nullable|string',
        ]);

        $category = Category::create($validated);

        return response()->json($category, 201);
    }

    public function show($id)
    {
        $category = Category::findOrFail($id);

        // Get products that match this category
        $category->products_count = DB::table('products')
            ->where('category', $category->name)
            ->count();

        return response()->json($category);
    }

    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:categories,name,' . $id,
            'description' => 'nullable|string',
        ]);

        $category->update($validated);

        return response()->json($category);
    }

    public function destroy($id)
    {
        $category = Category::findOrFail($id);

        // Check if category is used by products (legacy VARCHAR field)
        $legacyCount = DB::table('products')
            ->where('category', $category->name)
            ->count();

        // Check if category is used via pivot table
        $pivotCount = DB::table('product_category')
            ->where('category_id', $category->id)
            ->count();

        // Check if category is used by public products via pivot
        $publicPivotCount = DB::table('public_product_category')
            ->where('category_id', $category->id)
            ->count();

        $productsCount = $legacyCount + $pivotCount + $publicPivotCount;

        if ($productsCount > 0) {
            return response()->json([
                'message' => "Cannot delete category. It is used by {$productsCount} product(s). Please reassign products first.",
                'products_count' => $productsCount
            ], 422);
        }

        // Also delete subcategories
        $category->subCategories()->delete();

        $category->delete();

        return response()->json(['message' => 'Category deleted successfully']);
    }
}
