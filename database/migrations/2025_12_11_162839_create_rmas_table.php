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
        Schema::create('rmas', function (Blueprint $table) {
            $table->id();
            $table->string('rma_code')->unique();
            $table->foreignId('warranty_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('customer_name');
            $table->string('customer_contact')->nullable();
            $table->integer('quantity');
            $table->enum('reason', ['warranty_claim', 'damaged_shipment', 'defective', 'dead_on_arrival']);
            $table->enum('status', ['pending', 'approved', 'rejected', 'received', 'processed', 'completed'])->default('pending');
            $table->date('issue_date');
            $table->date('received_date')->nullable();
            $table->enum('resolution', ['repair', 'replace', 'refund'])->nullable();
            $table->text('notes')->nullable();
            $table->text('condition')->nullable();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rmas');
    }
};
