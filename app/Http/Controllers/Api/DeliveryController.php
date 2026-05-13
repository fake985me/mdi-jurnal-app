<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Delivery;
use App\Models\DeliveryItem;
use App\Models\Sale;
use App\Models\Product;
use App\Models\Warranty;
use App\Models\Warehouse;
use App\Exports\DeliverySerialTemplate;
use App\Imports\DeliverySerialImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Facades\Excel;

class DeliveryController extends Controller
{
    /**
     * Get all deliveries
     */
    public function index(Request $request)
    {
        try {
            // Show all deliveries (not just pending sales)
            $query = Delivery::with(['sale.items.product', 'sale.user', 'sale.customer', 'user', 'items.product']);

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
     * Generate unique warranty code
     */
    private function generateWarrantyCode($productId)
    {
        return 'WRN-' . now()->format('Ymd') . '-' . $productId . '-' . strtoupper(substr(uniqid(), -4));
    }

    /**
     * Auto-create warranties for delivery items that have serial numbers
     */
    private function autoCreateWarranties(Delivery $delivery, array $items)
    {
        $warrantiesCreated = 0;

        foreach ($items as $item) {
            $serialNumber = $item['serial_number'] ?? null;
            if (empty($serialNumber)) {
                continue;
            }

            $productId = $item['product_id'];
            $saleId = $delivery->sale_id;

            // Skip if warranty already exists for this sale + product + serial_number
            $existing = Warranty::where('sale_id', $saleId)
                ->where('product_id', $productId)
                ->where('serial_number', $serialNumber)
                ->first();

            if ($existing) {
                continue;
            }

            // Get product for warranty period
            $product = Product::find($productId);
            if (!$product) {
                continue;
            }
            $warrantyMonths = $product->warranty_period_months ?? 12;

            // Skip if warranty period is 0 (no warranty for this product)
            if ($warrantyMonths <= 0) {
                continue;
            }

            $startDate = $delivery->shipped_date ? Carbon::parse($delivery->shipped_date) : now();
            $endDate = $startDate->copy()->addMonths($warrantyMonths);

            Warranty::create([
                'warranty_code' => $this->generateWarrantyCode($productId),
                'serial_number' => $serialNumber,
                'product_id' => $productId,
                'sale_id' => $saleId,
                'user_id' => auth()->id(),
                'warranty_period_months' => $warrantyMonths,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'status' => 'active',
                'notes' => 'Auto-created from delivery ' . ($delivery->tracking_number ?? $delivery->id),
            ]);

            $warrantiesCreated++;
        }

        return $warrantiesCreated;
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
            'from_warehouse_id' => 'nullable|exists:warehouses,id',
            'destination' => 'nullable|string',
            'notes' => 'nullable|string',
            'items' => 'nullable|array',
            'items.*.product_id' => 'required_with:items|exists:products,id',
            'items.*.quantity' => 'required_with:items|integer|min:1',
            'items.*.serial_number' => 'nullable|string|max:255',
        ]);

        DB::beginTransaction();
        try {
            $sale = Sale::with('items.product')->findOrFail($validated['sale_id']);

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
            $fromWarehouseId = $validated['from_warehouse_id']
                ?? $sale->warehouse_id
                ?? Warehouse::getDefault()?->id;
            $destination = $validated['destination'] ?? $sale->customer_address;

            $delivery = Delivery::create([
                'sale_id' => $validated['sale_id'],
                'from_warehouse_id' => $fromWarehouseId,
                'tracking_number' => $trackingNumber,
                'courier' => $validated['courier'],
                'destination' => $destination,
                'status' => 'preparing',
                'user_id' => auth()->id(),
                'notes' => $validated['notes'] ?? null,
                'shipped_date' => now(),
            ]);

            // Create delivery items with serial numbers
            $items = $validated['items'] ?? [];
            
            // If no items provided, create from sale items (backward compatibility)
            if (empty($items)) {
                foreach ($sale->items as $saleItem) {
                    DeliveryItem::create([
                        'delivery_id' => $delivery->id,
                        'product_id' => $saleItem->product_id,
                        'quantity' => $saleItem->quantity,
                    ]);
                }
            } else {
                foreach ($items as $item) {
                    DeliveryItem::create([
                        'delivery_id' => $delivery->id,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'serial_number' => $item['serial_number'] ?? null,
                    ]);
                }

                // Auto-create warranties for items with serial numbers
                $warrantiesCreated = $this->autoCreateWarranties($delivery, $items);
            }

            DB::commit();

            $response = $delivery->load(['sale.items.product', 'items.product']);
            $responseData = $response->toArray();
            $responseData['warranties_created'] = $warrantiesCreated ?? 0;

            return response()->json($responseData, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function show($id)
    {
        $delivery = Delivery::with(['sale.items.product', 'user', 'items.product'])->findOrFail($id);
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
            'from_warehouse_id' => 'nullable|exists:warehouses,id',
            'destination' => 'nullable|string',
            'notes' => 'nullable|string',
            'items' => 'nullable|array',
            'items.*.product_id' => 'required_with:items|exists:products,id',
            'items.*.quantity' => 'required_with:items|integer|min:1',
            'items.*.serial_number' => 'nullable|string|max:255',
        ]);

        DB::beginTransaction();
        try {
            // Update delivery items with serial numbers if provided
            if (!empty($validated['items'])) {
                // Update existing items or create new ones
                foreach ($validated['items'] as $item) {
                    $deliveryItem = DeliveryItem::where('delivery_id', $delivery->id)
                        ->where('product_id', $item['product_id'])
                        ->first();

                    if ($deliveryItem) {
                        $deliveryItem->update([
                            'serial_number' => $item['serial_number'] ?? $deliveryItem->serial_number,
                            'quantity' => $item['quantity'] ?? $deliveryItem->quantity,
                        ]);
                    } else {
                        DeliveryItem::create([
                            'delivery_id' => $delivery->id,
                            'product_id' => $item['product_id'],
                            'quantity' => $item['quantity'],
                            'serial_number' => $item['serial_number'] ?? null,
                        ]);
                    }
                }

                // Auto-create warranties for new serial numbers
                $warrantiesCreated = $this->autoCreateWarranties($delivery, $validated['items']);
            }

            unset($validated['items']);
            $delivery->update($validated);

            // If delivered, update sale status to completed
            if ($validated['status'] === 'delivered') {
                $delivery->delivered_date = now();
                $delivery->save();
                
                $delivery->sale->update(['status' => 'completed']);
            }

            DB::commit();

            $response = $delivery->load(['sale', 'items.product']);
            $responseData = $response->toArray();
            $responseData['warranties_created'] = $warrantiesCreated ?? 0;

            return response()->json($responseData);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Import serial numbers from Excel file for a delivery
     */
    public function importSerialNumbers(Request $request, $id)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:5120',
        ]);

        $delivery = Delivery::with(['sale.items.product', 'items'])->findOrFail($id);

        DB::beginTransaction();
        try {
            $import = new DeliverySerialImport($delivery);
            Excel::import($import, $request->file('file'));

            $importErrors = $import->getErrors();
            $importedCount = $import->getImportedCount();

            // Auto-create warranties for imported serial numbers
            $freshDelivery = $delivery->fresh();
            $updatedItems = $freshDelivery->items->map(function ($item) {
                return [
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'serial_number' => $item->serial_number,
                ];
            })->toArray();

            $warrantiesCreated = $this->autoCreateWarranties($freshDelivery, $updatedItems);

            DB::commit();

            $message = "{$importedCount} serial number berhasil diimport.";
            if ($warrantiesCreated > 0) {
                $message .= " {$warrantiesCreated} warranty otomatis dibuat.";
            }

            return response()->json([
                'message' => $message,
                'imported_count' => $importedCount,
                'warranties_created' => $warrantiesCreated,
                'delivery' => $freshDelivery->load(['items.product', 'sale']),
                'errors' => $importErrors,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Serial Import Error: ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
            return response()->json([
                'message' => 'Gagal import serial numbers: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Download serial number template for a delivery
     */
    public function downloadSerialTemplate($id)
    {
        $delivery = Delivery::with(['sale.items.product'])->findOrFail($id);

        return Excel::download(
            new DeliverySerialTemplate($delivery),
            'serial_template_' . ($delivery->tracking_number ?? $delivery->id) . '.xlsx'
        );
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
