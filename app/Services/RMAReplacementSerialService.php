<?php

namespace App\Services;

use App\Models\{Delivery, DeliveryItem, RMA, RMAShipment, Warranty};
use Illuminate\Support\Facades\DB;

class RMAReplacementSerialService
{
    public function replace(RMAShipment $shipment, array $serials): void
    {
        DB::transaction(function () use ($shipment, $serials) {
            $locked = RMAShipment::whereKey($shipment->id)->lockForUpdate()->firstOrFail();
            $rma = RMA::whereKey($locked->rma_id)->lockForUpdate()->firstOrFail();
            if ($locked->status === 'cancelled' || (!$rma->replacement_requested && $rma->resolution !== 'replace')) {
                throw new \RuntimeException('Shipment ini bukan permintaan penggantian RMA yang aktif.');
            }
            $serials = array_map(fn ($sn) => trim((string) $sn), $serials);
            if (count($serials) !== $locked->quantity ||
                count(array_unique(array_map('strtolower', $serials))) !== count($serials) ||
                collect($serials)->contains(fn ($sn) => $sn === '' || strlen($sn) > 255 || str_contains($sn, ','))) {
                throw new \RuntimeException('Isi satu SN unik per unit, sesuai jumlah barang pengganti.');
            }
            $previous = $locked->serial_numbers ?: $this->split($rma->serial_number);
            $returned = $this->split($rma->returned_serial_number ?: $rma->serial_number);
            if (array_intersect(array_map('strtolower', $serials), array_map('strtolower', $returned))) {
                throw new \RuntimeException('SN pengganti harus berbeda dari SN barang yang diretur.');
            }
            if (count($previous) !== count($serials)) {
                throw new \RuntimeException('Jumlah SN barang retur harus sesuai jumlah SN pengganti.');
            }
            $map = array_combine(array_map('strtolower', $previous), $serials);
            foreach ($serials as $serial) {
                $exists = Warranty::where('product_id', $locked->product_id)->where('serial_number', $serial)
                    ->whereNotIn('serial_number', $previous)->exists();
                if ($exists) {
                    throw new \RuntimeException("SN {$serial} sudah digunakan pada garansi lain.");
                }
            }
            $deliveryIds = Delivery::where('sale_id', $rma->sale_id)->pluck('id');
            $warrantyId = count($returned) === 1 ? Warranty::where('sale_id', $rma->sale_id)->where('product_id', $locked->product_id)->where('serial_number', $returned[0])->value('id') : null;
            foreach (DeliveryItem::whereIn('delivery_id', $deliveryIds)->where('product_id', $locked->product_id)->lockForUpdate()->get() as $item) {
                $old = $this->split($item->serial_number);
                $updated = array_map(fn ($sn) => $map[strtolower($sn)] ?? $sn, $old);
                if ($old !== $updated) $item->update(['serial_number' => implode(', ', $updated)]);
            }
            foreach (Warranty::where('sale_id', $rma->sale_id)->where('product_id', $locked->product_id)->lockForUpdate()->get() as $warranty) {
                $old = $this->split($warranty->serial_number);
                $updated = array_map(fn ($sn) => $map[strtolower($sn)] ?? $sn, $old);
                if ($old !== $updated) $warranty->update(['serial_number' => implode(', ', $updated)]);
            }
            $locked->update(['serial_numbers' => $serials]);
            app(ReturnedInventoryService::class)->register('rma', $rma->id, $rma->rma_code, $rma->product_id, $rma->quantity, $returned, $rma->condition);
            $rma->update([
                'warranty_id' => $warrantyId ?? $rma->warranty_id,
                'returned_serial_number' => $rma->returned_serial_number ?? $rma->serial_number,
                'serial_number' => implode(', ', $serials),
            ]);
            $shipment->setAttribute('serial_numbers', $serials);
        });
    }

    private function split(?string $serials): array
    {
        return array_values(array_filter(array_map('trim', explode(',', $serials ?? '')), fn ($sn) => $sn !== ''));
    }
}
