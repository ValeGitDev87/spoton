<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('author_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('location_id')->constrained('locations')->cascadeOnDelete();
            $table->text('text');
            $table->string('musica', 255)->nullable();
            $table->date('sighting_date');
            $table->timestamp('expires_at');
            $table->unsignedInteger('like_count')->default(0);
            $table->unsignedInteger('comment_count')->default(0);
            $table->unsignedInteger('share_count')->default(0);
            $table->unsignedInteger('io_cero_count')->default(0);
            $table->string('status')->default('active');
            $table->timestamps();

            $table->index('author_id');
            $table->index('location_id');
            $table->index('status');
            $table->index('created_at');
            $table->index('expires_at');
            $table->index(['status', 'created_at']);
            $table->index(['location_id', 'status', 'created_at']);
            $table->index(['author_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
