<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('rmas', function (Blueprint $table) {
            $table->boolean('warranty_claim_flow')->default(false);
            $table->timestamp('inspected_at')->nullable();
        });
    }
    public function down(): void
    {
        Schema::table('rmas', fn (Blueprint $table) => $table->dropColumn(['warranty_claim_flow', 'inspected_at']));
    }
};
