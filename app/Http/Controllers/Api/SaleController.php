<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Product;
use App\Models\CurrentStock;
use App\Models\Payment;
use App\Models\StockTransaction;
use App\Models\TaxRate;
use App\Models\Warehouse;
use App\Services\StockSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    /**
     * Generate sequential invoice number
     */
    private function generateInvoiceNumber()
    {
        // Format: INV-YYYYMMDD-XXXX
        // Example: INV-20251210-0001
        $today = now()->format('Ymd');
        $prefix = "INV-{$today}-";
        
        // Find last invoice today
        $lastSale = Sale::where('invoice_number', 'like', "{$prefix}%")
            ->orderBy('invoice_number', 'desc')
            ->first();
        
        if ($lastSale) {
            // Get last number and increment
            $lastNumber = (int) substr($lastSale->invoice_number, -4);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }
        
        return $prefix . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    public function index(Request $request)
    {
        try {
            $query = Sale::with(['salesPerson', 'user', 'items.product', 'delivery', 'warehouse', 'customer']);

            // Filter by status
            if ($request->has('status') && !empty($request->status)) {
                $query->where('status', $request->status);
            }

            // Filter by date range
            if ($request->has('start_date') && !empty($request->start_date)) {
                $query->whereDate('sale_date', '>=', $request->start_date);
            }
            if ($request->has('end_date') && !empty($request->end_date)) {
                $query->whereDate('sale_date', '<=', $request->end_date);
            }

            // Search
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('invoice_number', 'like', "%{$search}%")
                      ->orWhere('customer_name', 'like', "%{$search}%");
                });
            }

            $sales = $query->orderBy('created_at', 'desc')
                ->paginate($request->per_page ?? 15);

            return response()->json($sales);
        } catch (\Exception $e) {
            \Log::error('Sales API Error: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'message' => 'Error loading sales',
                'error' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'invoice_number' => 'nullable|unique:sales,invoice_number',
            'customer_id' => 'nullable|exists:customers,id',
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'nullable|email',
            'customer_phone' => 'nullable|string',
            'customer_address' => 'nullable|string',
            'sales_person_id' => 'nullable|exists:sales_people,id',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'sale_date' => 'required|date',
            'status' => 'required|in:pending,completed,cancelled',
            'notes' => 'nullable|string',
            'tax_type' => 'nullable|string',
            'discount_amount' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            // Auto-generate invoice number if not provided
            $invoiceNumber = $validated['invoice_number'] ?? $this->generateInvoiceNumber();
            $warehouseId = $validated['warehouse_id'] ?? Warehouse::getDefault()?->id;

            // If customer_id provided, auto-fill customer fields from customer record
            $customerName = $validated['customer_name'];
            $customerEmail = $validated['customer_email'] ?? null;
            $customerPhone = $validated['customer_phone'] ?? null;
            $customerAddress = $validated['customer_address'] ?? null;

            if (!empty($validated['customer_id'])) {
                $customer = \App\Models\Customer::find($validated['customer_id']);
                if ($customer) {
                    $customerName = $customerName ?: $customer->name;
                    $customerEmail = $customerEmail ?: $customer->email;
                    $customerPhone = $customerPhone ?: $customer->phone;
                    $customerAddress = $customerAddress ?: $customer->address;
                }
            }

            // Create sale
            $sale = Sale::create([
                'invoice_number' => $invoiceNumber,
                'customer_id' => $validated['customer_id'] ?? null,
                'customer_name' => $customerName,
                'customer_email' => $customerEmail,
                'customer_phone' => $customerPhone,
                'customer_address' => $customerAddress,
                'sales_person_id' => $validated['sales_person_id'] ?? null,
                'warehouse_id' => $warehouseId,
                'sale_date' => $validated['sale_date'],
                'status' => $validated['status'],
                'notes' => $validated['notes'] ?? null,
                'user_id' => auth()->id(),
                'total_amount' => 0,
            ]);

            $totalAmount = 0;

            // Create sale items and reduce stock
            foreach ($validated['items'] as $item) {
                $subtotal = $item['quantity'] * $item['unit_price'];
                $totalAmount += $subtotal;

                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $subtotal,
                ]);

                // Always reduce stock when sale is created (even if pending)
                $stockService = new StockSyncService();
                $stockService->deductStock(
                    $item['product_id'],
                    $item['quantity'],
                    'sale',
                    $sale->id,
                    "Sale #{$sale->invoice_number} - Status: {$validated['status']}",
                    $warehouseId
                );
            }

            // Calculate subtotal, tax, and grand total
            $subtotal = $totalAmount;
            $taxType = $validated['tax_type'] ?? null;
            $taxRateValue = 0;
            $taxAmount = 0;
            $discountAmount = $validated['discount_amount'] ?? 0;

            if ($taxType) {
                $taxRate = TaxRate::getByCode($taxType);
                if ($taxRate) {
                    $taxRateValue = $taxRate->rate;
                    $taxAmount = $taxRate->calculateTax($subtotal);
                }
            }

            $grandTotal = $subtotal + $taxAmount - $discountAmount;

            // Update sale with all financial fields
            $sale->update([
                'total_amount' => $subtotal,
                'subtotal' => $subtotal,
                'tax_type' => $taxType,
                'tax_rate' => $taxRateValue,
                'tax_amount' => $taxAmount,
                'discount_amount' => $discountAmount,
                'grand_total' => $grandTotal,
            ]);

            // Use grand_total (tax-inclusive) for payment amount
            $paymentAmount = $grandTotal > 0 ? $grandTotal : $subtotal;

            // Auto-create payment record (unpaid)
            Payment::create([
                'payable_type' => Sale::class,
                'payable_id' => $sale->id,
                'payment_type' => 'full',
                'amount' => $paymentAmount,
                'payment_date' => $validated['sale_date'],
                'method' => null,
                'status' => 'unpaid',
                'reference_number' => $invoiceNumber,
                'notes' => "Auto-created from Sale #{$invoiceNumber}",
                'user_id' => auth()->id(),
            ]);

            DB::commit();
            return response()->json($sale->load(['items.product', 'salesPerson', 'user', 'customer']), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function show($id)
    {
        $sale = Sale::with(['items.product', 'salesPerson', 'user', 'warehouse', 'customer', 'payments', 'delivery'])->findOrFail($id);
        return response()->json($sale);
    }

    public function update(Request $request, $id)
    {
        $sale = Sale::with('items')->findOrFail($id);
        $oldStatus = $sale->status;
        $warehouseId = $sale->warehouse_id ?? Warehouse::getDefault()?->id;

        $validated = $request->validate([
            'status' => 'required|in:pending,completed,cancelled',
            'notes' => 'nullable|string',
        ]);

        $newStatus = $validated['status'];

        DB::beginTransaction();
        try {
            $stockService = new StockSyncService();

            // Stock already deducted on create. Only restore when cancelling.
            // Handle status change: completed -> cancelled (restore stock)
            if (in_array($oldStatus, ['completed', 'pending']) && $newStatus === 'cancelled') {
                foreach ($sale->items as $item) {
                    $stockService->addStock(
                        $item->product_id,
                        $item->quantity,
                        'sale_cancelled',
                        $sale->id,
                        "Sale #{$sale->invoice_number} cancelled",
                        $warehouseId
                    );
                }
            }

            $sale->update($validated);
            DB::commit();

            return response()->json($sale->load(['items.product', 'salesPerson', 'user', 'customer']));
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function destroy($id)
    {
        $sale = Sale::with('items')->findOrFail($id);

        DB::beginTransaction();
        try {
            // If sale was completed, restore stock before deletion
            if (in_array($sale->status, ['completed', 'pending'])) {
                $stockService = new StockSyncService();
                foreach ($sale->items as $item) {
                    $stockService->addStock(
                        $item->product_id,
                        $item->quantity,
                        'sale_deleted',
                        $sale->id,
                        "Sale #{$sale->invoice_number} deleted - stock restored",
                        $sale->warehouse_id ?? Warehouse::getDefault()?->id
                    );
                }
            }

            // Delete associated payments to prevent orphaned records
            $sale->payments()->delete();

            $sale->delete();
            DB::commit();
            return response()->json(['message' => 'Sale deleted successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function statistics()
    {
        $stats = [
            'total_sales' => Sale::where('status', 'completed')->count(),
            'total_revenue' => Sale::where('status', 'completed')->sum('total_amount'),
            'pending_sales' => Sale::where('status', 'pending')->count(),
            'cancelled_sales' => Sale::where('status', 'cancelled')->count(),
        ];

        return response()->json($stats);
    }

    /**
     * Export invoice to Excel
     */
    public function exportInvoice($id)
    {
        $sale = Sale::with(['items.product', 'salesPerson'])->findOrFail($id);
        $filename = "invoice_{$sale->invoice_number}.xlsx";
        
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\SaleInvoiceExport($id), 
            $filename
        );
    }

    /**
     * Download invoice as PDF
     */
    public function downloadPdf($id)
    {
        $sale = Sale::with(['items.product', 'salesPerson', 'user'])
            ->findOrFail($id);
        
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('invoices.pdf', compact('sale'));
        
        return $pdf->download("invoice_{$sale->invoice_number}.pdf");
    }
}
