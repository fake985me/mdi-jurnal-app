<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah kolom public_product_id ke tabel products sebagai link permanen
     * antara produk internal (inventory) dan produk publik (website).
     *
     * Kolom ini nullable — produk internal tidak harus punya padanan publik.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('public_product_id')
                ->nullable()
                ->after('id')
                ->constrained('public_products')
                ->onDelete('set null');

            $table->index('public_product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['public_product_id']);
            $table->dropIndex(['public_product_id']);
            $table->dropColumn('public_product_id');
        });
    }
};
