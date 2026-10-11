<?php

namespace App\Exports;

use App\Models\Category;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class ProductsTemplateExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            'Products'             => new ProductsTemplateSheet(),
            'Kategori & Referensi' => new CategoriesReferenceSheet(),
        ];
    }
}

class ProductsTemplateSheet implements FromArray, WithHeadings, WithColumnWidths, WithStyles
{
    public function array(): array
    {
        return [
            // Sample Row 1
            [
                '', // id (leave blank for new product)
                'A', // module
                'https://ik.imagekit.io/...', // image
                'V8208', // SKU
                'V8208', // title
                'XGSPON OLT', // subtitle
                'XGSPON', // category (use | for multi-category: e.g. GPON | SWITCH)
                'OLT', // subCategory (use | for multi-subcategory: e.g. ONU | L2 SWITCH)
                'dasan', // brand
                '10', // Stock
                '12', // Warranty period in months
                'Chassis PON Optical Line Termination', // descriptions
                '8 port GPON/XGSPON Combo', // spec1
                '16p GPON/XGSPON Combo', // spec2
                '8p 10GE', // spec3
                'DC', // spec4
                'up to 96PON', // spec5
                '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', // spec6-20
                '8-port GPON interfaces per slot', // fitur1
                '8-port XGS-PON interfaces per slot', // fitur2
                '8-port 10GE interface per slot', // fitur3
                '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', // fitur4-20
                '', '', '', '', '', '', '', // wireless
                '', '', '', '', '', '', '', // capacity / switch
                '8MB(Boot) + 512MB(NOS)', // flashmemory
                '4GB (DDR4)', // sdrammemory
                '', // Interface
                'IU_GPON8 for GPON(SFP)', // Interface1
                'IU_XGSPON8 for GPON/XGS-PON Combo(SFP+)', // Interface2
                'IU_10GE8 for 10GE(SFP+)', // Interface3
                '', '', // Interface4, 5
                '', // antena
                '', '', '', '', '', '', // cu, cu1-5
                '', '', '', '', '', // aditionalinterface1-5
                '', '', '', '', '', // mac, routing, dustproof, noise, mtbf
                '0 ~ 50°C', // operatingtemperature
                '-20 ~ 70°C', // storagetemperature
                '20 ~ 80%', // operatinghumidity
                '-48VDC', '', '', '', '', // power1-5
                '875W', // powercomsumptions
                '', '', '', // withPoE, ExcludingPoE, PoEBudget
                '443.8 x 265.9 x 280 mm', // dimensions
                'xgspon_olt', // diagram (use | for multi: xgspon_olt | gpon_olt)
                'xgsponLine', // networkDIagram (use | for multi: xgsponLine | gponline)
            ],
        ];
    }

    public function headings(): array
    {
        $headers = [
            'id',
            'module',
            'image',
            'SKU',
            'title',
            'subtitle',
            'category',
            'subCategory',
            'brand',
            'Stock',
            'warranty_period_months',
            'descriptions',
        ];

        for ($i = 1; $i <= 20; $i++) {
            $headers[] = "spec{$i}";
        }

        for ($i = 1; $i <= 20; $i++) {
            $headers[] = "fitur{$i}";
        }

        $headers = array_merge($headers, [
            'wirelessStandard',
            'wirelessStream',
            'wirelessStream1',
            'wirelessStream2',
            'wirelessStream3',
            'wirelessStream4',
            'wirelessStream5',
            'manageableAps',
            'APnumber',
            'numberOfClients',
            'capacity',
            'iprating',
            'switching',
            'throughput',
            'flashmemory',
            'sdrammemory',
            'Interface',
            'Interface1',
            'Interface2',
            'Interface3',
            'Interface4',
            'Interface5',
            'antena',
            'cu',
            'cu1',
            'cu2',
            'cu3',
            'cu4',
            'cu5',
            'aditionalinterface1',
            'aditionalinterface2',
            'aditionalinterface3',
            'aditionalinterface4',
            'aditionalinterface5',
            'macaddress',
            'routingtable',
            'dustproofwaterproof',
            'noise',
            'mtbf',
            'operatingtemperature',
            'storagetemperature',
            'operatinghumidity',
            'power1',
            'power2',
            'power3',
            'power4',
            'power5',
            'powercomsumptions',
            'PowerConsumption(withPoE)',
            'PowerConsumption(ExcludingPoE)',
            'PoEBudget',
            'dimensions',
            'diagram',
            'networkDIagram',
        ]);

        return $headers;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 8,
            'B' => 10,
            'C' => 30,
            'D' => 15,
            'E' => 25,
            'F' => 20,
            'G' => 20,
            'H' => 20,
            'I' => 15,
            'J' => 10,
            'K' => 20,
            'L' => 35,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => '1A3A1A']],
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'C6EFCE'],
                ],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }
}

class CategoriesReferenceSheet implements FromArray, WithHeadings, WithColumnWidths, WithStyles
{
    public function array(): array
    {
        $data = [];
        $categories = Category::with('subCategories')->orderBy('name')->get();

        foreach ($categories as $category) {
            if ($category->subCategories && $category->subCategories->count() > 0) {
                foreach ($category->subCategories as $sub) {
                    $data[] = [$category->name, $sub->name];
                }
            } else {
                $data[] = [$category->name, '(tidak ada sub-kategori)'];
            }
        }

        return $data;
    }

    public function headings(): array
    {
        return ['Category', 'Sub Category'];
    }

    public function columnWidths(): array
    {
        return ['A' => 25, 'B' => 30];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'B4C6E7'],
                ],
            ],
        ];
    }
}
