<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 220)->unique();
            $table->string('title');
            $table->text('excerpt')->nullable();
            $table->longText('body_html')->nullable();
            $table->string('cover_image_url', 600)->nullable();
            $table->string('location')->nullable();
            $table->string('venue')->nullable();
            $table->string('city')->nullable();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at')->nullable();
            $table->string('registration_url', 600)->nullable();
            $table->boolean('is_featured')->default(false);
            $table->enum('status', ['draft', 'published'])->default('draft');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['status', 'starts_at'], 'idx_events_status_starts');
            $table->index(['is_featured', 'status'], 'idx_events_featured_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
