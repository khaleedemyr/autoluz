<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->string('meta_title', 70)->nullable()->after('excerpt');
            $table->string('meta_description', 180)->nullable()->after('meta_title');
            $table->string('focus_keyword', 120)->nullable()->after('meta_description');
            $table->string('canonical_url', 500)->nullable()->after('focus_keyword');
            $table->string('og_title', 120)->nullable()->after('canonical_url');
            $table->string('og_description', 200)->nullable()->after('og_title');
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn([
                'meta_title',
                'meta_description',
                'focus_keyword',
                'canonical_url',
                'og_title',
                'og_description',
            ]);
        });
    }
};
