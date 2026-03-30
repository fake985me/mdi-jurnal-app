<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('warehouse_id')
                ->nullable()
                ->after('sales_person_id')
                ->constrained()
                ->nullOnDelete();
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->foreignId('warehouse_id')
                ->nullable()
                ->after('supplier_phone')
                ->constrained()
                ->nullOnDelete();
        });

        Schema::table('deliveries', function (Blueprint $table) {
            $table->foreignId('from_warehouse_id')
                ->nullable()
                ->after('sale_id')
                ->constrained('warehouses')
                ->nullOnDelete();
            $table->string('destination')->nullable()->after('courier');
        });
    }

    public function down(): void
    {
        Schema::table('deliveries', function (Blueprint $table) {
            $table->dropForeign(['from_warehouse_id']);
            $table->dropColumn(['from_warehouse_id', 'destination']);
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->dropForeign(['warehouse_id']);
            $table->dropColumn('warehouse_id');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['warehouse_id']);
            $table->dropColumn('warehouse_id');
        });
    }
};
