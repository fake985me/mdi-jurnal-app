<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_investments', function (Blueprint $table) {
            // Project type: 'invest' or 'design_build'
            $table->enum('type', ['invest', 'design_build'])->default('invest')->after('project_code');
            
            // Link to dedicated warehouse for this project
            $table->foreignId('warehouse_id')->nullable()->after('type')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('project_investments', function (Blueprint $table) {
            $table->dropForeign(['warehouse_id']);
            $table->dropColumn(['type', 'warehouse_id']);
        });
    }
};
