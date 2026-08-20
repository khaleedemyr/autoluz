<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('session_id', 100)->nullable();
            $table->string('visitor_name', 120)->nullable();
            $table->string('visitor_email', 160)->nullable();
            $table->string('status', 20)->default('open');
            $table->string('last_body', 180)->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'last_message_at']);
            $table->index(['user_id', 'status']);
            $table->index('session_id');
        });

        Schema::create('support_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('support_conversations')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('sender_type', 20);
            $table->string('body', 2000);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['conversation_id', 'id']);
            $table->index(['sender_type', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_messages');
        Schema::dropIfExists('support_conversations');
    }
};
