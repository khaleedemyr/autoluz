<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('videos', function (Blueprint $table) {
            $table->id();
            $table->string('title', 180);
            $table->string('youtube_url', 500);
            $table->string('youtube_id', 20);
            $table->string('embed_url', 500);
            $table->enum('video_type', ['short', 'long'])->default('short');
            $table->string('duration_label', 30)->nullable();
            $table->string('thumbnail_url', 600)->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'sort_order'], 'idx_videos_active_sort');
            $table->index('youtube_id', 'idx_videos_youtube_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('videos');
    }
};
