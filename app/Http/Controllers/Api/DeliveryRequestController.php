<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{Sale, Lending, RMAShipment, StockTransfer, StockTransaction};
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Models\DeliveryFulfillment;
use Illuminate\Support\Facades\DB;

class DeliveryRequestController extends Controller
{
    public function index(Request $request)
    {
        $input = $request->validate([
            'search' => 'nullable|string|max:255',
            'page' => 'sometimes|integer|min:1',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ]);
        $rows = collect();
        $append = function ($id, $type, $number, $party, $items, $status, $date, $route) use ($rows) {
            $rows->push(compact('id', 'type', 'number', 'party', 'items', 'status', 'date', 'route'));
        };
        foreach (Sale::with(['items.product', 'delivery'])->whereIn('status', ['pending', 'completed'])->get() as $sale) {
            $append('sale-'.$sale->id, 'Penjualan', $sale->invoice_number, $sale->customer_name,
                $sale->items->map(fn ($item) => ['product' => $item->product?->title, 'quantity' => $item->quantity]),
                $sale->delivery?->status ?? 'preparing', $sale->created_at, '/dashboard/sales/'.$sale->id.'/edit');
        }
        foreach (Lending::with('product')->get() as $lending) {
            $append('lending-'.$lending->id, 'Peminjaman', $lending->lending_code, $lending->borrower_name,
                [['product' => $lending->product?->title, 'quantity' => $lending->quantity]],
                $lending->status === 'returned' ? 'delivered' : 'preparing', $lending->created_at, '/dashboard/lendings');
        }
        foreach (RMAShipment::with('product')->get() as $shipment) {
            $append('rma-'.$shipment->id, 'Pengiriman RMA', $shipment->shipment_code, $shipment->recipient_name,
                [['product' => $shipment->product?->title, 'quantity' => $shipment->quantity]],
                $shipment->status === 'pending' ? 'preparing' : $shipment->status, $shipment->created_at, '/dashboard/rma-shipments');
        }
        foreach (StockTransfer::with(['product', 'fromWarehouse', 'toWarehouse'])->get() as $transfer) {
            $append('transfer-'.$transfer->id, 'Transfer Gudang', $transfer->transfer_code,
                ($transfer->fromWarehouse?->name ?? '-').' → '.($transfer->toWarehouse?->name ?? '-'),
                [['product' => $transfer->product?->title, 'quantity' => $transfer->quantity]],
                match ($transfer->status) { 'pending' => 'preparing', 'completed' => 'delivered', default => $transfer->status }, $transfer->created_at, '/dashboard/stock-transfers');
        }
        // Other outgoing stock movements are already durable records. Do not duplicate
        // movements belonging to the source documents displayed above.
        $sourceIds = $rows->pluck('id')->flip();
        $inactiveSaleIds = Sale::withTrashed()->where(function ($query) {
            $query->where('status', 'cancelled')->orWhereNotNull('deleted_at');
        })->pluck('id')->flip();
        foreach (StockTransaction::with('product')->where('transaction_type', 'out')->get() as $transaction) {
            if ($transaction->reference_type === 'sale' && $inactiveSaleIds->has($transaction->reference_id)) {
                continue;
            }
            $sourceType = match ($transaction->reference_type) {
                'sale' => 'sale', 'lending' => 'lending',
                'rma_shipment' => 'rma', 'stock_transfer' => 'transfer',
                default => null,
            };
            if ($sourceType && $sourceIds->has($sourceType.'-'.$transaction->reference_id)) {
                continue;
            }
            $append('stock-'.$transaction->id, 'Stok Keluar', strtoupper($transaction->reference_type ?: 'STK').'-'.($transaction->reference_id ?: $transaction->id),
                $transaction->notes, [['product' => $transaction->product?->title, 'quantity' => $transaction->quantity]],
                'preparing', $transaction->created_at, '/dashboard/history');
        }
        $fulfillments = DeliveryFulfillment::whereIn('request_key', $rows->pluck('id'))->get()->keyBy('request_key');
        $rows = $rows->map(function ($row) use ($fulfillments) {
            $fulfillment = $fulfillments->get($row['id']);
            if ($fulfillment) {
                // Sales, RMA and transfers keep their authoritative source status.
                if (str_starts_with($row['id'], 'stock-') || str_starts_with($row['id'], 'lending-')) {
                    $row['status'] = $fulfillment->status;
                }
                foreach (['courier', 'tracking_number', 'destination', 'notes'] as $field) {
                    $row[$field] = $fulfillment->$field;
                }
            }
            return $row;
        });
        $needle = mb_strtolower($input['search'] ?? '');
        $rows = $rows->filter(fn ($row) => !$needle || str_contains(mb_strtolower(json_encode($row, JSON_UNESCAPED_UNICODE)), $needle))
            ->sortByDesc('date')->values();
        $page = $input['page'] ?? 1;
        $perPage = $input['per_page'] ?? 15;
        return response()->json(new LengthAwarePaginator(
            $rows->forPage($page, $perPage)->values(), $rows->count(), $perPage, $page,
            ['path' => $request->url(), 'query' => $request->query()]
        ));
    }

