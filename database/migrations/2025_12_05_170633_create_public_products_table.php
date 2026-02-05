<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('public_products', function (Blueprint $table) {
            $table->id();
            $table->string('sku', 100)->nullable();
            $table->string('module', 100)->nullable();
            $table->string('slug')->unique();
            $table->text('image')->nullable();
            $table->string('category', 100)->nullable();
            $table->string('sub_category', 100)->nullable();
            $table->string('brand', 100)->nullable();
            $table->string('title')->nullable();
            $table->string('subtitle')->nullable();

            // Main specifications (spec1 - spec7) - TEXT to avoid row size limit
            $table->text('spec1')->nullable();
            $table->text('spec2')->nullable();
            $table->text('spec3')->nullable();
            $table->text('spec4')->nullable();
            $table->text('spec5')->nullable();
            $table->text('spec6')->nullable();
            $table->text('spec7')->nullable();

            // Product description
            $table->text('descriptions')->nullable();

            // Product features (fitur1 - fitur15) - TEXT to avoid row size limit
            $table->text('fitur1')->nullable();
            $table->text('fitur2')->nullable();
            $table->text('fitur3')->nullable();
            $table->text('fitur4')->nullable();
            $table->text('fitur5')->nullable();
            $table->text('fitur6')->nullable();
            $table->text('fitur7')->nullable();
            $table->text('fitur8')->nullable();
            $table->text('fitur9')->nullable();
            $table->text('fitur10')->nullable();
            $table->text('fitur11')->nullable();
            $table->text('fitur12')->nullable();
            $table->text('fitur13')->nullable();
            $table->text('fitur14')->nullable();
            $table->text('fitur15')->nullable();

            // Wireless specifications
            $table->string('wireless_standard')->nullable();
            $table->string('wireless_stream')->nullable();
            $table->string('wireless_stream1')->nullable();
            $table->string('wireless_stream2')->nullable();
            $table->string('wireless_stream3')->nullable();
            $table->string('wireless_stream4')->nullable();
            $table->string('wireless_stream5')->nullable();

            // Capacity & Performance
            $table->string('manageable_aps')->nullable();
            $table->string('ap_number', 100)->nullable();
            $table->string('number_of_clients', 100)->nullable();
            $table->string('capacity')->nullable();
            $table->string('ip_rating', 100)->nullable();
            $table->string('switching')->nullable();
            $table->string('throughput')->nullable();

            // Memory
            $table->string('flash_memory')->nullable();
            $table->string('sdram_memory')->nullable();

            // Interfaces (Interface1 - Interface5) - TEXT to avoid row size limit
            $table->text('interface_main')->nullable();
            $table->text('interface1')->nullable();
            $table->text('interface2')->nullable();
            $table->text('interface3')->nullable();
            $table->text('interface4')->nullable();
            $table->text('interface5')->nullable();

            // Antenna
            $table->text('antena')->nullable();

            // CU (cu - cu4)
            $table->string('cu')->nullable();
            $table->string('cu1')->nullable();
            $table->string('cu2')->nullable();
            $table->string('cu3')->nullable();
            $table->string('cu4')->nullable();

            // Additional Interfaces - TEXT to avoid row size limit
            $table->text('additional_interface1')->nullable();
            $table->text('additional_interface2')->nullable();
            $table->text('additional_interface3')->nullable();
            $table->text('additional_interface4')->nullable();

            // Networking
            $table->string('mac_address')->nullable();
            $table->string('routing_table')->nullable();

            // Environmental
            $table->string('dustproof_waterproof', 100)->nullable();
            $table->string('noise', 100)->nullable();
            $table->string('mtbf', 100)->nullable();
            $table->string('operating_temperature', 100)->nullable();
            $table->string('storage_temperature', 100)->nullable();
            $table->string('operating_humidity', 100)->nullable();

            // Power
            $table->string('power1')->nullable();
            $table->string('power2')->nullable();
            $table->string('power3')->nullable();
            $table->text('power_consumptions')->nullable();

            // Physical
            $table->string('dimensions')->nullable();

            // Diagram references
            $table->string('diagram', 100)->nullable();
            $table->string('network_diagram', 100)->nullable();

            // Visibility control
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);

            // Metadata
            $table->timestamps();

            // Indexes for better query performance
            $table->index('category');
            $table->index('sub_category');
            $table->index('brand');
            $table->index('module');
            $table->index('is_active');
            $table->index('sort_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('public_products');
    }
};
