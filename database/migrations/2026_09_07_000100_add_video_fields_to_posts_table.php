<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table): void {
            $table->string('video_disk')->nullable()->after('audio_duration_seconds');
            $table->string('video_path')->nullable()->after('video_disk');
            $table->string('video_url')->nullable()->after('video_path');
            $table->string('video_mime')->nullable()->after('video_url');
            $table->unsignedInteger('video_size_bytes')->nullable()->after('video_mime');
            $table->unsignedSmallInteger('video_duration_seconds')->nullable()->after('video_size_bytes');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table): void {
            $table->dropColumn([
                'video_disk',
                'video_path',
                'video_url',
                'video_mime',
                'video_size_bytes',
                'video_duration_seconds',
            ]);
        });
    }
};
