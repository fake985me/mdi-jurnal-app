<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\Product;
use App\Models\PurchaseItem;
use Illuminate\Http\Request;

class AssetController extends Controller
{
    /**
     * Display a listing of assets with filters
     */
    public function index(Request $request)
    {
        try {
            $query = Asset::with(['product', 'purchaseItem.purchase']);

            // Search by name, asset_code, serial_number
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('asset_code', 'like', "%{$search}%")
                        ->orWhere('serial_number', 'like', "%{$search}%")
                        ->orWhere('brand', 'like', "%{$search}%");
                });
            }

            // Filter by category
            if ($request->has('category') && !empty($request->category)) {
                $query->where('category', $request->category);
            }

            // Filter by location
            if ($request->has('location') && !empty($request->location)) {
                $query->where('location', $request->location);
            }

            // Filter by condition
            if ($request->has('condition') && !empty($request->condition)) {
                $query->where('condition', $request->condition);
            }

            // Filter by status
            if ($request->has('status') && !empty($request->status)) {
                $query->where('status', $request->status);
            }

            $assets = $query->orderBy('created_at', 'desc')
                ->paginate($request->per_page ?? 15);

            return response()->json($assets);
        } catch (\Exception $e) {
            \Log::error('Assets API Error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error loading assets',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get filter options for dropdowns
     */
    public function filterOptions()
    {
        try {
            $categories = Asset::distinct()->whereNotNull('category')->pluck('category');
            $locations = Asset::distinct()->whereNotNull('location')->pluck('location');
            $products = Product::select('id', 'title', 'sku')->orderBy('title')->get();
            $purchaseItems = PurchaseItem::with(['purchase:id,po_number,supplier_name', 'product:id,title'])
                ->whereHas('purchase', function($q) {
                    $q->where('status', 'received');
                })
                ->get()
                ->map(function($item) {
                    return [
                        'id' => $item->id,
                        'label' => ($item->purchase->po_number ?? '') . ' - ' . ($item->product->title ?? 'Unknown'),
                        'po_number' => $item->purchase->po_number ?? '',
                        'product_title' => $item->product->title ?? '',
                        'quantity' => $item->quantity,
                    ];
                });

            return response()->json([
                'categories' => $categories,
                'locations' => $locations,
                'conditions' => ['good', 'fair', 'poor', 'damaged'],
                'statuses' => ['active', 'in_use', 'maintenance', 'disposed'],
                'products' => $products,
                'purchase_items' => $purchaseItems,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error loading filter options',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created asset
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'product_id' => 'nullable|exists:products,id',
            'purchase_item_id' => 'nullable|exists:purchase_items,id',
            'category' => 'nullable|string|max:255',
            'brand' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'serial_number' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'condition' => 'nullable|in:good,fair,poor,damaged',
            'status' => 'nullable|in:active,in_use,maintenance,disposed',
            'purchase_date' => 'nullable|date',
            'purchase_price' => 'nullable|numeric|min:0',
            'current_value' => 'nullable|numeric|min:0',
            'assigned_to' => 'nullable|string|max:255',
            'warranty_end_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'image' => 'nullable|string',
        ]);

        try {
            // Auto-generate asset code
            $validated['asset_code'] = Asset::generateAssetCode();

            // Auto-fill from purchase item if selected
            if (!empty($validated['purchase_item_id'])) {
                $purchaseItem = PurchaseItem::with(['purchase', 'product'])->find($validated['purchase_item_id']);
                if ($purchaseItem) {
                    $validated['product_id'] = $validated['product_id'] ?? $purchaseItem->product_id;
                    $validated['purchase_date'] = $validated['purchase_date'] ?? $purchaseItem->purchase->received_date;
                    $validated['purchase_price'] = $validated['purchase_price'] ?? $purchaseItem->unit_price;
                    $validated['current_value'] = $validated['current_value'] ?? $purchaseItem->unit_price;
                    
                    // Auto-fill from product if available
                    if ($purchaseItem->product) {
                        $validated['brand'] = $validated['brand'] ?? $purchaseItem->product->brand;
                        $validated['category'] = $validated['category'] ?? $purchaseItem->product->category;
                    }
                }
            }

            $asset = Asset::create($validated);
            
            return response()->json(
                Asset::with(['product', 'purchaseItem.purchase'])->find($asset->id),
                201
            );
        } catch (\Exception $e) {
            \Log::error('Asset Store Error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error creating asset',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified asset
     */
    public function show(Asset $asset)
    {
        return response()->json(
            $asset->load(['product', 'purchaseItem.purchase'])
        );
    }

    /**
     * Update the specified asset
     */
    public function update(Request $request, Asset $asset)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'product_id' => 'nullable|exists:products,id',
            'purchase_item_id' => 'nullable|exists:purchase_items,id',
            'category' => 'nullable|string|max:255',
            'brand' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'serial_number' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'condition' => 'nullable|in:good,fair,poor,damaged',
            'status' => 'nullable|in:active,in_use,maintenance,disposed',
            'purchase_date' => 'nullable|date',
            'purchase_price' => 'nullable|numeric|min:0',
            'current_value' => 'nullable|numeric|min:0',
            'assigned_to' => 'nullable|string|max:255',
            'warranty_end_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'image' => 'nullable|string',
        ]);

        try {
            $asset->update($validated);
            
            return response()->json(
                $asset->fresh(['product', 'purchaseItem.purchase'])
            );
        } catch (\Exception $e) {
            \Log::error('Asset Update Error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error updating asset',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified asset
     */
    public function destroy(Asset $asset)
    {
        try {
            $asset->delete();
            return response()->json(['message' => 'Asset deleted successfully']);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error deleting asset',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Quick create an asset product (not counted as sales stock)
     */
    public function createAssetProduct(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'sku' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:255',
            'brand' => 'nullable|string|max:255',
            'descriptions' => 'nullable|string',
        ]);

        try {
            // Mark as asset product
            $validated['is_asset'] = true;
            $validated['stock'] = 0;
            $validated['minimum_stock'] = 0;

            // Auto-generate SKU if not provided
            if (empty($validated['sku'])) {
                $lastProduct = Product::where('sku', 'like', 'AST-%')->orderBy('id', 'desc')->first();
                $lastNum = $lastProduct ? intval(substr($lastProduct->sku, 4)) : 0;
                $validated['sku'] = 'AST-' . str_pad($lastNum + 1, 4, '0', STR_PAD_LEFT);
            }

            $product = Product::create($validated);

            return response()->json([
                'message' => 'Asset product created successfully',
                'product' => $product,
            ], 201);
        } catch (\Exception $e) {
            \Log::error('Create Asset Product Error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error creating asset product',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

