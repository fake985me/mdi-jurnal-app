<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProductsExport implements FromCollection, WithColumnWidths, WithHeadings, WithMapping, WithStyles
{
    public function collection()
    {
        return Product::with(['productCategories.category', 'productCategories.subCategory'])
            ->withSum('currentStocks', 'quantity')
            ->orderBy('id')
            ->get();
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

    /**
     * @param Product $product
     */
    public function map($product): array
    {
        // Category & SubCategory from pivot or string
        $categories = [];
        $subCategories = [];

        if ($product->productCategories && $product->productCategories->count() > 0) {
            foreach ($product->productCategories as $pc) {
                if ($pc->category) {
                    $categories[] = $pc->category->name;
                    $subCategories[] = $pc->subCategory ? $pc->subCategory->name : '';
                }
            }
        }

        if (empty($categories)) {
            $categoryString = $product->category ?? '';
            $subCategoryString = $product->sub_category ?? '';
        } else {
            $categoryString = implode(' | ', array_filter($categories));
            $subCategoryString = implode(' | ', array_filter($subCategories));
        }

        $stockQty = (int) ($product->current_stocks_sum_quantity ?? $product->stock ?? 0);

        $row = [
            $product->id,
            $product->module ?? 'A',
            $product->image,
            $product->sku,
            $product->title,
            $product->subtitle,
            $categoryString,
            $subCategoryString,
            $product->brand,
            $stockQty,
            $product->descriptions,
        ];

        // Specs 1-20
        for ($i = 1; $i <= 20; $i++) {
            $row[] = $product->{"spec{$i}"};
        }

        // Features 1-20
        for ($i = 1; $i <= 20; $i++) {
            $row[] = $product->{"fitur{$i}"};
        }

        // Technical specs
        $row = array_merge($row, [
            $product->wireless_standard,
            $product->wireless_stream,
            $product->wireless_stream1,
            $product->wireless_stream2,
            $product->wireless_stream3,
            $product->wireless_stream4,
            $product->wireless_stream5,
            $product->manageable_aps,
            $product->ap_number,
            $product->number_of_clients,
            $product->capacity,
            $product->ip_rating,
            $product->switching,
            $product->throughput,
            $product->flash_memory,
            $product->sdram_memory,
            $product->interface_main,
            $product->interface1,
            $product->interface2,
            $product->interface3,
            $product->interface4,
            $product->interface5,
            $product->antena,
            $product->cu,
            $product->cu1,
            $product->cu2,
            $product->cu3,
            $product->cu4,
            $product->cu5,
            $product->additional_interface1,
            $product->additional_interface2,
            $product->additional_interface3,
            $product->additional_interface4,
            $product->additional_interface5,
            $product->mac_address,
            $product->routing_table,
            $product->dustproof_waterproof,
            $product->noise,
            $product->mtbf,
            $product->operating_temperature,
            $product->storage_temperature,
            $product->operating_humidity,
            $product->power1,
            $product->power2,
            $product->power3,
            $product->power4,
            $product->power5,
            $product->power_consumptions,
            $product->power_consumption_with_poe,
            $product->power_consumption_excluding_poe,
            $product->poe_budget,
            $product->dimensions,
            $product->diagram,
            $product->network_diagram,
        ]);

        return $row;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 8,   // id
            'B' => 10,  // module
            'C' => 30,  // image
            'D' => 15,  // SKU
            'E' => 25,  // title
            'F' => 20,  // subtitle
            'G' => 20,  // category
            'H' => 20,  // subCategory
            'I' => 15,  // brand
            'J' => 10,  // Stock
            'K' => 40,  // descriptions
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
