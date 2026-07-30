<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chats', function (Blueprint $table): void {
            $table->dropUnique(['user_one_id', 'user_two_id']);
            $table->string('conversation_key')->nullable()->after('id');
            $table->string('context_type')->default('direct')->after('conversation_key');
            $table->foreignUuid('ghost_owner_id')->nullable()->after('origin_post_id')->constrained('users')->nullOnDelete();
            $table->timestamp('ghost_identity_revealed_at')->nullable()->after('ghost_owner_id');
        });

        DB::table('chats')
            ->select(['id', 'user_one_id', 'user_two_id'])
            ->orderBy('id')
            ->get()
            ->each(function (object $chat): void {
                [$one, $two] = strcmp($chat->user_one_id, $chat->user_two_id) < 0
                    ? [$chat->user_one_id, $chat->user_two_id]
                    : [$chat->user_two_id, $chat->user_one_id];

                DB::table('chats')
                    ->where('id', $chat->id)
                    ->update([
                        'user_one_id' => $one,
                        'user_two_id' => $two,
                        'conversation_key' => "direct:{$one}:{$two}",
                        'context_type' => 'direct',
                    ]);
            });

        Schema::table('chats', function (Blueprint $table): void {
            $table->string('conversation_key')->nullable(false)->change();
            $table->unique('conversation_key');
            $table->index(['context_type', 'origin_post_id']);
        });
    }

    public function down(): void
    {
        Schema::table('chats', function (Blueprint $table): void {
            $table->dropIndex(['context_type', 'origin_post_id']);
            $table->dropUnique(['conversation_key']);
            $table->dropConstrainedForeignId('ghost_owner_id');
            $table->dropColumn([
                'conversation_key',
                'context_type',
                'ghost_identity_revealed_at',
            ]);
            $table->unique(['user_one_id', 'user_two_id']);
        });
    }
};
