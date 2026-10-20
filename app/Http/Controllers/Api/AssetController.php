<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{Asset, Product, PurchaseItem};
use App\Services\AssetDepreciationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssetController extends Controller
{
    private const RELATIONS = ['product', 'purchaseItem.purchase', 'depreciations'];

    public function index(Request $request)
    {
        $request->validate(['per_page' => 'nullable|integer|min:1|max:100', 'page' => 'nullable|integer|min:1',
            'status' => 'nullable|in:active,in_use,maintenance,disposed']);
        $query = Asset::query();
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                foreach (['name', 'asset_code', 'serial_number', 'brand', 'assigned_to'] as $field) $q->orWhere($field, 'like', '%'.$search.'%');
            });
        }
        foreach (['category', 'location', 'condition', 'status'] as $field) {
            if ($request->filled($field)) $query->where($field, $request->$field);
        }
        $summary = ['count' => (clone $query)->count()];
        foreach (['cost' => 'purchase_price', 'depreciation' => 'accumulated_depreciation', 'book_value' => 'current_value'] as $key => $field) {
            $totals = clone $query;
            if ($key === 'book_value') $totals->where('status', '!=', 'disposed');
            $summary[$key] = (float) $totals->sum($field);
        }
        $assets = $query->with(['product', 'purchaseItem.purchase'])->orderByDesc('created_at')->orderByDesc('id')->paginate($request->per_page ?? 15);
        return response()->json(array_merge($assets->toArray(), ['summary' => $summary]));
    }

    public function filterOptions()
    {
        $items = PurchaseItem::with(['purchase', 'product'])->whereHas('purchase', fn ($q) => $q->whereIn('status', ['paid', 'received']))->get();
        return response()->json([
            'categories' => Asset::whereNotNull('category')->distinct()->orderBy('category')->pluck('category'),
            'locations' => Asset::whereNotNull('location')->distinct()->orderBy('location')->pluck('location'),
            'products' => Product::select('id', 'title', 'sku')->orderBy('title')->get(),
            'purchase_items' => $items->map(fn ($item) => [
                'id' => $item->id, 'label' => ($item->purchase?->po_number ?? '').' - '.($item->product?->title ?? ''),
                'po_number' => $item->purchase?->po_number, 'product_title' => $item->product?->title, 'quantity' => $item->quantity,
                'product_id' => $item->product_id, 'unit_price' => $item->unit_price,
                'purchase_date' => $item->purchase?->received_date ?? $item->purchase?->order_date,
            ]),
            'conditions' => ['good', 'fair', 'poor', 'damaged'], 'statuses' => ['active', 'in_use', 'maintenance', 'disposed'],
        ]);
    }

    private function rules(bool $updating = false): array
    {
        return [
            'name' => ($updating ? 'sometimes|' : '').'required|string|max:255',
            'product_id' => 'nullable|exists:products,id', 'purchase_item_id' => 'nullable|exists:purchase_items,id',
            'category' => 'nullable|string|max:255', 'brand' => 'nullable|string|max:255', 'model' => 'nullable|string|max:255',
            'serial_number' => 'nullable|string|max:255', 'location' => 'nullable|string|max:255',
            'condition' => 'sometimes|required|in:good,fair,poor,damaged', 'status' => 'sometimes|required|in:active,in_use,maintenance',
            'purchase_date' => 'nullable|date', 'purchase_price' => 'nullable|numeric|min:0|max:9999999999.99',
            'current_value' => 'nullable|numeric|min:0|max:9999999999.99', 'assigned_to' => 'nullable|string|max:255',
            'warranty_end_date' => 'nullable|date', 'notes' => 'nullable|string|max:10000', 'image' => 'nullable|string|max:2000',
            'depreciation_method' => 'sometimes|required|in:none,straight_line', 'depreciation_start_date' => 'nullable|date',
            'useful_life_months' => 'nullable|integer|min:1|max:600', 'residual_value' => 'nullable|numeric|min:0',
        ];
    }

    private function financials(array $data, ?Asset $asset = null): array
    {
        foreach (['purchase_price', 'current_value', 'residual_value'] as $field) {
            if (array_key_exists($field, $data) && $data[$field] === null) $data[$field] = 0;
        }
        if (!$asset && !array_key_exists('current_value', $data)) $data['current_value'] = $data['purchase_price'] ?? 0;
        $fields = ['purchase_price', 'purchase_date', 'depreciation_method', 'depreciation_start_date', 'useful_life_months', 'residual_value'];
        if ($asset && $asset->depreciations()->exists()) {
            foreach ($fields as $field) {
                $old = $asset->$field;
                if ($old instanceof \DateTimeInterface) $old = $old->format('Y-m-d');
                $new = array_key_exists($field, $data) ? $data[$field] : $old;
                $changed = is_numeric($old) && is_numeric($new) ? (float) $old !== (float) $new : (string) $old !== (string) $new;
                if ($changed) throw ValidationException::withMessages([$field => 'Depreciation settings are locked after posting.']);
            }
        }
        $values = array_merge(['depreciation_method' => 'none', 'residual_value' => 0, 'purchase_price' => 0], $asset?->only($fields) ?? [], $data);
        if ($values['depreciation_method'] === 'straight_line') {
            validator($values, [
                'purchase_price' => 'required|numeric|min:0.01|max:9999999999.99',
                'residual_value' => 'required|numeric|min:0|max:'.(float) $values['purchase_price'],
                'useful_life_months' => 'required|integer|min:1|max:600',
                'depreciation_start_date' => ['required', 'date', ...(!empty($values['purchase_date']) ? ['after_or_equal:purchase_date'] : [])],
            ])->validate();
            $data['current_value'] = round((float) $values['purchase_price'] - (float) ($asset?->accumulated_depreciation ?? 0), 2);
        }
        return $data;
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules());
        if (!empty($data['purchase_item_id'])) {
            $item = PurchaseItem::with(['purchase', 'product'])->findOrFail($data['purchase_item_id']);
            $data['product_id'] = $data['product_id'] ?? $item->product_id;
            $data['purchase_date'] = $data['purchase_date'] ?? $item->purchase?->received_date ?? $item->purchase?->order_date;
            $data['purchase_price'] = $data['purchase_price'] ?? $item->unit_price;
            $data['current_value'] = $data['current_value'] ?? $item->unit_price;
            $data['brand'] = $data['brand'] ?? $item->product?->brand;
            $data['category'] = $data['category'] ?? $item->product?->category;
        }
        $data = $this->financials($data);
        // A random code avoids collisions when multiple users register assets together.
        $data['asset_code'] = Asset::generateAssetCode();
        $asset = Asset::create($data);
        return response()->json($asset->load(self::RELATIONS), 201);
    }

    public function show(Asset $asset) { return response()->json($asset->load(self::RELATIONS)); }

    public function update(Request $request, Asset $asset)
    {
        $data = $request->validate($this->rules(true));
        return DB::transaction(function () use ($asset, $data) {
            $asset = Asset::lockForUpdate()->findOrFail($asset->id);
            abort_if($asset->status === 'disposed', 422, 'Disposed assets cannot be edited.');
            $asset->update($this->financials($data, $asset));
            return response()->json($asset->fresh(self::RELATIONS));
        });
    }

    public function destroy(Asset $asset)
    {
        return DB::transaction(function () use ($asset) {
            $asset = Asset::lockForUpdate()->findOrFail($asset->id);
            abort_if($asset->status === 'disposed' || $asset->depreciations()->exists(), 422, 'Use asset disposal to retain depreciation history.');
            $asset->delete();
            return response()->json(['message' => 'Asset deleted successfully']);
        });
    }

    public function depreciate(Request $request, Asset $asset, AssetDepreciationService $service)
    {
        $data = $request->validate(['as_of' => 'required|date|before_or_equal:today']);
        $count = $service->post($asset, $data['as_of'], auth()->id());
        return response()->json(['posted' => $count, 'asset' => $asset->fresh(self::RELATIONS)]);
    }

    public function dispose(Request $request, Asset $asset, AssetDepreciationService $service)
    {
        $data = $request->validate(['disposal_date' => 'required|date|before_or_equal:today',
            'disposal_proceeds' => 'required|numeric|min:0|max:9999999999.99', 'disposal_reason' => 'required|string|max:2000']);
        return DB::transaction(function () use ($asset, $data, $service) {
            $asset = Asset::lockForUpdate()->findOrFail($asset->id);
            abort_if($asset->status === 'disposed', 422, 'Asset already disposed.');
            $lastPeriod = $asset->depreciations()->max('period');
            $date = $data['disposal_date'];
            abort_if(($asset->purchase_date && $date < $asset->purchase_date->toDateString()) || ($lastPeriod && $date < $lastPeriod), 422, 'Disposal cannot precede acquisition or posted depreciation.');
            if ($asset->depreciation_method === 'straight_line') { $service->post($asset, $date, auth()->id()); $asset->refresh(); }
            $asset->update($data + ['disposal_book_value' => $asset->current_value, 'current_value' => 0, 'status' => 'disposed']);
            return response()->json($asset->fresh(self::RELATIONS));
        });
    }

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

