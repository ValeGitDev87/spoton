<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('auth_provider')->default('email')->after('password')->index();
            $table->string('avatar_url')->nullable()->after('avatar_color');
            $table->text('bio')->nullable()->after('avatar_url');
            $table->json('photos')->nullable()->after('bio');
            $table->unsignedInteger('karma')->default(0)->after('photos');
        });

        Schema::table('posts', function (Blueprint $table): void {
            $table->string('song_quote', 255)->nullable()->after('musica');
            $table->boolean('is_anonymous')->default(false)->after('sighting_date')->index();
            $table->text('secret_question')->nullable()->after('is_anonymous');
            $table->string('secret_answer_hash')->nullable()->after('secret_question');
            $table->unsignedInteger('spot_on_count')->default(0)->after('io_cero_count');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table): void {
            $table->dropColumn([
                'song_quote',
                'is_anonymous',
                'secret_question',
                'secret_answer_hash',
                'spot_on_count',
            ]);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'auth_provider',
                'avatar_url',
                'bio',
                'photos',
                'karma',
            ]);
        });
    }
};
