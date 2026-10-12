<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rmas', fn (Blueprint $table) => $table->string('returned_serial_number')->nullable());
    }

    public function down(): void
    {
        Schema::table('rmas', fn (Blueprint $table) => $table->dropColumn('returned_serial_number'));
    }
};
