<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Warranty;
use App\Models\Sale;
use Illuminate\Http\Request;

class WarrantyController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = Warranty::with(['sale.customer', 'product']);

            // Filter by status
            if ($request->has('status') && !empty($request->status)) {
                $query->where('status', $request->status);
            }

            // Search
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('warranty_code', 'like', "%{$search}%")
                      ->orWhere('serial_number', 'like', "%{$search}%")
                      ->orWhereHas('sale', function($sq) use ($search) {
                          $sq->where('invoice_number', 'like', "%{$search}%");
                      })
                      ->orWhereHas('product', function($pq) use ($search) {
                          $pq->where('title', 'like', "%{$search}%");
                      });
                });
            }

            $warranties = $query->orderBy('sale_id', 'desc')
                ->orderBy('product_id')
                ->orderBy('serial_number')
                ->paginate($request->per_page ?? 15);

            return response()->json($warranties);
        } catch (\Exception $e) {
            \Log::error('Warranties API Error: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'message' => 'Error loading warranties',
                'error' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'sale_id' => 'required|exists:sales,id',
            'product_id' => 'required|exists:products,id',
            'warranty_code' => 'required|unique:warranties,warranty_code',
            'serial_number' => 'nullable|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'status' => 'required|in:active,expired,claimed',
            'notes' => 'nullable|string',
        ]);

        $warranty = Warranty::create([
            ...$validated,
            'user_id' => auth()->id(),
        ]);

        return response()->json($warranty->load(['sale', 'product']), 201);
    }

    public function batchStore(Request $request)
    {
        $validated = $request->validate([
            'sale_id' => 'required|exists:sales,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'status' => 'required|in:active,expired,claimed',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.serial_number' => 'nullable|string|max:255',
        ]);

        $warranties = [];
        foreach ($validated['items'] as $item) {
            $warranties[] = Warranty::create([
                'warranty_code' => 'WRN-' . now()->timestamp . '-' . $item['product_id'],
                'sale_id' => $validated['sale_id'],
                'product_id' => $item['product_id'],
                'serial_number' => $item['serial_number'] ?? null,
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'status' => $validated['status'],
                'notes' => $validated['notes'] ?? null,
                'user_id' => auth()->id(),
            ]);
        }

        return response()->json([
            'message' => count($warranties) . ' warranties created successfully',
            'data' => $warranties,
        ], 201);
    }

    public function show($id)
    {
        $warranty = Warranty::with(['sale', 'product'])->findOrFail($id);
        return response()->json($warranty);
    }

    public function update(Request $request, $id)
    {
        $warranty = Warranty::findOrFail($id);

        $validated = $request->validate([
            'sale_id' => 'sometimes|required|exists:sales,id',
            'product_id' => 'sometimes|required|exists:products,id',
            'serial_number' => 'nullable|string|max:255',
            'start_date' => 'sometimes|required|date',
            'end_date' => 'sometimes|required|date|after:start_date',
            'status' => 'required|in:active,expired,claimed',
            'notes' => 'nullable|string',
        ]);

        $warranty->update($validated);

        return response()->json($warranty->load(['sale', 'product']));
    }

    public function destroy($id)
    {
        $warranty = Warranty::findOrFail($id);
        $warranty->delete();

        return response()->json(['message' => 'Warranty deleted successfully']);
    }
}
