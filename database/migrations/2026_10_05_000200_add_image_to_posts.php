<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table): void {
            $table->string('image_disk')->nullable();
            $table->string('image_path')->nullable();
            $table->string('image_url', 1024)->nullable();
            $table->string('image_mime')->nullable();
            $table->unsignedInteger('image_size_bytes')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('posts', fn (Blueprint $table) => $table->dropColumn([
            'image_disk', 'image_path', 'image_url', 'image_mime', 'image_size_bytes',
        ]));
    }
};
