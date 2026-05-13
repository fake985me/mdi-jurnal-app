<?php

namespace App\Imports;

use App\Models\Delivery;
use App\Models\DeliveryItem;
use App\Models\Product;
use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Log;

class DeliverySerialImport implements ToArray, WithHeadingRow
{
    protected Delivery $delivery;
    protected array $errors = [];
    protected int $importedCount = 0;

    public function __construct(Delivery $delivery)
    {
        $this->delivery = $delivery;
    }

    /**
     * Process the imported array
     */
    public function array(array $rows)
    {
        // Ensure sale items are loaded
        if (!$this->delivery->relationLoaded('sale') || !$this->delivery->sale->relationLoaded('items')) {
            $this->delivery->load('sale.items.product');
        }

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2; // +2 because of header row and 0-index

            // Normalize heading keys — Maatwebsite lowercases and replaces spaces with underscores
            $serialNumber = trim($row['serial_number'] ?? '');
            if (empty($serialNumber)) {
                continue; // Skip rows without serial number
            }

            // Find product by SKU or product_name
            $product = null;
            $sku = trim($row['sku'] ?? '');
            $productName = trim($row['product_name'] ?? '');

            if (!empty($sku)) {
                $product = Product::where('sku', $sku)->first();
            }
            if (!$product && !empty($productName)) {
                // Try exact match first, then like match
                $product = Product::where('title', $productName)->first();
                if (!$product) {
                    $product = Product::where('title', 'like', '%' . $productName . '%')->first();
                }
            }

            if (!$product) {
                $this->errors[] = "Baris {$rowNumber}: Produk tidak ditemukan (SKU: " . ($sku ?: '-') . ", Nama: " . ($productName ?: '-') . ")";
                continue;
            }

            // Check if this product is in the sale
            $saleItem = $this->delivery->sale->items->where('product_id', $product->id)->first();
            if (!$saleItem) {
                $this->errors[] = "Baris {$rowNumber}: Produk '{$product->title}' tidak ada di sale ini";
                continue;
            }

            // Check if serial number already exists for this delivery+product
            $existingWithSN = DeliveryItem::where('delivery_id', $this->delivery->id)
                ->where('product_id', $product->id)
                ->where('serial_number', $serialNumber)
                ->exists();

            if ($existingWithSN) {
                $this->errors[] = "Baris {$rowNumber}: Serial number '{$serialNumber}' sudah ada untuk produk '{$product->title}'";
                continue;
            }

            // Find existing delivery item without serial number to update
            $deliveryItem = DeliveryItem::where('delivery_id', $this->delivery->id)
                ->where('product_id', $product->id)
                ->whereNull('serial_number')
                ->first();

            if ($deliveryItem) {
                // Update existing item that has no serial number yet
                $deliveryItem->update(['serial_number' => $serialNumber]);
                $this->importedCount++;
            } else {
                // Create new delivery item for this serial number
                DeliveryItem::create([
                    'delivery_id' => $this->delivery->id,
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'serial_number' => $serialNumber,
                ]);
                $this->importedCount++;
            }
        }

        Log::info("DeliverySerialImport: Imported {$this->importedCount} serial numbers for delivery #{$this->delivery->id}");
    }

    /**
     * Get import errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get count of imported serial numbers
     */
    public function getImportedCount(): int
    {
        return $this->importedCount;
    }
}
