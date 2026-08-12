<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug', 220)->unique();
            $table->string('body_type', 80)->nullable(); // MPV, SUV, Sedan, Scooter, Sport...
            $table->string('model_year', 20)->nullable();
            $table->text('excerpt')->nullable();
            $table->longText('description_html')->nullable();
            $table->json('specs')->nullable(); // [{label, value}, ...]
            $table->string('cover_image_url', 600)->nullable();
            $table->unsignedBigInteger('price_from')->nullable();
            $table->string('price_currency', 8)->default('IDR');
            $table->enum('status', ['draft', 'published'])->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['brand_id', 'status', 'sort_order']);
        });

        Schema::create('vehicle_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->string('image_url', 600);
            $table->string('caption')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_images');
        Schema::dropIfExists('vehicles');
    }
};
