<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rmas', function (Blueprint $table) {
            // Link RMA to original sale
            $table->foreignId('sale_id')->nullable()->after('warranty_id')->constrained()->nullOnDelete();
            
            // Link to specific sale item
            $table->foreignId('sale_item_id')->nullable()->after('sale_id')->constrained()->nullOnDelete();
            
            // Link to MSA project if applicable
            $table->foreignId('msa_project_id')->nullable()->after('sale_item_id')->constrained('msa_projects')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('rmas', function (Blueprint $table) {
            $table->dropForeign(['sale_id']);
            $table->dropForeign(['sale_item_id']);
            $table->dropForeign(['msa_project_id']);
            $table->dropColumn(['sale_id', 'sale_item_id', 'msa_project_id']);
        });
    }
};
