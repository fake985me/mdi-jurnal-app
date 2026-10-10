<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rma_shipments', function (Blueprint $table) {
            $table->id();
            $table->string('shipment_code')->unique();
            $table->foreignId('rma_id')->constrained('rmas')->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->integer('quantity');
            $table->string('tracking_number')->nullable();
            $table->string('courier')->nullable();
            $table->enum('status', ['pending', 'shipped', 'in_transit', 'delivered', 'cancelled'])->default('pending');
            $table->date('shipped_date')->nullable();
            $table->date('delivered_date')->nullable();
            $table->text('recipient_name')->nullable();
            $table->text('recipient_address')->nullable();
            $table->text('recipient_phone')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rma_shipments');
    }
};
