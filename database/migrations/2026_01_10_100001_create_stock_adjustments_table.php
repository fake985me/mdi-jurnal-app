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
        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table->string('adjustment_code', 50)->unique();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->enum('adjustment_type', ['in', 'out']);
            $table->string('reason', 50); // damaged, expired, lost, found, correction, audit, theft, donation, return_from_lending, warranty_replacement
            $table->integer('quantity');
            $table->integer('before_qty');
            $table->integer('after_qty');
            $table->string('related_type')->nullable(); // asset, lending, warranty, etc.
            $table->unsignedBigInteger('related_id')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->timestamps();

            $table->index(['product_id', 'created_at']);
            $table->index('adjustment_code');
            $table->index('reason');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_adjustments');
    }
};
