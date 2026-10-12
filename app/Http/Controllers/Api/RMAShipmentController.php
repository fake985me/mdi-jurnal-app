<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RMA;
use App\Models\RMAShipment;
use App\Services\StockSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\RMAReplacementSerialImport;
use App\Exports\RMAReplacementSerialTemplate;

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
            $rma = RMA::whereKey($validated['rma_id'])->lockForUpdate()->firstOrFail();

            if ($rma->status !== 'processed') {
                DB::rollBack();
                return response()->json([
                    'message' => 'RMA harus dalam status processed untuk membuat shipment'
                ], 422);
            }

            if ($rma->resolution !== 'replace') {
                DB::rollBack();
                return response()->json([
                    'message' => 'Hanya RMA dengan resolution replace yang bisa dibuat shipment'
                ], 422);
            }

            $rma->loadMissing('sale');
            $existingShipment = RMAShipment::where('rma_id', $rma->id)->first();
            if ($existingShipment) {
                DB::commit();
                return response()->json($existingShipment->load(['rma', 'product', 'user']));
            }

            $shipment = RMAShipment::create([
                'shipment_code' => RMAShipment::generateCode(),
                'rma_id' => $validated['rma_id'],
                'product_id' => $rma->product_id,
                'quantity' => $rma->quantity,
                'recipient_name' => $rma->customer_name,
                'recipient_address' => $rma->sale?->customer_address ?? $validated['recipient_address'],
                'recipient_phone' => $rma->customer_contact ?: ($rma->sale?->customer_phone ?? $validated['recipient_phone']),
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
        $shipment = RMAShipment::with(['rma.sale', 'product', 'user'])->findOrFail($id);
        return response()->json($shipment);
    }

    public function updateStatus(Request $request, $id)
    {
        $data = $request->validate([
            'status' => 'required|in:preparing,shipped,in_transit,delivered,cancelled',
            'tracking_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);
        try {
            return DB::transaction(function () use ($request, $id, $data) {
                $shipment = RMAShipment::whereKey($id)->lockForUpdate()->firstOrFail();
                $target = $data['status'] === 'preparing' ? 'pending' : $data['status'];
                if ($target !== $shipment->status) {
                    if (in_array($shipment->status, ['delivered', 'cancelled'], true) ||
                        ($target === 'pending' && $shipment->status !== 'pending') ||
                        ($target === 'shipped' && $shipment->status === 'in_transit') ||
                        ($target === 'cancelled' && $shipment->status !== 'pending')) {
                        throw new \RuntimeException('Perubahan status ini tidak dapat dilakukan untuk shipment yang sudah diproses.');
                    }
                    if ($shipment->status === 'pending' && in_array($target, ['shipped', 'in_transit', 'delivered'], true)) {
                        $response = $this->ship($request, $id, false);
                        if ($response->status() >= 400) {
                            throw new \RuntimeException($response->getData()->message);
                        }
                        $shipment->refresh();
                    }
                    if ($target === 'delivered') {
                        $response = $this->markDelivered($id);
                        if ($response->status() >= 400) {
                            throw new \RuntimeException($response->getData()->message);
                        }
                        $shipment->refresh();
                    } else {
                        $shipment->status = $target;
                    }
                }
                $shipment->fill(collect($data)->except('status')->all())->save();
                return response()->json($shipment->load(['rma', 'product']));
            });
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function downloadSerialTemplate($id)
    {
        $shipment = RMAShipment::with('product')->findOrFail($id);
        return Excel::download(new RMAReplacementSerialTemplate($shipment), 'serial_template_'.$shipment->shipment_code.'.xlsx');
    }

    public function importSerialNumbers(Request $request, $id)
    {
        $request->validate(['file' => 'required|mimes:xlsx,xls,csv|max:5120']);
        try {
            return DB::transaction(function () use ($request, $id) {
                $shipment = RMAShipment::whereKey($id)->lockForUpdate()->firstOrFail();
                $import = new RMAReplacementSerialImport($shipment);
                Excel::import($import, $request->file('file'));
                return response()->json([
                    'message' => count($shipment->serial_numbers ?? []).' SN pengganti berhasil disimpan dan diperbarui pada pengiriman asal serta garansi.',
                    'shipment' => $shipment->fresh(['rma.sale', 'product']),
                    'errors' => [],
                ]);
            });
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function ship(Request $request, $id, bool $requireShippingDetails = true)
    {
        $validated = $request->validate([
            'tracking_number' => ($requireShippingDetails ? 'required' : 'nullable').'|string|max:100',
            'courier' => ($requireShippingDetails ? 'required' : 'nullable').'|string|max:100',
        ]);

        DB::beginTransaction();
        try {
            $shipment = RMAShipment::whereKey($id)->lockForUpdate()->firstOrFail();

            if ($shipment->status !== 'pending') {
                throw new \RuntimeException('Shipment hanya bisa dikonfirmasi satu kali saat berstatus pending');
            }

            $rma = RMA::whereKey($shipment->rma_id)->lockForUpdate()->firstOrFail();
            if ($rma->status !== 'processed' || $rma->resolution !== 'replace') {
                throw new \RuntimeException('RMA harus diproses dengan resolution replace sebelum shipment dikonfirmasi');
            }

            $this->stockService->deductStock(
                $shipment->product_id,
                $shipment->quantity,
                'rma_shipment',
                $shipment->id,
                "RMA Shipment: {$shipment->shipment_code} - Replacement sent"
            );

            $shipment->update([
                'status' => 'shipped',
                'tracking_number' => $validated['tracking_number'] ?? $shipment->tracking_number,
                'courier' => $validated['courier'] ?? $shipment->courier,
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
            $shipment = RMAShipment::whereKey($id)->lockForUpdate()->firstOrFail();

            if (!in_array($shipment->status, ['shipped', 'in_transit'], true)) {
                throw new \RuntimeException('Hanya shipment yang sudah dikirim yang dapat ditandai delivered');
            }

            $shipment->update([
                'status' => 'delivered',
                'delivered_date' => now(),
            ]);

            $shipment->rma()->update([
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
