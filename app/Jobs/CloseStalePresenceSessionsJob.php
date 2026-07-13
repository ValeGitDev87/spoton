<?php

namespace App\Jobs;

use App\Models\PresenceSession;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CloseStalePresenceSessionsJob implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        PresenceSession::query()
            ->whereNull('ended_at')
            ->where('last_ping_at', '<', now()->subMinutes(5))
            ->update(['ended_at' => now()]);
    }
}
