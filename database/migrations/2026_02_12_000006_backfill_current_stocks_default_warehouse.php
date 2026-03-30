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

        $rows = DB::table('current_stocks')->whereNull('warehouse_id')->get();
        foreach ($rows as $row) {
            $existing = DB::table('current_stocks')
                ->where('product_id', $row->product_id)
                ->where('warehouse_id', $defaultWarehouse->id)
                ->first();

            if ($existing) {
                DB::table('current_stocks')
                    ->where('id', $existing->id)
                    ->update([
                        'quantity' => $existing->quantity + $row->quantity,
                        'last_updated' => now(),
                    ]);
                DB::table('current_stocks')->where('id', $row->id)->delete();
            } else {
                DB::table('current_stocks')
                    ->where('id', $row->id)
                    ->update(['warehouse_id' => $defaultWarehouse->id]);
            }
        }
    }

    public function down(): void
    {
        // No down migration for data backfill.
    }
};
