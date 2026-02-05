<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_sections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('page_id');
            $table->string('section_type'); // hero, features, gallery, cta, testimonials, contact, etc.
            $table->integer('order')->default(0);
            $table->json('content'); // Section content (title, subtitle, images, etc.)
            $table->json('settings')->nullable(); // Section settings (colors, spacing, etc.)
            $table->boolean('is_visible')->default(true);
            $table->timestamps();
            
            $table->index(['page_id', 'order']);
            $table->foreign('page_id')->references('id')->on('pages')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_sections');
    }
};
