<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RMA;
use App\Models\RMAShipment;
use App\Services\StockSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RMAShipmentController extends Controller
{
    public function __construct(private StockSyncService $stockService) {}
    public function index(Request $request)
    {
        try {
            $query = RMAShipment::with(['rma.product', 'product', 'user']);

            if ($request->has('status') && !empty($request->status)) {
                $query->where('status', $request->status);
            }

            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('shipment_code', 'like', "%{$search}%")
                      ->orWhere('tracking_number', 'like', "%{$search}%")
                      ->orWhere('recipient_name', 'like', "%{$search}%");
                });
            }

            $shipments = $query->orderBy('created_at', 'desc')
                ->paginate($request->per_page ?? 15);

            return response()->json($shipments);
        } catch (\Exception $e) {
            \Log::error('RMA Shipments API Error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error loading shipments',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'rma_id' => 'required|exists:rmas,id',
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'recipient_name' => 'required|string|max:255',
            'recipient_address' => 'required|string',
            'recipient_phone' => 'required|string|max:50',
            'courier' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $rma = RMA::findOrFail($validated['rma_id']);
            
            if (!in_array($rma->status, ['processed'])) {
                return response()->json([
                    'message' => 'RMA harus dalam status processed untuk membuat shipment'
                ], 422);
            }

            if ($rma->resolution !== 'replace') {
                return response()->json([
                    'message' => 'Hanya RMA dengan resolution replace yang bisa dibuat shipment'
                ], 422);
            }

            $stockService = $this->stockService;
            
            if (!$stockService->hasStock($validated['product_id'], $validated['quantity'])) {
                throw new \Exception("Stock tidak mencukupi untuk shipment");
            }

            $shipment = RMAShipment::create([
                'shipment_code' => RMAShipment::generateCode(),
                'rma_id' => $validated['rma_id'],
                'product_id' => $validated['product_id'],
                'quantity' => $validated['quantity'],
                'recipient_name' => $validated['recipient_name'],
                'recipient_address' => $validated['recipient_address'],
                'recipient_phone' => $validated['recipient_phone'],
                'courier' => $validated['courier'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'status' => 'pending',
                'user_id' => auth()->id(),
            ]);

            DB::commit();
            return response()->json($shipment->load(['rma', 'product', 'user']), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function show($id)
    {
        $shipment = RMAShipment::with(['rma', 'product', 'user'])->findOrFail($id);
        return response()->json($shipment);
    }

    public function ship(Request $request, $id)
    {
        $validated = $request->validate([
            'tracking_number' => 'required|string|max:100',
            'courier' => 'required|string|max:100',
        ]);

        DB::beginTransaction();
        try {
            $shipment = RMAShipment::findOrFail($id);
            $stockService = $this->stockService;

            $stockService->deductStock(
                $shipment->product_id,
                $shipment->quantity,
                'rma_shipment',
                $shipment->id,
                "RMA Shipment: {$shipment->shipment_code} - Replacement sent"
            );

            $shipment->update([
                'status' => 'shipped',
                'tracking_number' => $validated['tracking_number'],
                'courier' => $validated['courier'],
                'shipped_date' => now(),
            ]);

            DB::commit();
            return response()->json($shipment->load(['rma', 'product', 'user']));
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function markDelivered($id)
    {
        DB::beginTransaction();
        try {
            $shipment = RMAShipment::findOrFail($id);

            $shipment->update([
                'status' => 'delivered',
                'delivered_date' => now(),
            ]);

            $shipment->rma->update([
                'status' => 'completed'
            ]);

            DB::commit();
            return response()->json($shipment->load(['rma', 'product', 'user']));
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
