<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{RMA, RMAShipment, Warranty};
use App\Services\RMAReplacementSerialService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WarrantyClaimController extends Controller
{
    public function correctReplacement(Request $request, $id)
    {
        $data = $request->validate(['replacement_serial_numbers' => 'required|array|min:1', 'replacement_serial_numbers.*' => 'required|string|max:255|distinct:ignore_case']);
        try {
            $rma = app(\App\Services\RMAReplacementCorrectionService::class)->correct((int) $id, $data['replacement_serial_numbers']);
            return response()->json(['message' => 'SN pengganti dan stok retur diperbarui.', 'rma' => $rma]);
        } catch (\Exception $e) { return response()->json(['message' => $e->getMessage()], 422); }
    }
    public function store(Request $request, $id)
    {
        $data = $request->validate([
            'issue_date' => 'required|date',
            'notes' => 'required|string|max:5000',
            'customer_contact' => 'nullable|string|max:255',
        ]);
        return DB::transaction(function () use ($id, $data) {
            $warranty = Warranty::whereKey($id)->lockForUpdate()->firstOrFail();
            $warranty->load(['sale.invoice']);
            $sale = $warranty->sale;
            abort_unless($sale && $sale->status !== 'cancelled' && $warranty->isActiveOn($sale->invoice?->invoice_date ?? $sale->sale_date), 422, 'Garansi tidak aktif atau penjualan dibatalkan.');
            abort_unless(trim($warranty->serial_number ?? '') !== '', 422, 'SN produk harus diisi sebelum membuat klaim.');
            abort_if(RMA::where('sale_id', $sale->id)->where('product_id', $warranty->product_id)
                ->where('serial_number', $warranty->serial_number)->whereNotIn('status', ['rejected', 'completed'])->exists(), 422, 'SN ini masih memiliki klaim yang sedang diproses.');
            $rma = RMA::create([
                'rma_code' => 'RMA-'.now()->format('Ymd').'-'.strtoupper(Str::random(6)),
                'warranty_id' => $warranty->id, 'sale_id' => $sale->id,
                'sale_item_id' => $sale->items()->where('product_id', $warranty->product_id)->value('id'),
                'product_id' => $warranty->product_id, 'serial_number' => $warranty->serial_number,
                'customer_name' => $sale->customer_name,
                'customer_contact' => $data['customer_contact'] ?? $sale->customer_phone,
                'quantity' => 1, 'reason' => 'warranty_claim', 'status' => 'pending',
                'issue_date' => $data['issue_date'], 'notes' => $data['notes'],
                'warranty_claim_flow' => true, 'user_id' => auth()->id(),
            ]);
            return response()->json($rma, 201);
        });
    }

    public function inspect(Request $request, $id)
    {
        $data = $request->validate([
            'result' => 'required|in:normal,replace',
            'condition' => 'required|string|max:5000',
            'replacement_serial_numbers' => 'required_if:result,replace|array',
            'replacement_serial_numbers.*' => 'required|string|max:255|distinct:ignore_case',
        ]);
        try {
            return DB::transaction(function () use ($id, $data) {
                $rma = RMA::whereKey($id)->lockForUpdate()->firstOrFail();
                if (!in_array($rma->status, ['pending', 'approved', 'received'], true)) {
                    throw new \RuntimeException('Hasil pengecekan sudah disimpan atau klaim tidak dapat diproses.');
                }
                $rma->load('sale');
                $replace = $data['result'] === 'replace';
                $rma->update([
                    'status' => 'processed', 'received_date' => $rma->received_date ?? now(),
                    'resolution' => $replace ? 'replace' : 'repair',
                    'condition' => $data['condition'], 'condition_level' => $replace ? 'damaged_total' : 'working',
                    'replacement_requested' => $replace, 'inspected_at' => now(),
                ]);
                $shipment = RMAShipment::firstOrCreate(['rma_id' => $rma->id], [
                    'shipment_code' => 'SHIP-'. $rma->rma_code, 'product_id' => $rma->product_id,
                    'quantity' => $rma->quantity, 'status' => 'pending',
                    'recipient_name' => $rma->customer_name,
                    'recipient_address' => $rma->sale?->customer_address,
                    'recipient_phone' => $rma->customer_contact,
                    'notes' => $replace ? 'Pengiriman barang pengganti: '.$data['condition'] : 'Pengembalian barang normal: '.$data['condition'],
                    'user_id' => auth()->id(),
                ]);
                if ($replace) {
                    app(RMAReplacementSerialService::class)->replace($shipment, $data['replacement_serial_numbers']);
                } else {
                    $shipment->update(['serial_numbers' => array_map('trim', explode(',', $rma->serial_number ?? ''))]);
                }
                return response()->json(['message' => 'Hasil pengecekan disimpan. Permintaan pengiriman tersedia di Delivery.', 'rma' => $rma->fresh(), 'shipment' => $shipment->fresh()]);
            });
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
