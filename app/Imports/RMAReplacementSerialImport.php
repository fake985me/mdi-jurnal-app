<?php

namespace App\Imports;

use App\Models\RMAShipment;
use App\Services\RMAReplacementSerialService;
use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class RMAReplacementSerialImport implements ToArray, WithHeadingRow
{
    public function __construct(private RMAShipment $shipment) {}

    public function array(array $rows)
    {
        $serials = [];
        $this->shipment->loadMissing('product');
        foreach ($rows as $row) {
            $serial = trim((string) ($row['serial_number'] ?? ''));
            if ($serial === '') continue;
            $sku = trim((string) ($row['sku'] ?? ''));
            $name = trim((string) ($row['product_name'] ?? ''));
            if (($sku !== '' && $sku !== $this->shipment->product?->sku) ||
                ($sku === '' && $name !== $this->shipment->product?->title)) {
                throw new \RuntimeException('Produk pada file tidak sesuai dengan barang pengganti RMA.');
            }
            $serials[] = $serial;
        }
        app(RMAReplacementSerialService::class)->replace($this->shipment, $serials);
    }
}
