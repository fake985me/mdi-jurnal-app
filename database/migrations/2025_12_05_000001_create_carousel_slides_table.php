<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('carousel_slides', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->text('text'); // Caption/description text

            // Image URLs
            $table->string('bg_image')->nullable(); // Background image
            $table->string('center_img')->nullable(); // Center logo/image
            $table->string('img_left')->nullable(); // Left product image
            $table->string('mid_left')->nullable(); // Mid-left product image
            $table->string('mid_right')->nullable(); // Mid-right product image
            $table->string('img_right')->nullable(); // Right product image

            // Component options
            $table->boolean('use_component')->default(false); // Use LineAnimationSVG component
            $table->string('component_name')->nullable(); // Component name if use_component is true

            // Display settings
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('carousel_slides');
    }
};
