<?php

namespace App\Services;

use App\Models\{DeliveryFulfillment, Lending, RMAShipment, Sale, StockTransaction, StockTransfer, ProjectMaterial};

class DeliveryNoteService
{
    public function data(string $key): array
    {
        abort_unless(preg_match('/^(sale|lending|rma|transfer|stock|material)-([1-9][0-9]*)$/', $key, $match), 404);
        [, $type, $id] = $match;
        $source = match ($type) {
            'sale' => Sale::with(['items.product', 'delivery.items.product', 'delivery.fromWarehouse'])->findOrFail($id),
            'rma' => RMAShipment::with(['product', 'rma.sale'])->findOrFail($id),
            'lending' => Lending::with('product')->findOrFail($id),
            'transfer' => StockTransfer::with(['product', 'fromWarehouse', 'toWarehouse'])->findOrFail($id),
            'stock' => StockTransaction::with('product')->findOrFail($id),
            'material' => ProjectMaterial::with('project')->findOrFail($id),
        };
        if ($type === 'stock') {
            abort_unless($source->transaction_type === 'out' && !in_array($source->reference_type,
                ['adjustment', 'manual_adjustment', 'stock_adjustment', 'project_defective_return', 'project_transfer_correction'], true), 404);
        }

        $fulfillment = DeliveryFulfillment::where('request_key', $key)->first();
        $shipment = $type === 'sale' ? $source->delivery : ($type === 'rma' ? $source : null);
        $sale = $type === 'sale' ? $source : ($type === 'rma' ? $source->rma?->sale : null);
        $reference = match ($type) {
            'material' => $source->project->project_code.' / MAT-'.$source->id,
            'sale' => $source->invoice_number,
            'rma' => $source->shipment_code,
            'lending' => $source->lending_code,
            'transfer' => $source->transfer_code,
            'stock' => strtoupper($source->reference_type ?: 'STK').'-'.($source->reference_id ?: $source->id),
        };
        $row = fn ($product, $quantity, $serial = null, $description = null) => [
            'product' => $product?->title ?? $product?->name ?? 'Produk tidak tersedia',
            'sku' => $product?->sku,
            'description' => $description ?? $product?->brand,
            'quantity' => (int) $quantity,
            'serial' => $serial,
        ];
        if ($type === 'sale') {
            $items = $shipment?->items->isNotEmpty() ? $shipment->items : $source->items;
            $rows = $items->map(fn ($item) => $row($item->product, $item->quantity, $item->serial_number, $item->description))->all();
        } elseif ($type === 'material') {
            $rows = [['product' => $source->name, 'sku' => null, 'description' => $source->specification, 'quantity' => $source->quantity_planned, 'serial' => null]];
        } elseif (in_array($type, ['rma', 'transfer'], true)) {
            $serials = collect($source->serial_numbers ?? [])->filter(fn ($serial) => trim((string) $serial) !== '')->values();
            $rows = $serials->map(fn ($serial) => $row($source->product, 1, $serial))->all();
            $remaining = max(0, $source->quantity - $serials->count());
            if ($remaining > 0) $rows[] = $row($source->product, $remaining);
        } else {
            $rows = [$row($source->product, $source->quantity)];
        }

        return [
            'number' => 'DN-'.strtoupper($type).'-'.str_pad($id, 6, '0', STR_PAD_LEFT),
            'reference' => $reference,
            'invoice' => $sale?->invoice_number,
            'po' => $sale?->po_number,
            'type' => ['sale' => 'Penjualan', 'rma' => 'Pengiriman RMA', 'lending' => 'Peminjaman', 'transfer' => 'Transfer Gudang', 'stock' => 'Stok Keluar', 'material' => 'Material Proyek'][$type],
            'date' => $shipment?->shipped_date ?? $fulfillment?->shipped_at ?? $source->created_at,
            'recipient' => match ($type) {
                'material' => $source->project->client_name,
                'sale' => $source->customer_name,
                'rma' => $source->recipient_name,
                'lending' => $source->borrower_name,
                'transfer' => $source->toWarehouse?->name,
                default => null,
            },
            'contact' => match ($type) {
                'material' => $source->project->client_contact,
                'sale' => $source->customer_phone,
                'rma' => $source->recipient_phone,
                'lending' => $source->borrower_contact,
                'transfer' => $source->toWarehouse?->phone,
                default => null,
            },
            'destination' => $shipment?->destination ?: ($fulfillment?->destination ?: match ($type) {
                'material' => $source->project->project_location,
                'sale' => $source->customer_address,
                'rma' => $source->recipient_address,
                'transfer' => $source->toWarehouse?->address,
                default => null,
            }),
            'warehouse' => $type === 'transfer' ? $source->fromWarehouse?->name : ($type === 'sale' ? $shipment?->fromWarehouse?->name : null),
            'courier' => $shipment?->courier ?: $fulfillment?->courier,
            'tracking' => $shipment?->tracking_number ?: $fulfillment?->tracking_number,
            'status' => $shipment?->status ?? $fulfillment?->status ?? match ($type) {
                'transfer' => $source->status === 'completed' ? 'delivered' : $source->status,
                'lending' => $source->status === 'returned' ? 'delivered' : 'preparing',
                default => 'preparing',
            },
            'notes' => $fulfillment?->notes ?? $shipment?->notes ?? $source->notes,
            'items' => $rows,
            'totalQuantity' => array_sum(array_column($rows, 'quantity')),
        ];
    }
}
