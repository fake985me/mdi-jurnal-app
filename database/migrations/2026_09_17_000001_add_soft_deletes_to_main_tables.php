<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambahkan kolom deleted_at (soft delete) ke tabel utama bisnis.
     *
     * Soft delete mencegah kehilangan data yang tidak sengaja — data yang
     * "dihapus" masih tersimpan di DB dan bisa dipulihkan jika diperlukan.
     *
     * Tabel yang ditambahkan: sales, products, rmas, warranties
     */
    public function up(): void
    {
        // Sales — penting: invoice, stock history harus bisa dipulihkan
        if (!Schema::hasColumn('sales', 'deleted_at')) {
            Schema::table('sales', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        // Products — mencegah kehilangan produk yang masih ada di transaksi lama
        if (!Schema::hasColumn('products', 'deleted_at')) {
            Schema::table('products', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        // RMAs — audit trail klaim garansi harus terjaga
        if (!Schema::hasColumn('rmas', 'deleted_at')) {
            Schema::table('rmas', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        // Warranties — riwayat garansi harus dipertahankan
        if (!Schema::hasColumn('warranties', 'deleted_at')) {
            Schema::table('warranties', function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('rmas', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('warranties', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
