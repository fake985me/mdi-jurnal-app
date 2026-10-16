<?php

namespace App\Console\Commands;

use App\Models\{RMA, RMAShipment, MSAProject};
use App\Services\{ReturnedInventoryService, RMAReplacementSerialService};
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncReturnedInventory extends Command
{
    protected $signature = 'inventory:sync-returns';
    protected $description = 'Register historical replacements and reconcile known replacement serial numbers';
    public function handle(): int
    {
        $service = app(ReturnedInventoryService::class);
        foreach (RMA::where('resolution', 'replace')->where('status', '!=', 'rejected')->get() as $rma) {
            DB::transaction(function () use ($rma, $service) {
                $serials = array_values(array_filter(array_map('trim', explode(',', $rma->returned_serial_number ?: $rma->serial_number ?: ''))));
                $service->register('rma', $rma->id, $rma->rma_code, $rma->product_id, $rma->quantity, $serials, $rma->condition);
                $shipment = RMAShipment::where('rma_id', $rma->id)->first();
                if ($shipment?->serial_numbers && !array_intersect(array_map('strtolower', $serials), array_map('strtolower', $shipment->serial_numbers))) {
                    app(RMAReplacementSerialService::class)->replace($shipment, $shipment->serial_numbers);
                }
            });
        }
        foreach (MSAProject::where(fn ($query) => $query->where('status', 'replaced')->orWhere('resolution', 'replaced'))->get() as $msa) {
            $service->register('msa', $msa->id, $msa->msa_code, $msa->product_id, $msa->quantity, $msa->serial_numbers ?? [], $msa->issue_description);
        }
        $this->info('Historical returned inventory synchronized.');
        return self::SUCCESS;
    }
}
