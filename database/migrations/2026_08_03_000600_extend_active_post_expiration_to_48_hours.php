<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('posts')
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->get(['id', 'created_at', 'expires_at'])
            ->each(function (object $post): void {
                $currentExpiration = Carbon::parse($post->expires_at);
                $newExpiration = Carbon::parse($post->created_at)->addHours(48);

                if ($newExpiration->isAfter($currentExpiration)) {
                    DB::table('posts')
                        ->where('id', $post->id)
                        ->update(['expires_at' => $newExpiration]);
                }
            });
    }

    public function down(): void
    {
        // Existing expiration timestamps are not shortened during rollback.
    }
};
