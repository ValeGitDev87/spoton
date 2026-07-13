<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chats', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_one_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('user_two_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_one_id', 'user_two_id']);
            $table->index('user_two_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chats');
    }
};
