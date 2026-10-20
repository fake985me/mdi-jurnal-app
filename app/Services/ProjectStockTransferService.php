<?php

namespace App\Services;

use App\Models\{ProjectInvestment, ProjectInvestmentItem, ProjectItemChange, ReturnedItem, StockTransfer, Warehouse};
use Illuminate\Support\Facades\DB;

class ProjectStockTransferService
{
    public function request(ProjectInvestmentItem $item, string $action = 'allocation', ?int $quantity = null, ?array $serials = null): StockTransfer
    {
        $project = $item->project;
        $default = Warehouse::getDefault();
        if (!$default || !$project->warehouse_id || $default->id == $project->warehouse_id) throw new \RuntimeException('Gudang asal dan gudang proyek harus tersedia dan berbeda.');
        return StockTransfer::create([
            'project_investment_item_id' => $item->id, 'returned_item_id' => $action === 'replacement' ? null : $item->returned_item_id,
            'project_action' => $action, 'product_id' => $item->product_id, 'quantity' => $quantity ?? $item->quantity,
            'serial_numbers' => $serials ?? $item->serial_numbers ?? [],
            'from_warehouse_id' => $action === 'return' ? $project->warehouse_id : $default->id,
            'to_warehouse_id' => $action === 'return' ? $default->id : $project->warehouse_id,
            'status' => 'pending', 'transferred_by' => auth()->id(),
            'notes' => $project->project_code.' · '.$action.' · '.$item->product?->title,
        ]);
    }

    public function transition(StockTransfer $transfer, string $target): StockTransfer
    {
        return DB::transaction(function () use ($transfer, $target) {
            $item = ProjectInvestmentItem::findOrFail($transfer->project_investment_item_id);
            $project = ProjectInvestment::lockForUpdate()->findOrFail($item->project_investment_id);
            $transfer = StockTransfer::lockForUpdate()->findOrFail($transfer->id);
            $item = ProjectInvestmentItem::lockForUpdate()->findOrFail($item->id);
            if ($transfer->status === $target) return $transfer;
            $returning = $transfer->project_action === 'return';
            if ($target !== 'cancelled' && !$returning && !in_array($project->status, ['approved', 'active'], true)) throw new \RuntimeException('Proyek tidak aktif untuk pengiriman.');
            $stock = app(StockSyncService::class);
            $recovered = $transfer->returned_item_id ? ReturnedItem::lockForUpdate()->findOrFail($transfer->returned_item_id) : null;
            if ($target === 'in_transit') {
                if ($transfer->status !== 'pending') throw new \RuntimeException('Pengiriman harus berstatus disiapkan.');
                if ($recovered && !$returning) {
                    if ($recovered->available_quantity < $transfer->quantity) throw new \RuntimeException('Stok RMA tidak mencukupi.');
                    $recovered->decrement('available_quantity', $transfer->quantity);
                } elseif (!$recovered) {
                    $stock->deductStock($transfer->product_id, $transfer->quantity, 'stock_transfer', $transfer->id, $transfer->notes, $transfer->from_warehouse_id);
                }
                $transfer->update(['status' => $target, 'transferred_at' => now()]);
            } elseif ($target === 'completed') {
                if ($transfer->status !== 'in_transit') throw new \RuntimeException('Barang harus dikirim sebelum diterima.');
                if ($recovered && $returning) $recovered->increment('available_quantity', $transfer->quantity);
                elseif (!$recovered) $stock->addStock($transfer->product_id, $transfer->quantity, 'stock_transfer', $transfer->id, $transfer->notes, $transfer->to_warehouse_id);
                $transfer->update(['status' => $target, 'received_at' => now(), 'received_by' => auth()->id()]);
                $item->update(['stock_deducted' => !$returning]);
            } elseif ($target === 'cancelled') {
                if (!in_array($transfer->status, ['pending', 'in_transit'], true)) throw new \RuntimeException('Pengiriman selesai tidak dapat dibatalkan.');
                if ($transfer->status === 'in_transit') {
                    if ($recovered && !$returning) $recovered->increment('available_quantity', $transfer->quantity);
                    elseif (!$recovered) $stock->addStock($transfer->product_id, $transfer->quantity, 'stock_transfer', $transfer->id, 'Pembatalan '.$transfer->notes, $transfer->from_warehouse_id);
                }
                $transfer->update(['status' => $target]);
            } else throw new \RuntimeException('Status pengiriman tidak valid.');
            ProjectItemChange::create([
                'project_investment_id' => $project->id, 'project_investment_item_id' => $item->id,
                'type' => 'shipment', 'quantity' => $transfer->quantity, 'user_id' => auth()->id(),
                'notes' => $transfer->transfer_code.' · '.$target, 'old_serial_numbers' => [], 'new_serial_numbers' => $transfer->serial_numbers,
                'details' => ['transfer_id' => $transfer->id, 'status' => $target, 'unit_price' => $item->unit_price, 'total_price' => round($item->unit_price * $transfer->quantity, 2), 'product' => $item->product?->title],
            ]);
            return $transfer->fresh();
        });
    }
}
