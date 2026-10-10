<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rmas', function (Blueprint $table) {
            $table->enum('condition_level', ['working', 'repairable', 'damaged_total', 'parts_only'])->nullable()->after('condition');
            $table->foreignId('rma_warehouse_id')->nullable()->after('condition_level')->constrained('warehouses')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('rmas', function (Blueprint $table) {
            $table->dropForeign(['rma_warehouse_id']);
            $table->dropColumn(['condition_level', 'rma_warehouse_id']);
        });
    }
};
