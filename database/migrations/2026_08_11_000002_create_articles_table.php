<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('legacy_wp_id')->nullable()->unique();
            $table->string('slug', 220)->unique();
            $table->string('title');
            $table->text('excerpt')->nullable();
            $table->longText('content_html');
            $table->string('featured_image_url', 600)->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_slider')->default(false);
            $table->string('slider_image_url', 600)->nullable();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->dateTime('published_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'published_at'], 'idx_articles_status_published_at');
            $table->index('category_id', 'idx_articles_category_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
