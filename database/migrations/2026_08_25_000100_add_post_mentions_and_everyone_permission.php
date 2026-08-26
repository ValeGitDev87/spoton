<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('can_mention_everyone')->default(false);
        });

        Schema::table('posts', function (Blueprint $table): void {
            $table->boolean('mentions_everyone')->default(false);
        });

        Schema::create('post_mentions', function (Blueprint $table): void {
            $table->foreignUuid('post_id')->constrained('posts')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['post_id', 'user_id']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_mentions');

        Schema::table('posts', function (Blueprint $table): void {
            $table->dropColumn('mentions_everyone');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('can_mention_everyone');
        });
    }
};
