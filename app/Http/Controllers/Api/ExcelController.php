<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Exports\ProductsExport;
use App\Imports\ProductsImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ExcelController extends Controller
{
    /**
     * Export products to Excel
     */
    public function exportProducts()
    {
        return Excel::download(new ProductsExport, 'products_' . date('Y-m-d_His') . '.xlsx');
    }

    /**
     * Import products from Excel
     */
    public function importProducts(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:5120', // Max 5MB
        ]);

        try {
            $import = new ProductsImport();
            Excel::import($import, $request->file('file'));

            // Check if there were any failures
            $failures = $import->failures();
            
            if ($failures->count() > 0) {
                $errors = [];
                foreach ($failures as $failure) {
                    $errors[] = [
                        'row' => $failure->row(),
                        'attribute' => $failure->attribute(),
                        'errors' => $failure->errors(),
                    ];
                }

                return response()->json([
                    'message' => 'Some rows failed to import',
                    'errors' => $errors,
                ], 422);
            }

            return response()->json([
                'message' => 'Products imported successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Import failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export sales to Excel
     */
    public function exportSales()
    {
        // This can be implemented later if needed
        return response()->json([
            'message' => 'Sales export not yet implemented',
        ], 501);
    }
}
