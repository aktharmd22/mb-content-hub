<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inbox_conversations', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->string('context_type', 30)->nullable();        // 'article' | 'viral_package' | null
            $table->unsignedBigInteger('context_id')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();

            $table->index(['context_type', 'context_id']);
            $table->index('last_message_at');
        });

        Schema::create('inbox_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('inbox_conversations')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('last_read_at')->nullable();
            $table->boolean('pinned')->default(false);
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();

            $table->unique(['conversation_id', 'user_id']);
            $table->index(['user_id', 'last_read_at']);
        });

        Schema::create('inbox_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('inbox_conversations')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->string('attachment_drive_file_id', 128)->nullable();
            $table->string('attachment_filename')->nullable();
            $table->string('attachment_mime_type', 100)->nullable();
            $table->unsignedBigInteger('attachment_size')->nullable();
            $table->json('mentions')->nullable();        // array of user IDs mentioned
            $table->timestamps();

            $table->index(['conversation_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inbox_messages');
        Schema::dropIfExists('inbox_participants');
        Schema::dropIfExists('inbox_conversations');
    }
};
