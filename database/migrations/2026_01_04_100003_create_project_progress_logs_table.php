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
        Schema::create('project_progress_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_investment_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['delivery', 'installation', 'status', 'material', 'other']);
            $table->string('field_name')->nullable(); // e.g., delivery_progress, status
            $table->string('old_value')->nullable();
            $table->string('new_value')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('updated_by')->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_progress_logs');
    }
};
