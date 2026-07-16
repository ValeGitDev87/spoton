<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('challenges', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('post_id')->constrained('posts')->cascadeOnDelete();
            $table->string('origin')->index();
            $table->foreignUuid('challenger_id')->constrained('users')->cascadeOnDelete();
            $table->string('target_type')->index();
            $table->foreignUuid('target_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('source_comment_id')->nullable()->constrained('comments')->nullOnDelete();
            $table->text('question');
            $table->string('answer_hash');
            $table->string('status')->default('pending')->index();
            $table->text('counter_text')->nullable();
            $table->foreignUuid('counter_proposed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['target_user_id', 'status']);
            $table->index(['challenger_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('challenges');
    }
};
