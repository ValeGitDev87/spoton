<?php

namespace App\Services;

use App\Models\Location;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class LocationDuplicateFinder
{
    /** @return Collection<int, array{location: Location, distance_meters: int}> */
    public function find(float $latitude, float $longitude, string $name, string $type): Collection
    {
        $radiusMeters = (int) config('spoton.community_locations.duplicate_radius_meters', 150);
        $latitudeDelta = $radiusMeters / 111_320;
        $longitudeScale = max(0.01, abs(cos(deg2rad($latitude))));
        $longitudeDelta = $radiusMeters / (111_320 * $longitudeScale);
        $normalizedName = $this->normalizeName($name);

        return Location::query()
            ->where('is_active', true)
            ->whereIn('moderation_status', [
                Location::MODERATION_PENDING,
                Location::MODERATION_APPROVED,
            ])
            ->whereBetween('latitude', [$latitude - $latitudeDelta, $latitude + $latitudeDelta])
            ->whereBetween('longitude', [$longitude - $longitudeDelta, $longitude + $longitudeDelta])
            ->get()
            ->map(function (Location $location) use ($latitude, $longitude): array {
                return [
                    'location' => $location,
                    'distance_meters' => (int) round(GeoDistance::kilometers(
                        $latitude,
                        $longitude,
                        (float) $location->latitude,
                        (float) $location->longitude,
                    ) * 1000),
                ];
            })
            ->filter(function (array $candidate) use ($normalizedName, $type, $radiusMeters): bool {
                if ($candidate['distance_meters'] > $radiusMeters) {
                    return false;
                }

                $location = $candidate['location'];
                $candidateName = $this->normalizeName($location->name);

                if ($candidateName === $normalizedName) {
                    return true;
                }

                similar_text($normalizedName, $candidateName, $similarity);

                return $location->type === $type && $similarity >= 80;
            })
            ->sortBy('distance_meters')
            ->take(5)
            ->values();
    }

    private function normalizeName(string $name): string
    {
        return Str::of($name)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', ' ')
            ->squish()
            ->value();
    }
}
