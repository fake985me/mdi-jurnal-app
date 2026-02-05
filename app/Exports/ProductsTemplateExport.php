<?php

namespace App\Exports;

use App\Models\Category;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProductsTemplateExport implements WithMultipleSheets
{
    /**
     * @return array
     */
    public function sheets(): array
    {
        return [
            'Products' => new ProductsTemplateSheet(),
            'Categories Reference' => new CategoriesReferenceSheet(),
        ];
    }
}

/**
 * Main template sheet with example data
 */
class ProductsTemplateSheet implements FromArray, WithHeadings, WithColumnWidths, WithStyles
{
    /**
     * @return array
     */
    public function array(): array
    {
        // Return example rows showing multi-category format
        return [
            [
                'EXAMPLE-001',              // SKU
                'Example Product Single',   // Title
                'Single Category Example',  // Subtitle
                'GPON',                     // Category (single)
                'ONT',                      // Sub Category (single)
                'DASAN',                    // Brand
                'A',                        // Module
                'Product with single category', // Descriptions
                '', '', '', '', '', '', '', // Specs
                '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', // Features
                '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '' // Technical
            ],
            [
                'EXAMPLE-002',              // SKU
                'Example Product Multi',    // Title
                'Multi Category Example',   // Subtitle
                'GPON|SWITCH',              // Category (multi with pipe separator)
                'ONU|L2 SWITCH',            // Sub Category (multi with pipe separator)
                'DASAN',                    // Brand
                'A',                        // Module
                'Product with multiple categories (GPON>ONU and SWITCH>L2 SWITCH)', // Descriptions
                '', '', '', '', '', '', '', // Specs
                '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', // Features
                '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '' // Technical
            ],
        ];
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
            'Category',           // Use pipe (|) separator for multiple categories
            'Sub Category',       // Use pipe (|) separator for multiple subcategories
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
            'H' => 60,  // Descriptions
        ];
    }

    /**
     * @param Worksheet $sheet
     * @return array
     */
    public function styles(Worksheet $sheet)
    {
        // Add instructions as comment in header
        $sheet->getComment('D1')->getText()->createTextRun('Use pipe (|) separator for multiple categories. Example: GPON|SWITCH');
        $sheet->getComment('E1')->getText()->createTextRun('Use pipe (|) separator for multiple subcategories. Order must match categories. Example: ONU|L2 SWITCH');
        
        return [
            // Style the first row as bold text with background
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E2EFDA']
                ]
            ],
            // Style example rows with light yellow background
            2 => [
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'FFFFD0']
                ]
            ],
            3 => [
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'FFFFD0']
                ]
            ],
        ];
    }
}

/**
 * Reference sheet listing all valid categories and subcategories
 */
class CategoriesReferenceSheet implements FromArray, WithHeadings, WithColumnWidths, WithStyles
{
    /**
     * @return array
     */
    public function array(): array
    {
        $data = [];
        
        // Fetch all categories with their subcategories
        $categories = Category::with('subCategories')->orderBy('name')->get();
        
        foreach ($categories as $category) {
            if ($category->subCategories && $category->subCategories->count() > 0) {
                foreach ($category->subCategories as $subCategory) {
                    $data[] = [$category->name, $subCategory->name];
                }
            } else {
                $data[] = [$category->name, '(no subcategories)'];
            }
        }
        
        return $data;
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return ['Category', 'Sub Category'];
    }

    /**
     * @return array
     */
    public function columnWidths(): array
    {
        return [
            'A' => 25,
            'B' => 30,
        ];
    }

    /**
     * @param Worksheet $sheet
     * @return array
     */
    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'B4C6E7']
                ]
            ],
        ];
    }
}
