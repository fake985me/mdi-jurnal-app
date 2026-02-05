<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Delivery;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeliveryController extends Controller
{
    /**
     * Get all deliveries
     */
    public function index(Request $request)
    {
        try {
            // Show all deliveries (not just pending sales)
            $query = Delivery::with(['sale.items.product', 'sale.user', 'user']);

            // Filter by search
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->whereHas('sale', function($q) use ($search) {
                    $q->where('invoice_number', 'like', "%{$search}%")
                      ->orWhere('customer_name', 'like', "%{$search}%");
                });
            }

            // Filter by status
            if ($request->has('status') && !empty($request->status)) {
                $query->where('status', $request->status);
            }

            $deliveries = $query->orderBy('created_at', 'desc')
                ->paginate($request->per_page ?? 15);

            return response()->json($deliveries);
        } catch (\Exception $e) {
            \Log::error('Deliveries API Error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error loading deliveries',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate sequential tracking number
     */
    private function generateTrackingNumber()
    {
        // Format: TRK-YYYYMMDD-XXXX
        // Example: TRK-20251210-0001
        $today = now()->format('Ymd');
        $prefix = "TRK-{$today}-";
        
        // Find last tracking number today
        $lastDelivery = Delivery::where('tracking_number', 'like', "{$prefix}%")
            ->orderBy('tracking_number', 'desc')
            ->first();
        
        if ($lastDelivery) {
            // Get last number and increment
            $lastNumber = (int) substr($lastDelivery->tracking_number, -4);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }
        
        return $prefix . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Create delivery from a sale (pending or completed)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'sale_id' => 'required|exists:sales,id',
            'tracking_number' => 'nullable|string',
            'courier' => 'required|string',
            'notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $sale = Sale::findOrFail($validated['sale_id']);

            // Allow only pending or completed sales
            if (!in_array($sale->status, ['pending', 'completed'])) {
                throw new \Exception('Can only create delivery for pending or completed sales');
            }

            // Check if delivery already exists
            if ($sale->delivery) {
                throw new \Exception('Delivery already exists for this sale');
            }

            // Auto-generate tracking number if not provided
            $trackingNumber = $validated['tracking_number'] ?? $this->generateTrackingNumber();

            $delivery = Delivery::create([
                'sale_id' => $validated['sale_id'],
                'tracking_number' => $trackingNumber,
                'courier' => $validated['courier'],
                'status' => 'preparing',
                'user_id' => auth()->id(),
                'notes' => $validated['notes'],
                'shipped_date' => now(),
            ]);

            DB::commit();
            return response()->json($delivery->load('sale'), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function show($id)
    {
        $delivery = Delivery::with(['sale.items.product', 'user'])->findOrFail($id);
        return response()->json($delivery);
    }

    /**
     * Update delivery status - when delivered, update sale to completed
     */
    public function update(Request $request, $id)
    {
        $delivery = Delivery::with('sale')->findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:preparing,shipped,in_transit,delivered,cancelled',
            'tracking_number' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $delivery->update($validated);

            // If delivered, update sale status to completed
            if ($validated['status'] === 'delivered') {
                $delivery->delivered_date = now();
                $delivery->save();
                
                $delivery->sale->update(['status' => 'completed']);
            }

            DB::commit();
            return response()->json($delivery->load('sale'));
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function destroy($id)
    {
        $delivery = Delivery::with('sale')->findOrFail($id);

        // Can only delete if not yet delivered
        if ($delivery->status === 'delivered') {
            return response()->json(['message' => 'Cannot delete delivered delivery'], 422);
        }

        $delivery->delete();
        return response()->json(['message' => 'Delivery deleted successfully']);
    }
}
