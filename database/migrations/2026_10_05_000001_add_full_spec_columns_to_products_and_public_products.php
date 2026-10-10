<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1. Update products table
        Schema::table('products', function (Blueprint $table) {
            // spec8 to spec20
            for ($i = 8; $i <= 20; $i++) {
                if (!Schema::hasColumn('products', "spec{$i}")) {
                    $table->text("spec{$i}")->nullable();
                }
            }

            // fitur16 to fitur20
            for ($i = 16; $i <= 20; $i++) {
                if (!Schema::hasColumn('products', "fitur{$i}")) {
                    $table->text("fitur{$i}")->nullable();
                }
            }

            // Wireless specifications
            if (!Schema::hasColumn('products', 'wireless_standard')) {
                $table->string('wireless_standard', 150)->nullable();
            }
            if (!Schema::hasColumn('products', 'wireless_stream')) {
                $table->string('wireless_stream', 150)->nullable();
            }
            for ($i = 1; $i <= 5; $i++) {
                if (!Schema::hasColumn('products', "wireless_stream{$i}")) {
                    $table->string("wireless_stream{$i}", 150)->nullable();
                }
            }

            // Controller specifications
            if (!Schema::hasColumn('products', 'manageable_aps')) {
                $table->string('manageable_aps', 150)->nullable();
            }
            if (!Schema::hasColumn('products', 'ap_number')) {
                $table->string('ap_number', 150)->nullable();
            }
            if (!Schema::hasColumn('products', 'number_of_clients')) {
                $table->string('number_of_clients', 150)->nullable();
            }
            if (!Schema::hasColumn('products', 'capacity')) {
                $table->string('capacity', 150)->nullable();
            }
            if (!Schema::hasColumn('products', 'ip_rating')) {
                $table->string('ip_rating', 150)->nullable();
            }

            // Switch specifications
            if (!Schema::hasColumn('products', 'switching')) {
                $table->string('switching', 150)->nullable();
            }
            if (!Schema::hasColumn('products', 'throughput')) {
                $table->string('throughput', 150)->nullable();
            }

            // Antenna
            if (!Schema::hasColumn('products', 'antena')) {
                $table->text('antena')->nullable();
            }

            // CU (Control Unit)
            if (!Schema::hasColumn('products', 'cu')) {
                $table->string('cu', 150)->nullable();
            }
            for ($i = 1; $i <= 5; $i++) {
                if (!Schema::hasColumn('products', "cu{$i}")) {
                    $table->string("cu{$i}", 150)->nullable();
                }
            }

            // Additional Interface
            for ($i = 1; $i <= 5; $i++) {
                if (!Schema::hasColumn('products', "additional_interface{$i}")) {
                    $table->text("additional_interface{$i}")->nullable();
                }
            }

            // Network specs
            if (!Schema::hasColumn('products', 'mac_address')) {
                $table->string('mac_address', 150)->nullable();
            }
            if (!Schema::hasColumn('products', 'routing_table')) {
                $table->string('routing_table', 150)->nullable();
            }

            // Environmental
            if (!Schema::hasColumn('products', 'dustproof_waterproof')) {
                $table->string('dustproof_waterproof', 150)->nullable();
            }
            if (!Schema::hasColumn('products', 'noise')) {
                $table->string('noise', 150)->nullable();
            }
            if (!Schema::hasColumn('products', 'mtbf')) {
                $table->string('mtbf', 150)->nullable();
            }

            // Power 4 & 5
            if (!Schema::hasColumn('products', 'power4')) {
                $table->string('power4', 150)->nullable();
            }
            if (!Schema::hasColumn('products', 'power5')) {
                $table->string('power5', 150)->nullable();
            }

            // PoE & Power Consumptions
            if (!Schema::hasColumn('products', 'power_consumption_with_poe')) {
                $table->string('power_consumption_with_poe', 150)->nullable();
            }
            if (!Schema::hasColumn('products', 'power_consumption_excluding_poe')) {
                $table->string('power_consumption_excluding_poe', 150)->nullable();
            }
            if (!Schema::hasColumn('products', 'poe_budget')) {
                $table->string('poe_budget', 150)->nullable();
            }
        });

        // 2. Update public_products table
        Schema::table('public_products', function (Blueprint $table) {
            // stock
            if (!Schema::hasColumn('public_products', 'stock')) {
                $table->integer('stock')->nullable()->default(0)->after('brand');
            }

            // spec8 to spec20
            for ($i = 8; $i <= 20; $i++) {
                if (!Schema::hasColumn('public_products', "spec{$i}")) {
                    $table->text("spec{$i}")->nullable();
                }
            }

            // fitur16 to fitur20
            for ($i = 16; $i <= 20; $i++) {
                if (!Schema::hasColumn('public_products', "fitur{$i}")) {
                    $table->text("fitur{$i}")->nullable();
                }
            }

            // cu5
            if (!Schema::hasColumn('public_products', 'cu5')) {
                $table->string('cu5', 150)->nullable()->after('cu4');
            }

            // additional_interface5
            if (!Schema::hasColumn('public_products', 'additional_interface5')) {
                $table->text('additional_interface5')->nullable()->after('additional_interface4');
            }

            // power4, power5
            if (!Schema::hasColumn('public_products', 'power4')) {
                $table->string('power4', 150)->nullable()->after('power3');
            }
            if (!Schema::hasColumn('public_products', 'power5')) {
                $table->string('power5', 150)->nullable()->after('power4');
            }

            // PoE & Power Consumptions
            if (!Schema::hasColumn('public_products', 'power_consumption_with_poe')) {
                $table->string('power_consumption_with_poe', 150)->nullable()->after('power_consumptions');
            }
            if (!Schema::hasColumn('public_products', 'power_consumption_excluding_poe')) {
                $table->string('power_consumption_excluding_poe', 150)->nullable()->after('power_consumption_with_poe');
            }
            if (!Schema::hasColumn('public_products', 'poe_budget')) {
                $table->string('poe_budget', 150)->nullable()->after('power_consumption_excluding_poe');
            }

            // Change diagram & network_diagram to text to allow multi-diagrams
            $table->text('diagram')->nullable()->change();
            $table->text('network_diagram')->nullable()->change();
        });
    }

    public function down(): void
    {
        // down logic if needed
    }
};
