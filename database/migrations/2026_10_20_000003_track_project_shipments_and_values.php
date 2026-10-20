<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_transfers', function (Blueprint $table) {
            $table->foreignId('project_investment_item_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('returned_item_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('project_action', 30)->nullable();
            $table->json('serial_numbers')->nullable();
        });
        Schema::table('project_item_changes', fn (Blueprint $table) => $table->json('details')->nullable());
        Schema::table('project_progress_logs', fn (Blueprint $table) => $table->json('details')->nullable());
    }

    public function down(): void
    {
        Schema::table('project_item_changes', fn (Blueprint $table) => $table->dropColumn('details'));
        Schema::table('project_progress_logs', fn (Blueprint $table) => $table->dropColumn('details'));
        Schema::table('stock_transfers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('project_investment_item_id');
            $table->dropConstrainedForeignId('returned_item_id');
            $table->dropColumn(['project_action', 'serial_numbers']);
        });
    }
};
