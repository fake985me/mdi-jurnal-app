<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('returned_items', function (Blueprint $table) {
            $table->id();
            $table->string('source_key')->unique();
            $table->string('source_type');
            $table->unsignedBigInteger('source_id');
            $table->string('source_code')->nullable();
            $table->foreignId('product_id')->constrained();
            $table->string('serial_number')->nullable();
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('available_quantity')->default(0);
            $table->string('condition')->default('defective');
            $table->decimal('book_value', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('inspected_by')->nullable();
            $table->timestamp('inspected_at')->nullable();
            $table->timestamps();
        });
        Schema::table('project_investment_items', function (Blueprint $table) {
            $table->json('serial_numbers')->nullable();
            $table->unsignedBigInteger('returned_item_id')->nullable();
        });
        Schema::table('msa_projects', function (Blueprint $table) {
            $table->json('serial_numbers')->nullable();
            $table->json('replacement_serial_numbers')->nullable();
        });
        Schema::create('project_item_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_investment_id')->constrained();
            $table->foreignId('project_investment_item_id')->constrained();
            $table->string('type');
            $table->unsignedInteger('quantity');
            $table->json('old_serial_numbers')->nullable();
            $table->json('new_serial_numbers')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('project_item_changes');
        Schema::table('msa_projects', fn (Blueprint $table) => $table->dropColumn(['serial_numbers', 'replacement_serial_numbers']));
        Schema::table('project_investment_items', fn (Blueprint $table) => $table->dropColumn(['serial_numbers', 'returned_item_id']));
        Schema::dropIfExists('returned_items');
    }
};
