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
    public function __construct(private StockSyncService $stockService) {}
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
            if ($request->boolean('include_warranty_status')) {
                $query->with(['invoice', 'warranties', 'rmas', 'delivery.items.product']);
            }

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
            'po_number' => 'nullable|string|max:255',
            'customer_id' => 'nullable|exists:customers,id',
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'nullable|email',
            'customer_phone' => 'nullable|string',
            'customer_address' => 'nullable|string',
            'sales_person_id' => 'nullable|exists:sales_people,id',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'sale_date' => 'required|date',
            'due_date' => 'nullable|date',
            'memo' => 'nullable|string',
            'status' => 'required|in:pending,completed,cancelled',
            'notes' => 'nullable|string',
            'tax_type' => 'nullable|string',
            'discount_amount' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.description' => 'nullable|string',
            'items.*.unit' => 'nullable|string|max:50',
            'items.*.discount' => 'nullable|numeric|min:0',
            'items.*.tax_code' => 'nullable|string|max:50',
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
                'po_number' => $validated['po_number'] ?? null,
                'customer_id' => $validated['customer_id'] ?? null,
                'customer_name' => $customerName,
                'customer_email' => $customerEmail,
                'customer_phone' => $customerPhone,
                'customer_address' => $customerAddress,
                'sales_person_id' => $validated['sales_person_id'] ?? null,
                'warehouse_id' => $warehouseId,
                'sale_date' => $validated['sale_date'],
                'due_date' => $validated['due_date'] ?? null,
                'memo' => $validated['memo'] ?? null,
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
                    'description' => $item['description'] ?? null,
                    'unit' => $item['unit'] ?? null,
                    'discount' => $item['discount'] ?? 0,
                    'tax_code' => $item['tax_code'] ?? null,
                ]);

                // Always reduce stock when sale is created (even if pending)
                $this->stockService->deductStock(
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
            if ($discountAmount > $subtotal) throw new \RuntimeException('Diskon tidak boleh melebihi subtotal.');

            if ($taxType) {
                $taxRate = TaxRate::getByCode($taxType);
                if ($taxRate) {
                    $taxRateValue = $taxRate->rate;
                    $taxAmount = round(($subtotal - $discountAmount) * $taxRate->rate / 100);
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

            // Auto-create delivery record
            $deliveryInfo = $request->input('delivery_info');
            $trackingNumber = $deliveryInfo['tracking_number'] ?? null;
            if (empty($trackingNumber)) {
                $trackingNumber = 'TRK-' . date('Ymd') . '-' . str_pad($sale->id, 4, '0', STR_PAD_LEFT);
            }
            \App\Models\Delivery::create([
                'sale_id' => $sale->id,
                'status' => 'preparing',
                'courier' => $deliveryInfo['courier'] ?? null,
                'tracking_number' => $trackingNumber,
                'user_id' => auth()->id(),
                'notes' => "Auto-created from Sale #{$invoiceNumber}",
            ]);

            DB::commit();
            return response()->json($sale->load(['items.product', 'salesPerson', 'user', 'customer', 'delivery']), 201);
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
        $validated = $request->validate([
            'status' => 'required|in:pending,completed,cancelled',
            'invoice_number' => 'sometimes|required|string|max:255|unique:sales,invoice_number,'.$id,
            'po_number' => 'nullable|string|max:255',
            'customer_id' => 'nullable|exists:customers,id',
            'customer_name' => 'sometimes|required|string|max:255',
            'customer_email' => 'nullable|email|max:255',
            'customer_phone' => 'nullable|string|max:255',
            'customer_address' => 'nullable|string',
            'sales_person_id' => 'nullable|exists:sales_people,id',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'sale_date' => 'sometimes|required|date', 'due_date' => 'nullable|date',
            'notes' => 'nullable|string', 'memo' => 'nullable|string',
            'tax_type' => 'nullable|string', 'discount_amount' => 'nullable|numeric|min:0',
            'items' => 'sometimes|required|array|min:1',
            'items.*.id' => 'nullable|integer',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.description' => 'nullable|string', 'items.*.unit' => 'nullable|string|max:50',
            'items.*.discount' => 'nullable|numeric|min:0', 'items.*.tax_code' => 'nullable|string|max:50',
            'delivery_info' => 'nullable|array',
            'delivery_info.courier' => 'nullable|string|max:255',
            'delivery_info.tracking_number' => 'nullable|string|max:255',
        ]);
        try {
            return DB::transaction(function () use ($id, $validated) {
                $sale = Sale::with('items')->lockForUpdate()->findOrFail($id);
                $oldStatus = $sale->status;
                $newStatus = $validated['status'];
                $oldWarehouse = $sale->warehouse_id ?? Warehouse::getDefault()?->id;
                $newWarehouse = $validated['warehouse_id'] ?? $oldWarehouse;
                $items = $validated['items'] ?? $sale->items->toArray();
                $quantities = function ($rows) {
                    $result = [];
                    foreach ($rows as $item) $result[$item['product_id']] = ($result[$item['product_id']] ?? 0) + (int) $item['quantity'];
                    ksort($result);
                    return $result;
                };
                $oldQuantities = $quantities($sale->items->toArray());
                $newQuantities = $quantities($items);
                $structuralChange = $oldQuantities !== $newQuantities || $oldWarehouse != $newWarehouse;
                if (isset($validated['items'])) {
                    $oldShape = $sale->items->mapWithKeys(fn ($item) => [$item->id => $item->product_id.':'.$item->quantity])->all();
                    $newShape = [];
                    foreach ($items as $index => $item) $newShape[$item['id'] ?? 'new-'.$index] = $item['product_id'].':'.$item['quantity'];
                    ksort($oldShape); ksort($newShape);
                    $structuralChange = $structuralChange || $oldShape !== $newShape;
                }
                if ($structuralChange && ($sale->warranties()->exists() || $sale->rmas()->where('status', '!=', 'rejected')->exists() ||
                    $sale->delivery()->whereHas('items', fn ($query) => $query->whereNotNull('serial_number'))->exists())) {
                    throw new \RuntimeException('Produk, quantity, atau gudang tidak dapat diganti karena SN/garansi/RMA sudah tercatat. Gunakan alur retur atau penggantian; data pelanggan dan harga tetap dapat diedit.');
                }
                $stock = $this->stockService;
                $sameWarehouse = $oldWarehouse == $newWarehouse;
                foreach (array_unique(array_merge(array_keys($oldQuantities), array_keys($newQuantities))) as $productId) {
                    $before = $oldStatus === 'cancelled' ? 0 : ($oldQuantities[$productId] ?? 0);
                    $after = $newStatus === 'cancelled' ? 0 : ($newQuantities[$productId] ?? 0);
                    $restore = $sameWarehouse ? max(0, $before - $after) : $before;
                    $deduct = $sameWarehouse ? max(0, $after - $before) : $after;
                    if ($restore) $stock->addStock($productId, $restore, $newStatus === 'cancelled' ? 'sale_cancelled' : 'sale_edit', $sale->id, "Sale #{$sale->invoice_number} updated", $oldWarehouse);
                    if ($deduct) $stock->deductStock($productId, $deduct, 'sale', $sale->id, "Sale #{$sale->invoice_number} updated", $newWarehouse);
                }
                if ($oldStatus === 'cancelled' && $newStatus !== 'cancelled') $sale->delivery()->update(['status' => 'preparing', 'delivered_date' => null]);
                if ($oldStatus !== 'cancelled' && $newStatus === 'cancelled') $sale->delivery()->update(['status' => 'cancelled']);
                if (isset($validated['items'])) {
                    $retained = [];
                    foreach ($items as $input) {
                        $existing = !empty($input['id']) ? $sale->items->firstWhere('id', $input['id']) : $sale->items->first(fn ($item) => $item->product_id == $input['product_id'] && !in_array($item->id, $retained, true));
                        if (!empty($input['id']) && (!$existing || in_array($existing->id, $retained, true))) throw new \RuntimeException('Item penjualan tidak valid.');
                        $attributes = collect($input)->except('id')->all();
                        $attributes['subtotal'] = $input['quantity'] * $input['unit_price'];
                        if ($existing) { $existing->update($attributes); $retained[] = $existing->id; }
                        else $retained[] = $sale->items()->create($attributes)->id;
                    }
                    $sale->items()->whereNotIn('id', $retained)->delete();
                }
                $attributes = collect($validated)->except(['items', 'tax_type', 'discount_amount', 'delivery_info'])->all();
                if (array_key_exists('warehouse_id', $validated)) $attributes['warehouse_id'] = $newWarehouse;
                $sale->update($attributes);
                if (isset($validated['delivery_info'])) {
                    $delivery = $sale->delivery;
                    if ($delivery) $delivery->update($validated['delivery_info']);
                    elseif (array_filter($validated['delivery_info'])) $sale->delivery()->create(array_merge($validated['delivery_info'], ['status' => 'preparing', 'user_id' => auth()->id()]));
                }
                if (isset($validated['items']) || array_key_exists('tax_type', $validated) || array_key_exists('discount_amount', $validated)) {
                    $subtotal = $sale->items()->sum('subtotal');
                    $discount = $validated['discount_amount'] ?? $sale->discount_amount ?? 0;
                    if ($discount > $subtotal) throw new \RuntimeException('Diskon tidak boleh melebihi subtotal.');
                    $taxType = array_key_exists('tax_type', $validated) ? $validated['tax_type'] : $sale->tax_type;
                    $taxRate = $taxType ? TaxRate::getByCode($taxType) : null;
                    $rate = (float) ($taxRate?->rate ?? 0);
                    $taxAmount = round(($subtotal - $discount) * $rate / 100);
                    $total = round($subtotal - $discount + $taxAmount, 2);
                    $paid = (float) $sale->payments()->where('status', 'paid')->sum('amount');
                    if ($paid > $total + 0.001) throw new \RuntimeException('Total penjualan tidak boleh lebih kecil dari pembayaran yang sudah lunas.');
                    $sale->update(['total_amount' => $subtotal, 'subtotal' => $subtotal, 'discount_amount' => $discount, 'tax_type' => $taxType ?: null, 'tax_rate' => $rate, 'tax_amount' => $taxAmount, 'grand_total' => $total]);
                    $unpaid = $sale->payments()->where('status', 'unpaid')->lockForUpdate()->orderBy('id')->get();
                    $weight = (float) $unpaid->sum('amount');
                    $remaining = round($total - $paid, 2);
                    foreach ($unpaid as $index => $payment) {
                        $amount = $index === $unpaid->count() - 1 ? $remaining : min($remaining, round(($total - $paid) * ($weight > 0 ? (float) $payment->amount / $weight : 1 / $unpaid->count()), 2));
                        $payment->update(['amount' => $amount]);
                        $remaining = round($remaining - $amount, 2);
                    }
                }
                if ($sale->invoice()->exists()) $sale->invoice()->update(collect([
                    'invoice_date' => $sale->sale_date, 'due_date' => $sale->due_date,
                    'subtotal' => $sale->subtotal, 'tax_type' => $sale->tax_type, 'tax_rate' => $sale->tax_rate,
                    'tax_amount' => $sale->tax_amount, 'discount_amount' => $sale->discount_amount, 'total' => $sale->grand_total,
                ])->all());
                return response()->json($sale->load(['items.product', 'salesPerson', 'user', 'customer']));
            });
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function destroy($id)
    {
        $sale = Sale::with('items')->findOrFail($id);

        DB::beginTransaction();
        try {
            $sale = Sale::with('items')->lockForUpdate()->findOrFail($id);
            // If sale was completed, restore stock before deletion
            if (in_array($sale->status, ['completed', 'pending'])) {
                $stockService = $this->stockService;
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

    public function printInvoice($id)
    {
        $sale = Sale::with(['items.product', 'salesPerson', 'user'])->findOrFail($id);
        $html = view('invoices.pdf', compact('sale'))->render();
        $html = str_replace('src="./public/', 'src="'.url('/').'/', $html);
        return response($html, 200)->header('Content-Type', 'text/html; charset=UTF-8');
    }
}
