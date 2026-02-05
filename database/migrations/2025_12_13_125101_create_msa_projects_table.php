<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('msa_projects', function (Blueprint $table) {
            $table->id();
            $table->string('msa_code')->unique();
            $table->foreignId('project_investment_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->integer('quantity');
            $table->enum('issue_type', ['damaged', 'defective', 'malfunction', 'other'])->default('defective');
            $table->text('issue_description')->nullable();
            $table->enum('status', ['pending', 'in_repair', 'returned', 'replaced', 'closed'])->default('pending');
            $table->date('reported_date');
            $table->date('resolved_date')->nullable();
            $table->string('resolution')->nullable();
            $table->string('condition')->nullable();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('msa_projects');
    }
};
