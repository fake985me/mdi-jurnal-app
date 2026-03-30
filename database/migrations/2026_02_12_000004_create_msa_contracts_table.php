<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('msa_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_investment_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->string('msa_code')->unique();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->decimal('sharing_profit_rate', 5, 2)->nullable();
            $table->enum('status', ['draft', 'active', 'expired', 'cancelled'])->default('active');
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('msa_contracts');
    }
};
