<?php

namespace App\Services;

use App\Models\{RMA, RMAShipment, StockTransaction};
use Illuminate\Support\Facades\DB;

class RMAReplacementCorrectionService
{
    public function correct(int $id, array $serials): RMA
    {
        return DB::transaction(function () use ($id, $serials) {
            $rma = RMA::whereKey($id)->lockForUpdate()->firstOrFail();
            if (!in_array($rma->status, ['processed', 'completed'], true)) throw new \RuntimeException('RMA harus sudah diproses untuk koreksi penggantian.');
            $shipment = RMAShipment::where('rma_id', $id)->lockForUpdate()->firstOrFail();
            if ($shipment->status === 'cancelled') throw new \RuntimeException('Pengiriman sudah dibatalkan.');
            $wasNormal = $rma->inspected_at && $rma->resolution === 'repair';
            $rma->update(['resolution' => 'replace', 'replacement_requested' => true]);
            app(RMAReplacementSerialService::class)->replace($shipment, $serials);
            if ($wasNormal && in_array($shipment->status, ['shipped', 'in_transit', 'delivered'], true) &&
                !StockTransaction::where('reference_type', 'rma_shipment')->where('reference_id', $shipment->id)->where('transaction_type', 'out')->exists()) {
                app(StockSyncService::class)->deductStock($rma->product_id, $rma->quantity, 'rma_shipment', $shipment->id, 'Koreksi penggantian '.$rma->rma_code);
            }
            return $rma->fresh();
        });
    }
}
