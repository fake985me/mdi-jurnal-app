<?php

namespace App\Services;

use App\Models\{CurrentStock, DeliveryFulfillment, Product, ProjectInvestment, ProjectInvestmentItem, ProjectItemChange, StockTransaction, StockTransfer, Warehouse};
use Illuminate\Support\Facades\DB;

class LegacyProjectTransferCorrectionService
{
    /** Reset a verified, undispatched legacy allocation and retain its full audit snapshot. */
    public function resetUndispatched(int $transferId, int $itemId, string $reason): StockTransfer
    {
        if (!auth()->id() || !trim($reason)) throw new \RuntimeException('An operator and physical verification reason are required.');

        return DB::transaction(function () use ($transferId, $itemId, $reason) {
            $candidate = ProjectInvestmentItem::findOrFail($itemId);
            $project = ProjectInvestment::lockForUpdate()->findOrFail($candidate->project_investment_id);
            $product = Product::lockForUpdate()->findOrFail($candidate->product_id);
            $transfer = StockTransfer::lockForUpdate()->findOrFail($transferId);
            $item = ProjectInvestmentItem::lockForUpdate()->findOrFail($itemId);
            $previous = ProjectItemChange::where('project_investment_item_id', $itemId)
                ->where('type', 'shipment_correction')->get()
                ->first(fn ($change) => ($change->details['transfer_id'] ?? null) === $transferId);
            // A retry must never reverse inventory again, even after manual shipment resumes.
            if ($previous) return $transfer;

            if (!in_array($project->status, ['approved', 'active'], true) ||
                $transfer->status !== 'completed' || $transfer->project_investment_item_id ||
                $transfer->notes !== 'Project allocation: '.$project->project_code ||
                $transfer->to_warehouse_id != $project->warehouse_id ||
                $transfer->from_warehouse_id != Warehouse::getDefault()?->id ||
                $transfer->product_id != $item->product_id || $transfer->quantity !== $item->quantity ||
                !$item->stock_deducted || $item->returned_item_id || $item->transfers()->exists()) {
                throw new \RuntimeException('Transfer does not match an uncorrected legacy project allocation.');
            }
            if (StockTransaction::where('product_id', $item->product_id)->where('created_at', '>=', $transfer->created_at)->exists() ||
                StockTransfer::where('product_id', $item->product_id)->where('id', '!=', $transferId)
                    ->where('created_at', '>=', $transfer->created_at)->exists()) {
                throw new \RuntimeException('Later inventory activity requires a separate reconciliation.');
            }
            $stocks = CurrentStock::where('product_id', $item->product_id)
                ->whereIn('warehouse_id', [$transfer->from_warehouse_id, $transfer->to_warehouse_id])
                ->orderBy('warehouse_id')->lockForUpdate()->get();
            $source = $stocks->firstWhere('warehouse_id', $transfer->from_warehouse_id);
            $destination = $stocks->firstWhere('warehouse_id', $transfer->to_warehouse_id);
            if (!$source || !$destination || (int) $destination->quantity !== $transfer->quantity || (int) $source->quantity !== (int) $product->stock) {
                throw new \RuntimeException('Warehouse balances require a separate reconciliation.');
            }
            $fulfillment = DeliveryFulfillment::where('request_key', 'transfer-'.$transferId)->lockForUpdate()->first();
            $before = ['transfer' => $transfer->toArray(), 'item' => $item->toArray(),
                'stocks' => $stocks->toArray(), 'product_stock' => $product->stock, 'fulfillment' => $fulfillment?->toArray()];
            $notes = $transfer->transfer_code.' / '.$reason;
            $stock = app(StockSyncService::class);
            $stock->deductStock($item->product_id, $transfer->quantity, 'project_transfer_correction', $transferId, $notes, $transfer->to_warehouse_id);
            $stock->addStock($item->product_id, $transfer->quantity, 'project_transfer_correction', $transferId, $notes, $transfer->from_warehouse_id);
            $transfer->update(['project_investment_item_id' => $itemId, 'project_action' => 'allocation',
                'serial_numbers' => $item->serial_numbers ?? [], 'status' => 'pending',
                'transferred_at' => null, 'received_at' => null, 'received_by' => null]);
            $item->update(['stock_deducted' => false]);
            $fulfillment?->update(['status' => 'preparing', 'shipped_at' => null, 'delivered_at' => null]);
            ProjectItemChange::create(['project_investment_id' => $project->id, 'project_investment_item_id' => $itemId,
                'type' => 'shipment_correction', 'quantity' => $transfer->quantity, 'user_id' => auth()->id(),
                'notes' => $notes, 'details' => ['transfer_id' => $transferId, 'before' => $before,
                    'after' => ['transfer' => $transfer->fresh()->toArray(), 'stock_deducted' => false,
                        'source_quantity' => $source->fresh()->quantity, 'destination_quantity' => $destination->fresh()->quantity],
                    'product' => $product->title, 'unit_price' => $item->unit_price, 'total_price' => $item->total_price]]);
            return $transfer->fresh();
        });
    }
}
