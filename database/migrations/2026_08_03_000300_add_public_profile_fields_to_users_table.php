<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('motto', 160)->nullable()->after('bio');
            $table->string('favorite_song', 255)->nullable()->after('motto');
            $table->boolean('show_bio')->default(false)->after('favorite_song');
            $table->boolean('show_motto')->default(false)->after('show_bio');
            $table->boolean('show_favorite_song')->default(false)->after('show_motto');
            $table->json('public_photo_urls')->nullable()->after('photos');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'motto',
                'favorite_song',
                'show_bio',
                'show_motto',
                'show_favorite_song',
                'public_photo_urls',
            ]);
        });
    }
};
