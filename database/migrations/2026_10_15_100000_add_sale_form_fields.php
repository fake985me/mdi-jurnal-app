<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->date('due_date')->nullable();
            $table->text('memo')->nullable();
        });
        Schema::table('sale_items', function (Blueprint $table) {
            $table->text('description')->nullable();
            $table->string('unit', 50)->nullable();
            $table->decimal('discount', 15, 2)->default(0);
            $table->string('tax_code', 50)->nullable();
        });
    }
    public function down(): void
    {
        Schema::table('sales', fn (Blueprint $table) => $table->dropColumn(['due_date', 'memo']));
        Schema::table('sale_items', fn (Blueprint $table) => $table->dropColumn(['description', 'unit', 'discount', 'tax_code']));
    }
};
