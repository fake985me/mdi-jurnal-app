<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Delivery;
use App\Models\Lending;
use App\Models\Purchase;
use App\Models\RMA;
use App\Models\RMAShipment;
use App\Models\Sale;
use App\Models\StockTransaction;
use App\Models\StockTransfer;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class HistoryController extends Controller
{
    /**
     * List all stock movements and warehouse transfer movements.
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'page' => 'sometimes|integer|min:1',
            'per_page' => 'sometimes|integer|min:1|max:100',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'direction' => 'nullable|in:in,out',
            'search' => 'nullable|string|max:255',
        ]);

        $transactions = StockTransaction::with(['product:id,title', 'user:id,name'])
            ->when($validated['date_from'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '>=', $date))
            ->when($validated['date_to'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '<=', $date))
            ->get();

        $referenceIds = $transactions
            ->filter(fn ($transaction) => $transaction->reference_id)
            ->groupBy(fn ($transaction) => strtolower((string) $transaction->reference_type))
            ->map(fn ($items) => $items->pluck('reference_id')->unique()->values());

        $saleIds = $this->referenceIdsFor($referenceIds, fn ($type) => str_starts_with($type, 'sale'));
        $purchaseIds = $this->referenceIdsFor($referenceIds, fn ($type) => str_starts_with($type, 'purchase'));
        $lendingIds = $this->referenceIdsFor($referenceIds, fn ($type) => in_array($type, ['lending', 'lending_return'], true));
        $rmaIds = $this->referenceIdsFor($referenceIds, fn ($type) => str_starts_with($type, 'rma') && $type !== 'rma_shipment');
        $shipmentIds = $this->referenceIdsFor($referenceIds, fn ($type) => $type === 'rma_shipment');

        $sales = Sale::with(['delivery', 'invoice'])->whereIn('id', $saleIds)->get()->keyBy('id');
        $purchases = Purchase::whereIn('id', $purchaseIds)->get()->keyBy('id');
        $lendings = Lending::whereIn('id', $lendingIds)->get()->keyBy('id');
        $rmas = RMA::whereIn('id', $rmaIds)->get()->keyBy('id');
        $shipments = RMAShipment::with('rma')->whereIn('id', $shipmentIds)->get()->keyBy('id');
        $purchaseDeliveries = Delivery::whereIn('po_number', $purchases->pluck('po_number')->filter())->get()->keyBy('po_number');

        $rows = $transactions->map(function (StockTransaction $transaction) use (
            $sales,
            $purchases,
            $lendings,
            $rmas,
            $shipments,
            $purchaseDeliveries
        ) {
            $referenceType = strtolower((string) $transaction->reference_type);
            $referenceId = $transaction->reference_id;
            $partyName = null;
            $documentNumber = null;
            $deliveryNumber = null;
            $transactionLabel = $transaction->transaction_type === 'in' ? 'Stok Masuk' : 'Stok Keluar';

            if (str_starts_with($referenceType, 'sale')) {
                $sale = $sales->get($referenceId);
                $partyName = $sale?->customer_name;
                $documentNumber = $sale?->invoice?->invoice_number ?? $sale?->invoice_number;
                $deliveryNumber = $sale?->delivery?->tracking_number;
                $transactionLabel = str_contains($referenceType, 'cancel')
                    ? 'Pembatalan Penjualan'
                    : 'Penjualan';
            } elseif (str_starts_with($referenceType, 'purchase')) {
                $purchase = $purchases->get($referenceId);
                $partyName = $purchase?->supplier_name;
                $documentNumber = $purchase?->po_number;
                $deliveryNumber = $purchase
                    ? $purchaseDeliveries->get($purchase->po_number)?->tracking_number
                    : null;
                $transactionLabel = str_contains($referenceType, 'cancel') || str_contains($referenceType, 'deleted')
                    ? 'Pembatalan Pembelian'
                    : 'Pembelian';
            } elseif (in_array($referenceType, ['lending', 'lending_return'], true)) {
                $lending = $lendings->get($referenceId);
                $partyName = $lending?->borrower_name;
                $documentNumber = $lending?->lending_code;
                $deliveryNumber = $lending?->lending_code;
                $transactionLabel = $referenceType === 'lending_return'
                    ? 'Pengembalian Peminjaman'
                    : 'Peminjaman';
            } elseif ($referenceType === 'rma_shipment') {
                $shipment = $shipments->get($referenceId);
                $partyName = $shipment?->recipient_name ?? $shipment?->rma?->customer_name;
                $documentNumber = $shipment?->rma?->rma_code ?? $shipment?->shipment_code;
                $deliveryNumber = $shipment?->tracking_number ?? $shipment?->shipment_code;
                $transactionLabel = 'Pengiriman Penggantian RMA';
            } elseif (str_starts_with($referenceType, 'rma')) {
                $rma = $rmas->get($referenceId);
                $partyName = $rma?->customer_name;
                $documentNumber = $rma?->rma_code;
                $transactionLabel = match ($referenceType) {
                    'rma_pending' => 'Barang diterima untuk RMA',
                    'rma_return_working' => 'Barang RMA kembali ke stok',
                    'rma_return_repairable' => 'Barang RMA masuk gudang perbaikan',
                    'rma_replacement_remove' => 'Barang RMA dikirim untuk penggantian',
                    'rma_rejected' => 'RMA ditolak',
                    'rma_deleted' => 'RMA dibatalkan',
                    'rma_refund_restocked' => 'Barang refund kembali ke stok',
                    default => 'Pergerakan stok RMA',
                };
            }

            return [
                'id' => 'stock-' . $transaction->id,
                'date' => $transaction->created_at,
                'party_name' => $partyName ?: '—',
                'product_name' => $transaction->product?->title ?? 'Produk dihapus',
                'quantity' => $transaction->quantity,
                'direction' => $transaction->transaction_type,
                'transaction_label' => $transactionLabel,
                'document_number' => $documentNumber ?: ($transaction->reference_id ? strtoupper($referenceType) . '-' . $referenceId : 'STK-' . $transaction->id),
                'delivery_number' => $deliveryNumber,
                'warehouse_route' => null,
                'notes' => $transaction->notes,
            ];
        });

        $transfers = StockTransfer::with(['product:id,title', 'fromWarehouse:id,name', 'toWarehouse:id,name'])
            ->whereIn('status', ['in_transit', 'completed', 'cancelled'])
            ->get();

        foreach ($transfers as $transfer) {
            if (!$transfer->transferred_at) {
                continue;
            }

            $route = ($transfer->fromWarehouse?->name ?? 'Gudang asal') . ' → ' . ($transfer->toWarehouse?->name ?? 'Gudang tujuan');
            $rows->push([
                'id' => 'transfer-out-' . $transfer->id,
                'date' => $transfer->transferred_at,
                'party_name' => $route,
                'product_name' => $transfer->product?->title ?? 'Produk dihapus',
                'quantity' => $transfer->quantity,
                'direction' => 'out',
                'transaction_label' => 'Transfer Keluar',
                'document_number' => $transfer->transfer_code,
                'delivery_number' => $transfer->transfer_code,
                'warehouse_route' => $route,
                'notes' => $transfer->notes,
            ]);

            if ($transfer->status === 'completed' && $transfer->received_at) {
                $rows->push([
                    'id' => 'transfer-in-' . $transfer->id,
                    'date' => $transfer->received_at,
                    'party_name' => $route,
                    'product_name' => $transfer->product?->title ?? 'Produk dihapus',
                    'quantity' => $transfer->quantity,
                    'direction' => 'in',
                    'transaction_label' => 'Transfer Masuk',
                    'document_number' => $transfer->transfer_code,
                    'delivery_number' => $transfer->transfer_code,
                    'warehouse_route' => $route,
                    'notes' => $transfer->notes,
                ]);
            } elseif ($transfer->status === 'cancelled') {
                $rows->push([
                    'id' => 'transfer-return-' . $transfer->id,
                    'date' => $transfer->updated_at,
                    'party_name' => $route,
                    'product_name' => $transfer->product?->title ?? 'Produk dihapus',
                    'quantity' => $transfer->quantity,
                    'direction' => 'in',
                    'transaction_label' => 'Transfer Dibatalkan (Stok Kembali)',
                    'document_number' => $transfer->transfer_code,
                    'delivery_number' => $transfer->transfer_code,
                    'warehouse_route' => $route,
                    'notes' => $transfer->notes,
                ]);
            }
        }

        $rows = $rows
            ->when($validated['date_from'] ?? null, fn ($items, $date) => $items->filter(
                fn ($row) => Carbon::parse($row['date'])->toDateString() >= $date
            ))
            ->when($validated['date_to'] ?? null, fn ($items, $date) => $items->filter(
                fn ($row) => Carbon::parse($row['date'])->toDateString() <= $date
            ))
            ->when($validated['direction'] ?? null, fn ($items, $direction) => $items->where('direction', $direction))
            ->when($validated['search'] ?? null, function ($items, $search) {
                $needle = mb_strtolower($search);
                return $items->filter(fn ($row) =>
                    str_contains(mb_strtolower($row['party_name']), $needle)
                    || str_contains(mb_strtolower($row['product_name']), $needle)
                    || str_contains(mb_strtolower($row['document_number']), $needle)
                    || str_contains(mb_strtolower((string) $row['delivery_number']), $needle)
                    || str_contains(mb_strtolower((string) $row['notes']), $needle)
                );
            })
            ->sortByDesc('date')
            ->values();

        $perPage = (int) ($validated['per_page'] ?? 20);
        $page = (int) ($validated['page'] ?? 1);
        $total = $rows->count();

        return response()->json([
            'data' => $rows->slice(($page - 1) * $perPage, $perPage)->values(),
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => max(1, (int) ceil($total / $perPage)),
            ],
        ]);
    }

    private function referenceIdsFor($referenceIds, callable $matches): array
    {
        return $referenceIds
            ->filter(fn ($ids, $type) => $matches($type))
            ->flatten()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Get sales and purchases statistics.
     */
    public function stats(Request $request)
    {
        $dateFrom = $request->get('date_from', now()->startOfMonth());
        $dateTo = $request->get('date_to', now()->endOfMonth());

        $salesTotal = Sale::whereBetween('sale_date', [$dateFrom, $dateTo])
            ->where('status', 'completed')
            ->sum('total_amount');

        $salesCount = Sale::whereBetween('sale_date', [$dateFrom, $dateTo])->count();

        $purchasesTotal = Purchase::whereBetween('order_date', [$dateFrom, $dateTo])
            ->where('status', 'paid')
            ->sum('total_amount');

        $purchasesCount = Purchase::whereBetween('order_date', [$dateFrom, $dateTo])->count();

        return response()->json([
            'sales' => ['total' => $salesTotal, 'count' => $salesCount],
            'purchases' => ['total' => $purchasesTotal, 'count' => $purchasesCount],
            'net' => $salesTotal - $purchasesTotal,
        ]);
    }
}
