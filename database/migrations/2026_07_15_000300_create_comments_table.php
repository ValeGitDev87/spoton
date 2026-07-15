<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comments', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('post_id')->constrained('posts')->cascadeOnDelete();
            $table->foreignUuid('author_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('tagged_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('text');
            $table->timestamps();

            $table->index(['post_id', 'created_at']);
            $table->index('author_id');
            $table->index('tagged_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
