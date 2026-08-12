<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('legacy_wp_term_id')->nullable()->unique();
            $table->string('name', 120);
            $table->string('slug', 180)->unique();
            $table->string('description', 255)->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['parent_id', 'sort_order', 'name'], 'idx_categories_parent_sort');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
