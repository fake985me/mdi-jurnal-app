<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StockAdjustment;
use App\Models\Product;
use App\Services\StockSyncService;
use Illuminate\Http\Request;

class StockAdjustmentController extends Controller
{
    protected $stockService;

    public function __construct(StockSyncService $stockService)
    {
        $this->stockService = $stockService;
    }

    /**
     * Get list of adjustments with optional filters
     */
    public function index(Request $request)
    {
        $query = StockAdjustment::with(['product', 'user']);

        // Filter by product
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        // Filter by reason
        if ($request->filled('reason')) {
            $query->where('reason', $request->reason);
        }

        // Filter by type
        if ($request->filled('adjustment_type')) {
            $query->where('adjustment_type', $request->adjustment_type);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('adjustment_code', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhereHas('product', function ($pq) use ($search) {
                        $pq->where('title', 'like', "%{$search}%");
                    });
            });
        }

        // Date range
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $adjustments = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json($adjustments);
    }

    /**
     * Create a new stock adjustment
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'adjustment_type' => 'required|in:in,out',
            'quantity' => 'required|integer|min:1',
            'reason' => 'required|string|max:50',
            'related_type' => 'nullable|string|max:50',
            'related_id' => 'nullable|integer',
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            $adjustment = $this->stockService->adjustStock(
                $validated['product_id'],
                $validated['adjustment_type'],
                $validated['quantity'],
                $validated['reason'],
                $validated['related_type'] ?? null,
                $validated['related_id'] ?? null,
                $validated['notes'] ?? null
            );

            return response()->json(
                $adjustment->load(['product', 'user']),
                201
            );
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Get adjustment detail
     */
    public function show($id)
    {
        $adjustment = StockAdjustment::with(['product', 'user'])->findOrFail($id);
        return response()->json($adjustment);
    }

    /**
     * Get available reasons
     */
    public function reasons()
    {
        return response()->json(StockAdjustment::REASONS);
    }

    /**
     * Get adjustments for a specific product
     */
    public function byProduct($productId)
    {
        $adjustments = StockAdjustment::with(['user'])
            ->where('product_id', $productId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($adjustments);
    }

    /**
     * Get adjustment summary stats
     */
    public function stats(Request $request)
    {
        $query = StockAdjustment::query();

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        // Date range
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $stats = [
            'total_adjustments' => $query->count(),
            'total_in' => (clone $query)->where('adjustment_type', 'in')->sum('quantity'),
            'total_out' => (clone $query)->where('adjustment_type', 'out')->sum('quantity'),
            'by_reason' => (clone $query)
                ->selectRaw('reason, SUM(quantity) as total, COUNT(*) as count')
                ->groupBy('reason')
                ->get(),
        ];

        return response()->json($stats);
    }
}
