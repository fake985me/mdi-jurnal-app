<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();

            $table->string('slug')->unique();
            $table->string('title');

            $table->string('meta_description')->nullable();
            $table->string('meta_keywords')->nullable();

            // status publish
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();

            // navigasi
            $table->boolean('show_in_nav')->default(false);
            $table->integer('nav_order')->default(0);
            $table->string('nav_parent')->nullable();
            $table->string('nav_label')->nullable();

            // relasi & audit
            $table->unsignedBigInteger('template_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();

            $table->index('slug');
            $table->index('is_published');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
