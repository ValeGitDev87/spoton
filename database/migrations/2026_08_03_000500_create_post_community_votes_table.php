<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table): void {
            $table->unsignedInteger('community_confirm_count')->default(0)->after('spot_on_count');
            $table->unsignedInteger('community_false_count')->default(0)->after('community_confirm_count');
            $table->string('community_status', 20)->default('pending')->after('community_false_count')->index();
        });

        Schema::create('post_community_votes', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('post_id')->constrained('posts')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('vote', 20);
            $table->timestamps();

            $table->unique(['post_id', 'user_id']);
            $table->index(['post_id', 'vote']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_community_votes');

        Schema::table('posts', function (Blueprint $table): void {
            $table->dropIndex(['community_status']);
            $table->dropColumn([
                'community_confirm_count',
                'community_false_count',
                'community_status',
            ]);
        });
    }
};
