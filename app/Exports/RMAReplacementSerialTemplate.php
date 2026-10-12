<?php

namespace App\Exports;

use App\Models\RMAShipment;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class RMAReplacementSerialTemplate implements FromArray, WithHeadings
{
    public function __construct(private RMAShipment $shipment) {}

    public function headings(): array
    {
        return ['sku', 'product_name', 'quantity', 'serial_number'];
    }

    public function array(): array
    {
        return array_fill(0, $this->shipment->quantity, [
            $this->shipment->product?->sku ?? '',
            $this->shipment->product?->title ?? '',
            1,
            '',
        ]);
    }
}
