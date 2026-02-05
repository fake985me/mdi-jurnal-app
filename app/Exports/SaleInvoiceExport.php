<?php

namespace App\Exports;

use App\Models\Sale;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class SaleInvoiceExport implements FromArray, WithStyles, WithColumnWidths
{
    protected $sale;

    public function __construct($saleId)
    {
        $this->sale = Sale::with(['items.product', 'salesPerson', 'user'])
            ->findOrFail($saleId);
    }

    public function array(): array
    {
        $data = [];
        
        // Row 1: Empty
        $data[] = [''];
        
        // Row 2: INVOICE Title (centered, merged)
        $data[] = ['', '', 'INVOICE'];
        
        // Row 3: Subtitle
        $data[] = ['', '', 'MDI Warehouse Management System'];
        
        // Row 4: Empty
        $data[] = [''];
        
        // Row 5: Horizontal separator (will be styled)
        $data[] = [''];
        
        // Row 6: Empty
        $data[] = [''];
        
        // Row 7-10: Invoice Details (2 columns layout)
        $data[] = [
            'Invoice Number:', 
            $this->sale->invoice_number, 
            '', 
            'Date:', 
            \Carbon\Carbon::parse($this->sale->sale_date)->format('d F Y')
        ];
        
        $data[] = [
            'Customer Name:', 
            $this->sale->customer_name, 
            '', 
            'Status:', 
            strtoupper($this->sale->status)
        ];
        
        $data[] = [
            'Phone:', 
            $this->sale->customer_phone ?? '-', 
            '', 
            'Sales Person:', 
            $this->sale->salesPerson->name ?? '-'
        ];
        
        $data[] = [
            'Email:', 
            $this->sale->customer_email ?? '-'
        ];
        
        // Row 11: Empty
        $data[] = [''];
        
        // Row 12: Empty
        $data[] = [''];
        
        // Row 13: Table Header
        $data[] = ['#', 'Product', 'Quantity', 'Unit Price', 'Subtotal'];
        
        // Rows 14+: Items
        foreach ($this->sale->items as $index => $item) {
            $data[] = [
                $index + 1,
                $item->product->title ?? $item->product->name,
                $item->quantity,
                'Rp ' . number_format($item->unit_price, 0, ',', '.'),
                'Rp ' . number_format($item->subtotal, 0, ',', '.')
            ];
        }
        
        // Empty row after items
        $data[] = [''];
        
        // Separator row
        $data[] = [''];
        
        // Total row
        $totalRow = count($data) + 1;
        $data[] = ['', '', '', 'TOTAL:', 'Rp ' . number_format($this->sale->total_amount, 0, ',', '.')];
        
        // Empty rows
        $data[] = [''];
        $data[] = [''];
        $data[] = [''];
        
        // Footer
        $data[] = ['', '', 'Thank you for your business!'];
        $data[] = ['', '', 'Generated on ' . now()->format('d F Y - H:i')];
        
        return $data;
    }

    public function styles(Worksheet $sheet)
    {
        $itemsStartRow = 14;
        $itemsEndRow = 14 + count($this->sale->items) - 1;
        $totalRow = $itemsEndRow + 3;
        
        return [
            // Title "INVOICE" - Row 2
            2 => [
                'font' => [
                    'bold' => true,
                    'size' => 24,
                    'color' => ['rgb' => '4F46E5']
                ],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ],
            
            // Subtitle - Row 3
            3 => [
                'font' => ['size' => 12, 'color' => ['rgb' => '666666']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ],
            
            // Info labels (bold)
            'A7:A10' => [
                'font' => ['bold' => true, 'color' => ['rgb' => '333333']]
            ],
            'D7:D9' => [
                'font' => ['bold' => true, 'color' => ['rgb' => '333333']]
            ],
            
            // Table Header - Row 13
            13 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF']
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4F46E5']
                ],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ],
            
            // Items rows
            "A{$itemsStartRow}:E{$itemsEndRow}" => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'DDDDDD']
                    ]
                ]
            ],
            
            // Total label and value
            $totalRow => [
                'font' => [
                    'bold' => true,
                    'size' => 16,
                    'color' => ['rgb' => '4F46E5']
                ],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT]
            ],
            
            // Footer messages
            ($totalRow + 4) => [
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'font' => ['size' => 11]
            ],
            ($totalRow + 5) => [
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'font' => ['size' => 9, 'color' => ['rgb' => '999999']]
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 8,
            'B' => 35,
            'C' => 12,
            'D' => 18,
            'E' => 20,
        ];
    }
}
