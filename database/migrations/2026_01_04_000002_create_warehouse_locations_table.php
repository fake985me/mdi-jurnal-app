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
        Schema::create('warehouse_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained()->onDelete('cascade');
            $table->string('code', 20);
            $table->string('name');
            $table->string('zone')->nullable(); // A, B, C zones
            $table->string('aisle')->nullable(); // Aisle number
            $table->string('rack')->nullable(); // Rack number
            $table->string('shelf')->nullable(); // Shelf level
            $table->integer('capacity')->nullable(); // Max items
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['warehouse_id', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouse_locations');
    }
};
