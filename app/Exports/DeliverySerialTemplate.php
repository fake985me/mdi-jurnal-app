<?php

namespace App\Exports;

use App\Models\Delivery;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DeliverySerialTemplate implements FromArray, WithHeadings, WithStyles, WithColumnWidths
{
    protected Delivery $delivery;

    public function __construct(Delivery $delivery)
    {
        $this->delivery = $delivery;
    }

    /**
     * Define the headings
     */
    public function headings(): array
    {
        return [
            'sku',
            'product_name',
            'quantity',
            'serial_number',
        ];
    }

    /**
     * Generate pre-filled rows from sale items
     */
    public function array(): array
    {
        $rows = [];

        foreach ($this->delivery->sale->items as $saleItem) {
            $product = $saleItem->product;
            
            // Create one row per quantity unit so user can fill serial number per unit
            for ($i = 0; $i < $saleItem->quantity; $i++) {
                $rows[] = [
                    $product->sku ?? '',
                    $product->title ?? '',
                    1,
                    '', // Serial number to be filled
                ];
            }
        }

        return $rows;
    }

    /**
     * Style the worksheet
     */
    public function styles(Worksheet $sheet)
    {
        // Header row styling
        $sheet->getStyle('A1:D1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '7C3AED'], // Purple
            ],
        ]);

        // Serial number column highlight (light yellow background for empty cells)
        $lastRow = count($this->delivery->sale->items) + 1;
        $totalRows = 1;
        foreach ($this->delivery->sale->items as $item) {
            $totalRows += $item->quantity;
        }

        $sheet->getStyle("D2:D{$totalRows}")->applyFromArray([
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FEFCE8'], // Light yellow
            ],
        ]);

        // Add instruction comment to serial_number header
        $sheet->getComment('D1')->getText()->createTextRun('Isi kolom ini dengan serial number untuk setiap unit produk. Serial number yang diisi akan otomatis membuat warranty.');

        return [];
    }

    /**
     * Define column widths
     */
    public function columnWidths(): array
    {
        return [
            'A' => 18,
            'B' => 35,
            'C' => 10,
            'D' => 30,
        ];
    }
}
