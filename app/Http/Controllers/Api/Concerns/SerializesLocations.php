<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Models\Location;

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
            'icon' => $location->icon,
            'is_active' => $location->is_active,
            'connected_now_count' => 0,
        ];

        if ($distanceKm !== null) {
            $payload['distance_km'] = round($distanceKm, 2);
        }

        return $payload;
    }
}
