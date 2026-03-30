<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SubCategory;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SubCategoryController extends Controller
{
    /**
     * Get all subcategories, optionally filtered by category
     */
    public function index(Request $request)
    {
        $query = SubCategory::with(['category', 'parent', 'children']);

        // Filter by category
        if ($request->has('category_id') && $request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        // Search
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        $subCategories = $query->orderBy('name')->get();

        // Add product count for each subcategory
        $subCategories->each(function ($subCategory) {
            $subCategory->products_count = DB::table('product_category')
                ->where('sub_category_id', $subCategory->id)
                ->count();
            $subCategory->public_products_count = DB::table('public_product_category')
                ->where('sub_category_id', $subCategory->id)
                ->count();
        });

        return response()->json($subCategories);
    }

    /**
     * Store a new subcategory
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:sub_categories,id',
        ]);

        // Check for unique name within category
        $exists = SubCategory::where('category_id', $validated['category_id'])
            ->where('parent_id', $validated['parent_id'] ?? null)
            ->where('name', $validated['name'])
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'A subcategory with this name already exists in the selected category.'
            ], 422);
        }

        $subCategory = SubCategory::create($validated);
        $subCategory->load('category');

        return response()->json($subCategory, 201);
    }

    /**
     * Get a specific subcategory
     */
    public function show($id)
    {
        $subCategory = SubCategory::with('category')->findOrFail($id);

        // Get product counts
        $subCategory->products_count = DB::table('product_category')
            ->where('sub_category_id', $id)
            ->count();
        $subCategory->public_products_count = DB::table('public_product_category')
            ->where('sub_category_id', $id)
            ->count();

        return response()->json($subCategory);
    }

    /**
     * Update a subcategory
     */
    public function update(Request $request, $id)
    {
        $subCategory = SubCategory::findOrFail($id);

        $validated = $request->validate([
            'category_id' => 'sometimes|required|exists:categories,id',
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:sub_categories,id',
        ]);

        if (!empty($validated['parent_id']) && (int) $validated['parent_id'] === (int) $subCategory->id) {
            return response()->json([
                'message' => 'Subcategory cannot be its own parent.'
            ], 422);
        }

        // Check for unique name within category (excluding current)
        $categoryId = $validated['category_id'] ?? $subCategory->category_id;
        $parentId = $validated['parent_id'] ?? $subCategory->parent_id;
        $exists = SubCategory::where('category_id', $categoryId)
            ->where('parent_id', $parentId)
            ->where('name', $validated['name'])
            ->where('id', '!=', $id)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'A subcategory with this name already exists in the selected category.'
            ], 422);
        }

        $subCategory->update($validated);
        $subCategory->load('category');

        return response()->json($subCategory);
    }

    /**
     * Delete a subcategory
     */
    public function destroy($id)
    {
        $subCategory = SubCategory::findOrFail($id);

        if ($subCategory->children()->exists()) {
            return response()->json([
                'message' => 'Cannot delete subcategory that has child subcategories. Reassign or delete child subcategories first.'
            ], 422);
        }

        // Check if subcategory is used by products
        $productsCount = DB::table('product_category')
            ->where('sub_category_id', $id)
            ->count();
        $publicProductsCount = DB::table('public_product_category')
            ->where('sub_category_id', $id)
            ->count();

        $totalCount = $productsCount + $publicProductsCount;

        if ($totalCount > 0) {
            return response()->json([
                'message' => "Cannot delete subcategory. It is used by {$totalCount} product(s). Please reassign products first.",
                'products_count' => $totalCount
            ], 422);
        }

        $subCategory->delete();

        return response()->json(['message' => 'Subcategory deleted successfully']);
    }
}
