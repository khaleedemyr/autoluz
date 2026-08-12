<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('community_posts', function (Blueprint $table) {
            $table->foreignId('article_id')
                ->nullable()
                ->after('group_id')
                ->constrained('articles')
                ->nullOnDelete();
            $table->foreignId('event_id')
                ->nullable()
                ->after('article_id')
                ->constrained('events')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('community_posts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('article_id');
            $table->dropConstrainedForeignId('event_id');
        });
    }
};
