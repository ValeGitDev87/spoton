<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('is_system')->default(false)->index();
        });
        Schema::table('posts', function (Blueprint $table): void {
            $table->string('source_name')->nullable();
            $table->string('source_url', 1024)->nullable();
        });
        Schema::create('imported_napoli_events', function (Blueprint $table): void {
            $table->id();
            $table->string('source');
            $table->string('external_id', 64);
            $table->foreignUuid('post_id')->constrained('posts')->cascadeOnDelete();
            $table->string('raw_hash', 64);
            $table->timestamps();
            $table->unique(['source', 'external_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('imported_napoli_events');
        Schema::table('posts', fn (Blueprint $table) => $table->dropColumn(['source_name', 'source_url']));
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('is_system'));
    }
};
