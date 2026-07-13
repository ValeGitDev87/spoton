<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('chat_id')->constrained('chats')->cascadeOnDelete();
            $table->foreignUuid('sender_id')->constrained('users')->cascadeOnDelete();
            $table->text('text');
            $table->timestamp('sent_at');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index('chat_id');
            $table->index('sender_id');
            $table->index('sent_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