    public function update(Request $request, string $key)
    {
        abort_unless(preg_match('/^(sale|lending|rma|transfer|stock)-([1-9][0-9]*)$/', $key, $match), 404);
        $data = $request->validate([
            'status' => 'required|in:shipped,delivered',
            'courier' => 'required_if:status,shipped|string|max:100',
            'tracking_number' => 'required_if:status,shipped|string|max:100',
            'destination' => 'required_if:status,shipped|string|max:2000',
            'notes' => 'nullable|string|max:2000',
        ]);
        [$unused, $type, $id] = $match;
        try {
            return DB::transaction(function () use ($request, $key, $data, $type, $id) {
                $model = match ($type) {
                    'sale' => Sale::class, 'lending' => Lending::class, 'rma' => RMAShipment::class,
                    'transfer' => StockTransfer::class, 'stock' => StockTransaction::class,
                };
                $source = $model::lockForUpdate()->findOrFail($id);
                if (($type === 'sale' && $source->status === 'cancelled') ||
                    ($type === 'transfer' && $source->status === 'cancelled') ||
                    ($type === 'stock' && ($source->transaction_type !== 'out' || in_array($source->reference_type, ['sale', 'lending', 'rma_shipment', 'stock_transfer'], true)))) {
                    throw new \RuntimeException('Permintaan ini tidak dapat diproses untuk pengiriman.');
                }
                $fulfillment = DeliveryFulfillment::firstOrCreate(['request_key' => $key], ['status' => 'preparing']);
                $status = match ($type) {
                    'sale' => $source->delivery?->status ?? 'preparing',
                    'rma' => $source->status === 'pending' ? 'preparing' : $source->status,
                    'transfer' => match ($source->status) { 'pending' => 'preparing', 'completed' => 'delivered', default => $source->status },
                    default => $fulfillment->status,
                };
                if (($data['status'] === 'shipped' && $status !== 'preparing') ||
                    ($data['status'] === 'delivered' && !in_array($status, ['shipped', 'in_transit'], true))) {
                    throw new \RuntimeException('Urutan pengiriman harus: disiapkan, dikirim, lalu diterima.');
                }
                $response = null;
                if ($type === 'sale') {
                    $delivery = $source->delivery ?? $source->delivery()->create([
                        'status' => 'preparing', 'user_id' => auth()->id(),
                    ]);
                    $response = app(DeliveryController::class)->update($request, $delivery->id);
                    if ($response->status() < 400) {
                        $delivery->update(['courier' => $data['courier'] ?? $delivery->courier, 'shipped_date' => $delivery->shipped_date ?? now()]);
                    }
                } elseif ($type === 'rma') {
                    $controller = app(RMAShipmentController::class);
                    $response = $data['status'] === 'shipped' ? $controller->ship($request, $id) : $controller->markDelivered($id);
                } elseif ($type === 'transfer') {
                    $controller = app(StockTransferController::class);
                    $response = $data['status'] === 'shipped' ? $controller->startTransfer($source) : $controller->completeTransfer($request, $source);
                }
                if ($response && $response->status() >= 400) {
                    throw new \RuntimeException($response->getData()->message ?? 'Pengiriman gagal diproses.');
                }
                $fulfillment->fill($data);
                if ($data['status'] === 'shipped') $fulfillment->shipped_at = now();
                if ($data['status'] === 'delivered') $fulfillment->delivered_at = now();
                $fulfillment->save();
                return response()->json($fulfillment);
            });
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            abort(404);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
