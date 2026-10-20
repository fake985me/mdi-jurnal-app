<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{Sale, Lending, RMAShipment, StockTransfer, StockTransaction, ProjectMaterial, ProjectProgressLog};
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Models\DeliveryFulfillment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DeliveryRequestController extends Controller
{
    private const ADJUSTMENT_TYPES = ['adjustment', 'manual_adjustment', 'stock_adjustment', 'project_defective_return', 'project_transfer_correction'];

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
            $row = $rows->pop();
            $rows->push(array_merge($row, [
                'delivery_id' => $sale->delivery?->id,
                'courier' => $sale->delivery?->courier,
                'tracking_number' => $sale->delivery?->tracking_number,
                'destination' => $sale->delivery?->destination,
                'notes' => $sale->delivery?->notes,
                'contact' => $sale->customer_phone,
            ]));
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
            $row = $rows->pop();
            $rows->push(array_merge($row, [
                'courier' => $shipment->courier,
                'tracking_number' => $shipment->tracking_number,
                'destination' => $shipment->recipient_address,
                'contact' => $shipment->recipient_phone,
                'notes' => $shipment->notes,
            ]));
        }
        foreach (StockTransfer::with(['product', 'fromWarehouse', 'toWarehouse', 'projectItem.project'])->get() as $transfer) {
            $append('transfer-'.$transfer->id, 'Transfer Gudang', $transfer->transfer_code,
                ($transfer->fromWarehouse?->name ?? '-').' → '.($transfer->toWarehouse?->name ?? '-'),
                [['product' => $transfer->product?->title, 'quantity' => $transfer->quantity, 'serial_numbers' => $transfer->serial_numbers ?? []]],
                match ($transfer->status) { 'pending' => 'preparing', 'completed' => 'delivered', default => $transfer->status }, $transfer->created_at, $transfer->projectItem ? '/dashboard/project-investments/'.$transfer->projectItem->project_investment_id : '/dashboard/stock-transfers');
            $row = $rows->pop();
            $rows->push($row + ['project_id' => $transfer->projectItem?->project_investment_id]);
        }
        foreach (ProjectMaterial::with('project')->whereHas('project', fn ($q) => $q->whereIn('status', ['approved', 'active', 'completed']))->get() as $material) {
            $append('material-'.$material->id, 'Material Proyek', $material->project->project_code.' / MAT-'.$material->id,
                $material->project->project_name, [['product' => $material->name, 'quantity' => $material->quantity_planned]],
                $material->quantity_received >= $material->quantity_planned ? 'delivered' : 'preparing', $material->created_at,
                '/dashboard/project-investments/'.$material->project_investment_id);
        }
        // Other outgoing stock movements are already durable records. Do not duplicate
        // movements belonging to the source documents displayed above.
        $sourceIds = $rows->pluck('id')->flip();
        $inactiveSaleIds = Sale::withTrashed()->where(function ($query) {
            $query->where('status', 'cancelled')->orWhereNotNull('deleted_at');
        })->pluck('id')->flip();
        foreach (StockTransaction::with('product')->where('transaction_type', 'out')->get() as $transaction) {
            if (in_array($transaction->reference_type, self::ADJUSTMENT_TYPES, true)) {
                continue;
            }
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
            $row['attachments'] = collect($fulfillment?->attachments ?? [])
                ->map(fn ($file) => collect($file)->except('path')->all())->values()->all();
            if ($fulfillment) {
                // Sales, RMA and transfers keep their authoritative source status.
                if (str_starts_with($row['id'], 'stock-') || str_starts_with($row['id'], 'lending-') || str_starts_with($row['id'], 'material-')) {
                    if (!str_starts_with($row['id'], 'material-') || $row['status'] !== 'delivered') $row['status'] = $fulfillment->status;
                }
                foreach (['courier', 'tracking_number', 'destination', 'notes'] as $field) {
                    $row[$field] = $row[$field] ?? $fulfillment->$field;
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

    public function updateStatus(Request $request, string $key)
    {
        return $this->update($request, $key, true);
    }

    public function downloadAttachment(string $key, string $attachment)
    {
        $fulfillment = DeliveryFulfillment::where('request_key', $key)->firstOrFail();
        $file = collect($fulfillment->attachments ?? [])->firstWhere('id', $attachment);
        abort_unless($file && Storage::disk('local')->exists($file['path']), 404);
        return Storage::disk('local')->download($file['path'], $file['original_name']);
    }

    public function update(Request $request, string $key, bool $statusModal = false)
    {
        abort_unless(preg_match('/^(sale|lending|rma|transfer|stock|material)-([1-9][0-9]*)$/', $key, $match), 404);
        $data = $request->validate([
            'status' => $statusModal ? 'required|in:preparing,shipped,in_transit,delivered,cancelled' : 'required|in:shipped,delivered',
            'courier' => ($statusModal ? 'nullable' : 'required_if:status,shipped').'|string|max:100',
            'tracking_number' => ($statusModal ? 'nullable' : 'required_if:status,shipped').'|string|max:100',
            'destination' => ($statusModal ? 'nullable' : 'required_if:status,shipped').'|string|max:2000',
            'notes' => 'nullable|string|max:2000',
            'attachments' => 'nullable|array|max:5',
            'attachments.*' => 'required|file|mimes:jpg,jpeg,png,webp,gif,bmp,avif,pdf,doc,docx,xls,xlsx,csv|max:10240',
        ]);
        unset($data['attachments']);
        [$unused, $type, $id] = $match;
        $storedPaths = [];
        try {
            return DB::transaction(function () use ($request, $key, $data, $type, $id, $statusModal, &$storedPaths) {
                $model = match ($type) {
                    'sale' => Sale::class, 'lending' => Lending::class, 'rma' => RMAShipment::class,
                    'transfer' => StockTransfer::class, 'stock' => StockTransaction::class, 'material' => ProjectMaterial::class,
                };
                $candidate = $model::findOrFail($id);
                if ($type === 'material') $candidate->project()->lockForUpdate()->firstOrFail();
                if ($type === 'transfer' && $candidate->project_investment_item_id) $candidate->projectItem->project()->lockForUpdate()->firstOrFail();
                $source = $model::lockForUpdate()->findOrFail($id);
                if (($type === 'sale' && $source->status === 'cancelled') ||
                    ($type === 'transfer' && $source->status === 'cancelled') ||
                    ($type === 'stock' && ($source->transaction_type !== 'out' || in_array($source->reference_type, [...self::ADJUSTMENT_TYPES, 'sale', 'lending', 'rma_shipment', 'stock_transfer'], true)))) {
                    throw new \RuntimeException('Permintaan ini tidak dapat diproses untuk pengiriman.');
                }
                $fulfillment = DeliveryFulfillment::firstOrCreate(['request_key' => $key], ['status' => 'preparing']);
                $attachments = $fulfillment->attachments ?? [];
                $uploads = $request->file('attachments', []);
                if (count($attachments) + count($uploads) > 5) {
                    throw new \RuntimeException('Maksimal 5 attachment untuk satu pengiriman.');
                }
                $status = match ($type) {
                    'sale' => $source->delivery?->status ?? 'preparing',
                    'rma' => $source->status === 'pending' ? 'preparing' : $source->status,
                    'transfer' => match ($source->status) { 'pending' => 'preparing', 'completed' => 'delivered', default => $source->status },
                    'material' => $source->quantity_received >= $source->quantity_planned ? 'delivered' : $fulfillment->status,
                    default => $fulfillment->status,
                };
                if ((!$statusModal || $type === 'material') && (($data['status'] === 'shipped' && $status !== 'preparing') ||
                    ($data['status'] === 'delivered' && !in_array($status, ['shipped', 'in_transit'], true)))) {
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
                    $response = $statusModal ? $controller->updateStatus($request, $id)
                        : ($data['status'] === 'shipped' ? $controller->ship($request, $id) : $controller->markDelivered($id));
                } elseif ($type === 'transfer') {
                    $controller = app(StockTransferController::class);
                    $response = match ($data['status']) {
                        'shipped', 'in_transit' => $source->status === 'pending' ? $controller->startTransfer($source) : null,
                        'delivered' => $source->status === 'completed' ? null : $controller->completeTransfer($request, $source),
                        'cancelled' => $controller->cancelTransfer($request, $source),
                        'preparing' => $source->status === 'pending' ? null : throw new \RuntimeException('Transfer yang sudah berjalan tidak dapat kembali ke preparing.'),
                    };
                }
                if ($type === 'material') {
                    $project = $source->project()->lockForUpdate()->firstOrFail();
                    if (!in_array($project->status, ['approved', 'active'], true)) throw new \RuntimeException('Project is not open for shipment.');
                    if ($data['status'] === 'in_transit' && !in_array($status, ['preparing', 'shipped', 'in_transit'], true)) throw new \RuntimeException('Invalid shipment transition.');
                    if ($status === 'delivered' && $data['status'] !== 'delivered') throw new \RuntimeException('Received materials cannot be reverted.');
                    if ($data['status'] === 'preparing' && $status !== 'preparing') throw new \RuntimeException('Shipment cannot return to preparing.');
                    if (in_array($data['status'], ['shipped', 'in_transit'], true)) $source->quantity_ordered = $source->quantity_planned;
                    if ($data['status'] === 'delivered') {
                        $source->quantity_received = $source->quantity_planned;
                        $source->actual_delivery_date = now()->toDateString();
                    }
                    $source->save();
                    $source->updateStatus();
                    ProjectProgressLog::logChange($project->id, 'material', 'shipment', $status, $data['status'],
                        $source->name.' / '.$source->quantity_planned.' '.$source->unit.' / '.$data['status'],
                        ['material_id' => $source->id, 'name' => $source->name, 'unit_price' => $source->unit_price, 'total_price' => $source->total_price]);
                }
                if ($response && $response->status() >= 400) {
                    throw new \RuntimeException($response->getData()->message ?? 'Pengiriman gagal diproses.');
                }
                foreach ($uploads as $file) {
                    $path = $file->store('delivery-attachments/'.$key, 'local');
                    if (!$path) throw new \RuntimeException('Attachment gagal disimpan.');
                    $storedPaths[] = $path;
                    $attachments[] = [
                        'id' => (string) Str::uuid(),
                        'path' => $path,
                        'original_name' => $file->getClientOriginalName(),
                        'mime_type' => $file->getMimeType(),
                        'size' => $file->getSize(),
                    ];
                }
                if ($uploads) $fulfillment->attachments = $attachments;
                $fulfillment->fill($data);
                if ($data['status'] === 'shipped') $fulfillment->shipped_at = now();
                if ($data['status'] === 'delivered') $fulfillment->delivered_at = now();
                $fulfillment->save();
                $result = $fulfillment->toArray();
                $result['attachments'] = collect($attachments)->map(fn ($file) => collect($file)->except('path')->all())->all();
                return response()->json($result);
            });
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            abort(404);
        } catch (\Exception $e) {
            Storage::disk('local')->delete($storedPaths);
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
