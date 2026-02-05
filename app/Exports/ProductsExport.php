<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProductsExport implements FromCollection, WithHeadings, WithMapping, WithColumnWidths, WithStyles
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        // Eager load productCategories with category and subCategory
        return Product::with(['productCategories.category', 'productCategories.subCategory'])->get();
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'SKU',
            'Title',
            'Subtitle',
            'Category',           // Multi-category support: pipe-separated (e.g., GPON|SWITCH)
            'Sub Category',       // Multi-subcategory support: pipe-separated (e.g., ONU|L2 SWITCH)
            'Brand',
            'Module',
            'Descriptions',
            // Specs
            'Spec1',
            'Spec2',
            'Spec3',
            'Spec4',
            'Spec5',
            'Spec6',
            'Spec7',
            // Features
            'Fitur1',
            'Fitur2',
            'Fitur3',
            'Fitur4',
            'Fitur5',
            'Fitur6',
            'Fitur7',
            'Fitur8',
            'Fitur9',
            'Fitur10',
            'Fitur11',
            'Fitur12',
            'Fitur13',
            'Fitur14',
            'Fitur15',
            // Technical Specs
            'Flash Memory',
            'SDRAM Memory',
            'Interface Main',
            'Interface1',
            'Interface2',
            'Interface3',
            'Interface4',
            'Interface5',
            'Operating Temperature',
            'Storage Temperature',
            'Operating Humidity',
            'Power1',
            'Power2',
            'Power3',
            'Power Consumptions',
            'Dimensions',
            'Diagram',
            'Network Diagram',
        ];
    }

    /**
     * @param Product $product
     * @return array
     */
    public function map($product): array
    {
        // Get multi-category data from pivot table
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
        
        // If no pivot data, fallback to single category columns
        if (empty($categories)) {
            $categories = [$product->category ?? ''];
            $subCategories = [$product->sub_category ?? ''];
        }
        
        // Join with pipe separator for multi-category
        $categoryString = implode('|', array_filter($categories));
        $subCategoryString = implode('|', $subCategories);

        return [
            $product->sku,
            $product->title,
            $product->subtitle,
            $categoryString,
            $subCategoryString,
            $product->brand,
            $product->module,
            $product->descriptions,
            // Specs
            $product->spec1,
            $product->spec2,
            $product->spec3,
            $product->spec4,
            $product->spec5,
            $product->spec6,
            $product->spec7,
            // Features
            $product->fitur1,
            $product->fitur2,
            $product->fitur3,
            $product->fitur4,
            $product->fitur5,
            $product->fitur6,
            $product->fitur7,
            $product->fitur8,
            $product->fitur9,
            $product->fitur10,
            $product->fitur11,
            $product->fitur12,
            $product->fitur13,
            $product->fitur14,
            $product->fitur15,
            // Technical Specs
            $product->flash_memory,
            $product->sdram_memory,
            $product->interface_main,
            $product->interface1,
            $product->interface2,
            $product->interface3,
            $product->interface4,
            $product->interface5,
            $product->operating_temperature,
            $product->storage_temperature,
            $product->operating_humidity,
            $product->power1,
            $product->power2,
            $product->power3,
            $product->power_consumptions,
            $product->dimensions,
            $product->diagram,
            $product->network_diagram,
        ];
    }

    /**
     * @return array
     */
    public function columnWidths(): array
    {
        return [
            'A' => 20,  // SKU
            'B' => 30,  // Title
            'C' => 25,  // Subtitle
            'D' => 25,  // Category (wider for multi-category)
            'E' => 30,  // Sub Category (wider for multi-subcategory)
            'F' => 15,  // Brand
            'G' => 10,  // Module
            'H' => 50,  // Descriptions
        ];
    }

    /**
     * @param Worksheet $sheet
     * @return array
     */
    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold text
            1 => ['font' => ['bold' => true]],
        ];
    }
}
