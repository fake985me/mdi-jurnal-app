<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_rates', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // PPN, PPh 23, etc.
            $table->string('code')->unique(); // ppn, pph23, etc.
            $table->decimal('rate', 5, 2); // e.g., 11.00 for 11%
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Seed default tax rates
        DB::table('tax_rates')->insert([
            ['name' => 'PPN', 'code' => 'ppn', 'rate' => 11.00, 'description' => 'Pajak Pertambahan Nilai', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'PPh 23', 'code' => 'pph23', 'rate' => 2.00, 'description' => 'Pajak Penghasilan Pasal 23', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_rates');
    }
};
