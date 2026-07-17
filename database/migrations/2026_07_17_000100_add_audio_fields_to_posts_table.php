<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table): void {
            $table->string('audio_disk')->nullable()->after('song_quote');
            $table->string('audio_path')->nullable()->after('audio_disk');
            $table->string('audio_url')->nullable()->after('audio_path');
            $table->string('audio_mime', 120)->nullable()->after('audio_url');
            $table->unsignedInteger('audio_size_bytes')->nullable()->after('audio_mime');
            $table->unsignedTinyInteger('audio_duration_seconds')->nullable()->after('audio_size_bytes');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table): void {
            $table->dropColumn([
                'audio_disk',
                'audio_path',
                'audio_url',
                'audio_mime',
                'audio_size_bytes',
                'audio_duration_seconds',
            ]);
        });
    }
};
