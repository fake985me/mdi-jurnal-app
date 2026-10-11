<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('delivery_fulfillments', function (Blueprint $table) {
            $table->id();
            $table->string('request_key')->unique();
            $table->string('status')->default('preparing');
            $table->string('courier')->nullable();
            $table->string('tracking_number')->nullable();
            $table->text('destination')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('delivery_fulfillments');
    }
};
