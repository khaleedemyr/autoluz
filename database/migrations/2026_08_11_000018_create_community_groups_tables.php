<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('community_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('creator_id')->constrained('users')->cascadeOnDelete();
            $table->string('name', 80);
            $table->string('slug', 100)->unique();
            $table->string('description', 300)->nullable();
            $table->string('cover_path', 500)->nullable();
            $table->unsignedInteger('members_count')->default(0);
            $table->unsignedInteger('posts_count')->default(0);
            $table->timestamps();

            $table->index(['members_count', 'created_at']);
        });

        Schema::create('community_group_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('community_groups')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 20)->default('member'); // owner|admin|member
            $table->timestamps();

            $table->unique(['group_id', 'user_id']);
            $table->index(['user_id', 'created_at']);
        });

        Schema::table('community_posts', function (Blueprint $table) {
            $table->foreignId('group_id')
                ->nullable()
                ->after('parent_id')
                ->constrained('community_groups')
                ->nullOnDelete();

            $table->index(['group_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('community_posts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('group_id');
        });

        Schema::dropIfExists('community_group_members');
        Schema::dropIfExists('community_groups');
    }
};
