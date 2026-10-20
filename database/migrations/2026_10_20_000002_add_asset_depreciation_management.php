<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->string('depreciation_method', 30)->default('none');
            $table->date('depreciation_start_date')->nullable();
            $table->unsignedInteger('useful_life_months')->nullable();
            $table->decimal('residual_value', 15, 2)->default(0);
            $table->decimal('accumulated_depreciation', 15, 2)->default(0);
            $table->date('disposal_date')->nullable();
            $table->decimal('disposal_proceeds', 15, 2)->nullable();
            $table->decimal('disposal_book_value', 15, 2)->nullable();
            $table->text('disposal_reason')->nullable();
        });
        Schema::create('asset_depreciations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->restrictOnDelete();
            $table->date('period');
            $table->decimal('amount', 15, 2);
            $table->decimal('book_value_after', 15, 2);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
            $table->unique(['asset_id', 'period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_depreciations');
        Schema::table('assets', fn (Blueprint $table) => $table->dropColumn([
            'depreciation_method', 'depreciation_start_date', 'useful_life_months', 'residual_value',
            'accumulated_depreciation', 'disposal_date', 'disposal_proceeds', 'disposal_book_value', 'disposal_reason',
        ]));
    }
};
