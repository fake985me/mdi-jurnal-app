<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Sale;
use App\Models\TaxRate;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $query = Invoice::with(['sale.items.product', 'sale.salesPerson']);

        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('sale', function ($sq) use ($search) {
                        $sq->where('customer_name', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->start_date) {
            $query->whereDate('invoice_date', '>=', $request->start_date);
        }

        if ($request->end_date) {
            $query->whereDate('invoice_date', '<=', $request->end_date);
        }

        $invoices = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json($invoices);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'sale_id' => 'required|exists:sales,id',
            'invoice_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:invoice_date',
            'tax_type' => 'nullable|string',
            'discount_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $sale = Sale::with('items')->findOrFail($validated['sale_id']);

        // Check if invoice already exists for this sale
        if ($sale->invoice) {
            return response()->json(['message' => 'Invoice already exists for this sale'], 422);
        }

        $invoice = new Invoice();
        $invoice->invoice_number = Invoice::generateInvoiceNumber();
        $invoice->invoice_date = $validated['invoice_date'];
        $invoice->due_date = $validated['due_date'] ?? null;
        $invoice->notes = $validated['notes'] ?? null;
        
        // Calculate with tax
        $invoice->calculateFromSale($sale, $validated['tax_type'] ?? 'ppn');
        
        if (isset($validated['discount_amount'])) {
            $invoice->discount_amount = $validated['discount_amount'];
            $invoice->total = $invoice->subtotal + $invoice->tax_amount - $invoice->discount_amount;
        }

        $invoice->save();

        return response()->json($invoice->load('sale'), 201);
    }

    public function show(Invoice $invoice)
    {
        $invoice->load(['sale.items.product', 'sale.salesPerson', 'sale.user']);
        return response()->json($invoice);
    }

    public function update(Request $request, Invoice $invoice)
    {
        $validated = $request->validate([
            'due_date' => 'nullable|date',
            'status' => 'sometimes|in:draft,sent,paid,overdue,cancelled',
            'notes' => 'nullable|string',
        ]);

        $invoice->update($validated);
        return response()->json($invoice);
    }

    public function destroy(Invoice $invoice)
    {
        $invoice->delete();
        return response()->json(['message' => 'Invoice deleted successfully']);
    }

    public function markAsPaid(Invoice $invoice)
    {
        $invoice->update(['status' => 'paid']);
        return response()->json($invoice);
    }

    public function markAsSent(Invoice $invoice)
    {
        $invoice->update(['status' => 'sent']);
        return response()->json($invoice);
    }

    public function downloadPdf(Invoice $invoice)
    {
        $invoice->load(['sale.items.product', 'sale.salesPerson', 'sale.user']);

        $pdf = Pdf::loadView('invoices.pdf', [
            'invoice' => $invoice,
            'sale' => $invoice->sale,
        ]);

        return $pdf->download("invoice_{$invoice->invoice_number}.pdf");
    }

    // Tax Report
    public function taxReport(Request $request)
    {
        $startDate = $request->start_date ?? now()->startOfMonth()->toDateString();
        $endDate = $request->end_date ?? now()->endOfMonth()->toDateString();

        $invoices = Invoice::with('sale')
            ->whereDate('invoice_date', '>=', $startDate)
            ->whereDate('invoice_date', '<=', $endDate)
            ->where('status', '!=', 'cancelled')
            ->get();

        $report = [
            'period' => [
                'start' => $startDate,
                'end' => $endDate,
            ],
            'summary' => [
                'total_invoices' => $invoices->count(),
                'total_subtotal' => $invoices->sum('subtotal'),
                'total_tax' => $invoices->sum('tax_amount'),
                'total_discount' => $invoices->sum('discount_amount'),
                'total_revenue' => $invoices->sum('total'),
            ],
            'by_tax_type' => $invoices->groupBy('tax_type')->map(function ($group, $type) {
                return [
                    'tax_type' => $type ?: 'No Tax',
                    'count' => $group->count(),
                    'subtotal' => $group->sum('subtotal'),
                    'tax_amount' => $group->sum('tax_amount'),
                    'total' => $group->sum('total'),
                ];
            })->values(),
            'by_status' => $invoices->groupBy('status')->map(function ($group, $status) {
                return [
                    'status' => $status,
                    'count' => $group->count(),
                    'total' => $group->sum('total'),
                ];
            })->values(),
        ];

        return response()->json($report);
    }

    // Monthly summary for accounting
    public function monthlySummary(Request $request)
    {
        $year = $request->year ?? now()->year;

        $summary = [];
        for ($month = 1; $month <= 12; $month++) {
            $invoices = Invoice::whereYear('invoice_date', $year)
                ->whereMonth('invoice_date', $month)
                ->where('status', '!=', 'cancelled')
                ->get();

            $summary[] = [
                'month' => $month,
                'month_name' => \Carbon\Carbon::create($year, $month)->format('F'),
                'total_invoices' => $invoices->count(),
                'subtotal' => $invoices->sum('subtotal'),
                'tax_amount' => $invoices->sum('tax_amount'),
                'total' => $invoices->sum('total'),
                'paid' => $invoices->where('status', 'paid')->sum('total'),
                'unpaid' => $invoices->whereIn('status', ['draft', 'sent', 'overdue'])->sum('total'),
            ];
        }

        return response()->json([
            'year' => $year,
            'data' => $summary,
            'totals' => [
                'total_invoices' => array_sum(array_column($summary, 'total_invoices')),
                'subtotal' => array_sum(array_column($summary, 'subtotal')),
                'tax_amount' => array_sum(array_column($summary, 'tax_amount')),
                'total' => array_sum(array_column($summary, 'total')),
                'paid' => array_sum(array_column($summary, 'paid')),
                'unpaid' => array_sum(array_column($summary, 'unpaid')),
            ],
        ]);
    }
}
