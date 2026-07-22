<?php

namespace App\Jobs;

use App\Models\PresenceSession;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class PurgeLocationDataJob implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        $locationRetentionHours = max(1, (int) config('spoton.privacy.location_retention_hours', 24));
        $presenceRetentionDays = max(1, (int) config('spoton.privacy.presence_retention_days', 30));

        User::query()
            ->whereNotNull('last_location_update')
            ->where('last_location_update', '<', now()->subHours($locationRetentionHours))
            ->update([
                'last_known_latitude' => null,
                'last_known_longitude' => null,
                'last_location_update' => null,
            ]);

        PresenceSession::query()
            ->whereNotNull('ended_at')
            ->where('ended_at', '<', now()->subDays($presenceRetentionDays))
            ->delete();
    }
}
