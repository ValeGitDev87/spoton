<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chats', function (Blueprint $table): void {
            $table->foreignUuid('origin_challenge_id')->nullable()->after('user_two_id')->constrained('challenges')->nullOnDelete();
            $table->foreignUuid('origin_post_id')->nullable()->after('origin_challenge_id')->constrained('posts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('chats', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('origin_challenge_id');
            $table->dropConstrainedForeignId('origin_post_id');
        });
    }
};
