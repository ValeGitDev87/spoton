<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('presence_sessions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('location_id')->constrained('locations')->cascadeOnDelete();
            $table->timestamp('started_at');
            $table->timestamp('last_ping_at');
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('location_id');
            $table->index('last_ping_at');
            $table->index(['location_id', 'last_ping_at']);
            $table->index(['user_id', 'location_id', 'ended_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('presence_sessions');
    }
};
