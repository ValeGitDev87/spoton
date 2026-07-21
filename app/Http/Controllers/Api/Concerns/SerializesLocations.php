<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Models\Location;
use App\Models\PresenceSession;
use App\Support\LocationIcon;

trait SerializesLocations
{
    private function locationPayload(Location $location, ?float $distanceKm = null): array
    {
        $payload = [
            'id' => $location->id,
            'name' => $location->name,
            'short' => $location->short,
            'city' => $location->city,
            'type' => $location->type,
            'latitude' => (float) $location->latitude,
            'longitude' => (float) $location->longitude,
            'geo_radius_meters' => $location->geo_radius_meters,
            'icon' => $location->icon ?: LocationIcon::DEFAULT,
            'icon_library' => 'ionicons',
            'is_active' => $location->is_active,
            'stories_count' => (int) ($location->active_stories_count ?? 0),
            'connected_now_count' => PresenceSession::query()
                ->where('location_id', $location->id)
                ->whereNull('ended_at')
                ->where('last_ping_at', '>', now()->subMinutes(5))
                ->count(),
        ];

        if ($distanceKm !== null) {
            $payload['distance_km'] = round($distanceKm, 2);
        }

        return $payload;
    }
}
