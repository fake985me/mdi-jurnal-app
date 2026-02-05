<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('current_stocks', function (Blueprint $table) {
            // Step 1: Drop the foreign key constraint on product_id first
            $table->dropForeign(['product_id']);
        });
        
        Schema::table('current_stocks', function (Blueprint $table) {
            // Step 2: Now we can drop the unique index
            $table->dropUnique(['product_id']);
        });
        
        Schema::table('current_stocks', function (Blueprint $table) {
            // Step 3: Add composite unique constraint
            $table->unique(['product_id', 'warehouse_id'], 'current_stocks_product_warehouse_unique');
            
            // Step 4: Re-add the foreign key constraint
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('current_stocks', function (Blueprint $table) {
            // Drop foreign key first
            $table->dropForeign(['product_id']);
            
            // Drop composite unique
            $table->dropUnique('current_stocks_product_warehouse_unique');
            
            // Restore old unique on product_id only
            $table->unique('product_id');
            
            // Re-add foreign key
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        });
    }
};
