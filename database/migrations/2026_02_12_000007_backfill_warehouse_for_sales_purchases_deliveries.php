<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $defaultWarehouse = DB::table('warehouses')->where('is_default', true)->first();
        if (!$defaultWarehouse) {
            return;
        }

        DB::table('sales')
            ->whereNull('warehouse_id')
            ->update(['warehouse_id' => $defaultWarehouse->id]);

        DB::table('purchases')
            ->whereNull('warehouse_id')
            ->update(['warehouse_id' => $defaultWarehouse->id]);

        $deliveries = DB::table('deliveries')
            ->whereNull('from_warehouse_id')
            ->get();

        foreach ($deliveries as $delivery) {
            $saleWarehouseId = DB::table('sales')
                ->where('id', $delivery->sale_id)
                ->value('warehouse_id');

            DB::table('deliveries')
                ->where('id', $delivery->id)
                ->update([
                    'from_warehouse_id' => $saleWarehouseId ?? $defaultWarehouse->id,
                ]);
        }
    }

    public function down(): void
    {
        // No down migration for data backfill.
    }
};
