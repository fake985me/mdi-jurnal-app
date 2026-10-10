<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Tambah kolom deskripsi, subtitle, module, dan slug ke tabel products
     * agar kompatibel dengan import template yang sudah ada.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Cek dan tambah hanya kolom yang belum ada
            if (!Schema::hasColumn('products', 'descriptions')) {
                $table->text('descriptions')->nullable()->after('title');
            }
            if (!Schema::hasColumn('products', 'subtitle')) {
                $table->string('subtitle')->nullable()->after('title');
            }
            if (!Schema::hasColumn('products', 'module')) {
                $table->string('module', 10)->nullable()->default('A')->after('brand');
            }
            if (!Schema::hasColumn('products', 'slug')) {
                $table->string('slug')->nullable()->unique()->after('module');
            }

            // Spec fields
            if (!Schema::hasColumn('products', 'spec1')) {
                $table->string('spec1')->nullable();
                $table->string('spec2')->nullable();
                $table->string('spec3')->nullable();
                $table->string('spec4')->nullable();
                $table->string('spec5')->nullable();
                $table->string('spec6')->nullable();
                $table->string('spec7')->nullable();
            }

            // Fitur / Feature fields
            if (!Schema::hasColumn('products', 'fitur1')) {
                $table->string('fitur1')->nullable();
                $table->string('fitur2')->nullable();
                $table->string('fitur3')->nullable();
                $table->string('fitur4')->nullable();
                $table->string('fitur5')->nullable();
                $table->string('fitur6')->nullable();
                $table->string('fitur7')->nullable();
                $table->string('fitur8')->nullable();
                $table->string('fitur9')->nullable();
                $table->string('fitur10')->nullable();
                $table->string('fitur11')->nullable();
                $table->string('fitur12')->nullable();
                $table->string('fitur13')->nullable();
                $table->string('fitur14')->nullable();
                $table->string('fitur15')->nullable();
            }

            // Interface / technical fields
            if (!Schema::hasColumn('products', 'interface_main')) {
                $table->string('interface_main')->nullable();
                $table->string('interface1')->nullable();
                $table->string('interface2')->nullable();
                $table->string('interface3')->nullable();
                $table->string('interface4')->nullable();
                $table->string('interface5')->nullable();
            }

            if (!Schema::hasColumn('products', 'flash_memory')) {
                $table->string('flash_memory')->nullable();
                $table->string('sdram_memory')->nullable();
            }

            if (!Schema::hasColumn('products', 'operating_temperature')) {
                $table->string('operating_temperature')->nullable();
                $table->string('storage_temperature')->nullable();
                $table->string('operating_humidity')->nullable();
            }

            if (!Schema::hasColumn('products', 'power1')) {
                $table->string('power1')->nullable();
                $table->string('power2')->nullable();
                $table->string('power3')->nullable();
                $table->string('power_consumptions')->nullable();
            }

            if (!Schema::hasColumn('products', 'dimensions')) {
                $table->string('dimensions')->nullable();
            }
            if (!Schema::hasColumn('products', 'diagram')) {
                $table->text('diagram')->nullable();
            }
            if (!Schema::hasColumn('products', 'network_diagram')) {
                $table->text('network_diagram')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $columns = [
                'descriptions', 'subtitle', 'module', 'slug',
                'spec1', 'spec2', 'spec3', 'spec4', 'spec5', 'spec6', 'spec7',
                'fitur1', 'fitur2', 'fitur3', 'fitur4', 'fitur5', 'fitur6', 'fitur7',
                'fitur8', 'fitur9', 'fitur10', 'fitur11', 'fitur12', 'fitur13', 'fitur14', 'fitur15',
                'interface_main', 'interface1', 'interface2', 'interface3', 'interface4', 'interface5',
                'flash_memory', 'sdram_memory',
                'operating_temperature', 'storage_temperature', 'operating_humidity',
                'power1', 'power2', 'power3', 'power_consumptions',
                'dimensions', 'diagram', 'network_diagram',
            ];
            $table->dropColumn(array_filter($columns, fn($col) => Schema::hasColumn('products', $col)));
        });
    }
};
