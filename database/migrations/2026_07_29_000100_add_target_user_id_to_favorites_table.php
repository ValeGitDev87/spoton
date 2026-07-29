<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('favorites', function (Blueprint $table): void {
            $table->foreignUuid('target_user_id')
                ->nullable()
                ->after('owner_id')
                ->constrained('users')
                ->nullOnDelete();
            $table->index(['owner_id', 'target_user_id']);
        });

        DB::table('favorites')
            ->orderBy('created_at')
            ->get()
            ->each(function (object $favorite): void {
                $targetUserId = DB::table('users')
                    ->whereRaw('LOWER(display_name) = ?', [mb_strtolower(trim($favorite->target_name))])
                    ->value('id');

                if ($targetUserId) {
                    DB::table('favorites')
                        ->where('id', $favorite->id)
                        ->update(['target_user_id' => $targetUserId]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('favorites', function (Blueprint $table): void {
            $table->dropIndex(['owner_id', 'target_user_id']);
            $table->dropConstrainedForeignId('target_user_id');
        });
    }
};
