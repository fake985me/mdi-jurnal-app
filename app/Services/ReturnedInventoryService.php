<?php

namespace App\Services;

use App\Models\ReturnedItem;

class ReturnedInventoryService
{
    public function register(string $type, int $id, ?string $code, int $productId, int $quantity, array $serials = [], ?string $notes = null): void
    {
        $serials = array_values(array_filter(array_map('trim', $serials), fn ($sn) => $sn !== ''));
        if ($serials && count($serials) !== $quantity) throw new \RuntimeException('Jumlah SN retur tidak sesuai quantity.');
        foreach ($serials ?: [null] as $index => $serial) {
            $item = ReturnedItem::firstOrCreate(['source_key' => $type.'-'.$id.'-'.$index], [
                'source_type' => $type, 'source_id' => $id, 'source_code' => $code,
                'product_id' => $productId, 'serial_number' => $serial,
                'quantity' => $serials ? 1 : $quantity, 'condition' => 'defective',
                'available_quantity' => 0, 'book_value' => 0, 'notes' => $notes,
            ]);
            if ($serial && !$item->serial_number && $item->condition === 'defective' && !$item->available_quantity) {
                $item->update(['serial_number' => $serial, 'quantity' => 1]);
            }
        }
    }
}
