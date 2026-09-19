<?php

namespace App\Services;

use App\Models\Location;
use Illuminate\Support\Collection;

class LocationDuplicateFinder
{
    public function __construct(private readonly LocationNameNormalizer $normalizer) {}

    /** @return Collection<int, array{location: Location, distance_meters: int}> */
    public function find(
        float $latitude,
        float $longitude,
        string $name,
        string $type,
        ?string $provider = null,
        ?string $providerPlaceId = null,
        string $locationKind = 'poi',
    ): Collection {
        if ($provider && $providerPlaceId) {
            $providerMatch = $this->findByProvider($provider, $providerPlaceId);

            if ($providerMatch) {
                return collect([[
                    'location' => $providerMatch,
                    'distance_meters' => (int) round(GeoDistance::kilometers(
                        $latitude,
                        $longitude,
                        (float) $providerMatch->latitude,
                        (float) $providerMatch->longitude,
                    ) * 1000),
                ]]);
            }
        }

        $radiusMeters = (int) config('spoton.community_locations.duplicate_radius_meters', 150);
        $latitudeDelta = $radiusMeters / 111_320;
        $longitudeScale = max(0.01, abs(cos(deg2rad($latitude))));
        $longitudeDelta = $radiusMeters / (111_320 * $longitudeScale);
        $normalizedName = $this->normalizer->normalize($name, $locationKind);

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
            ->filter(function (array $candidate) use ($locationKind, $normalizedName, $type, $radiusMeters): bool {
                if ($candidate['distance_meters'] > $radiusMeters) {
                    return false;
                }

                $location = $candidate['location'];
                $candidateKind = $location->location_kind ?: 'poi';
                $candidateName = $location->normalized_name
                    ?: $this->normalizer->normalize($location->name, $candidateKind);

                if ($candidateName === $normalizedName) {
                    return true;
                }

                if ($locationKind === 'area' || $candidateKind === 'area') {
                    return false;
                }

                similar_text($normalizedName, $candidateName, $similarity);

                return $location->type === $type && $similarity >= 80;
            })
            ->sortBy('distance_meters')
            ->take(5)
            ->values();
    }

    public function findByProvider(string $provider, string $providerPlaceId): ?Location
    {
        return Location::query()
            ->where('provider', $provider)
            ->where('provider_place_id', $providerPlaceId)
            ->where('is_active', true)
            ->whereIn('moderation_status', [
                Location::MODERATION_PENDING,
                Location::MODERATION_APPROVED,
            ])
            ->first();
    }

    public function findAnyByProvider(string $provider, string $providerPlaceId): ?Location
    {
        return Location::query()
            ->where('provider', $provider)
            ->where('provider_place_id', $providerPlaceId)
            ->first();
    }
}
