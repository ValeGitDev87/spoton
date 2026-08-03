<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chats', function (Blueprint $table): void {
            $table->timestamp('user_one_cleared_at')->nullable()->after('user_two_id');
            $table->timestamp('user_two_cleared_at')->nullable()->after('user_one_cleared_at');
        });
    }

    public function down(): void
    {
        Schema::table('chats', function (Blueprint $table): void {
            $table->dropColumn(['user_one_cleared_at', 'user_two_cleared_at']);
        });
    }
};
