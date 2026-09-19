<?php

namespace App\Services\Places;

use App\Contracts\PlaceProvider;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class GooglePlacesProvider implements PlaceProvider
{
    public function nearby(float $latitude, float $longitude, int $radiusMeters, int $limit): array
    {
        $response = $this->client()
            ->withHeaders([
                'X-Goog-Api-Key' => $this->apiKey(),
                'X-Goog-FieldMask' => implode(',', [
                    'places.id',
                    'places.displayName',
                    'places.location',
                    'places.primaryType',
                    'places.formattedAddress',
                    'places.addressComponents',
                ]),
            ])
            ->post('https://places.googleapis.com/v1/places:searchNearby', [
                'languageCode' => 'it',
                'maxResultCount' => min(20, max(1, $limit)),
                'rankPreference' => 'DISTANCE',
                'locationRestriction' => [
                    'circle' => [
                        'center' => [
                            'latitude' => $latitude,
                            'longitude' => $longitude,
                        ],
                        'radius' => $radiusMeters,
                    ],
                ],
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Google Places lookup failed with status '.$response->status());
        }

        $places = collect($response->json('places', []))
            ->map(fn (array $place): ?array => $this->mapPlace($place))
            ->filter()
            ->values()
            ->all();

        return $places ?: $this->reverseArea($latitude, $longitude);
    }

    public function cacheable(): bool
    {
        // Google permits storing Place IDs, but not a persistent cache of the full Places payload.
        return false;
    }

    public function name(): string
    {
        return 'google';
    }

    private function reverseArea(float $latitude, float $longitude): array
    {
        $response = $this->client()->get('https://maps.googleapis.com/maps/api/geocode/json', [
            'key' => $this->apiKey(),
            'language' => 'it',
            'latlng' => "{$latitude},{$longitude}",
            'result_type' => 'route|neighborhood|locality',
        ]);

        if ($response->failed() || $response->json('status') !== 'OK') {
            return [];
        }

        foreach ($response->json('results', []) as $result) {
            $components = $result['address_components'] ?? [];
            $route = $this->component($components, ['route', 'neighborhood']);

            if (! $route || empty($result['place_id'])) {
                continue;
            }

            return [[
                'provider' => 'google',
                'provider_place_id' => (string) $result['place_id'],
                'name' => $route,
                'city' => $this->component($components, ['locality', 'administrative_area_level_3', 'administrative_area_level_2']) ?: 'Localita rilevata',
                'type' => $this->areaType($route),
                'location_kind' => 'area',
                'latitude' => $latitude,
                'longitude' => $longitude,
                'formatted_address' => $result['formatted_address'] ?? null,
            ]];
        }

        return [];
    }

    private function mapPlace(array $place): ?array
    {
        $id = $place['id'] ?? null;
        $name = $place['displayName']['text'] ?? null;
        $latitude = $place['location']['latitude'] ?? null;
        $longitude = $place['location']['longitude'] ?? null;

        if (! is_string($id) || ! is_string($name) || ! is_numeric($latitude) || ! is_numeric($longitude)) {
            return null;
        }

        $primaryType = (string) ($place['primaryType'] ?? '');

        return [
            'provider' => 'google',
            'provider_place_id' => $id,
            'name' => Str::squish($name),
            'city' => $this->googleComponent($place['addressComponents'] ?? [], [
                'locality',
                'administrative_area_level_3',
                'administrative_area_level_2',
            ]) ?: 'Localita rilevata',
            'type' => $this->spotOnType($primaryType, $name),
            'location_kind' => $this->isAreaType($primaryType) ? 'area' : 'poi',
            'latitude' => (float) $latitude,
            'longitude' => (float) $longitude,
            'formatted_address' => $place['formattedAddress'] ?? null,
        ];
    }

    private function spotOnType(string $primaryType, string $name): string
    {
        return match ($primaryType) {
            'subway_station', 'train_station', 'transit_station' => 'metro',
            'bus_station', 'bus_stop' => 'bus',
            'restaurant', 'meal_delivery', 'meal_takeaway' => 'ristorante',
            'bar', 'cafe', 'coffee_shop' => 'bar',
            'university', 'school' => 'universita',
            'night_club', 'dance_hall' => 'discoteca',
            'park', 'national_park', 'garden' => 'parco',
            default => $this->areaType($name),
        };
    }

    private function areaType(string $name): string
    {
        $name = Str::lower($name);

        return match (true) {
            Str::startsWith($name, ['piazza', 'largo']) => 'piazza',
            Str::contains($name, ['lungomare']) => 'lungomare',
            default => 'altro',
        };
    }

    private function isAreaType(string $type): bool
    {
        return in_array($type, ['route', 'street_address', 'neighborhood', 'locality', 'sublocality'], true);
    }

    private function googleComponent(array $components, array $wantedTypes): ?string
    {
        foreach ($wantedTypes as $wantedType) {
            foreach ($components as $component) {
                if (in_array($wantedType, $component['types'] ?? [], true)) {
                    return $component['longText'] ?? $component['shortText'] ?? null;
                }
            }
        }

        return null;
    }

    private function component(array $components, array $wantedTypes): ?string
    {
        foreach ($wantedTypes as $wantedType) {
            foreach ($components as $component) {
                if (in_array($wantedType, $component['types'] ?? [], true)) {
                    return $component['long_name'] ?? $component['short_name'] ?? null;
                }
            }
        }

        return null;
    }

    private function client(): PendingRequest
    {
        return Http::acceptJson()
            ->asJson()
            ->connectTimeout(2)
            ->timeout((int) config('spoton.smart_location.provider_timeout_seconds', 5));
    }

    private function apiKey(): string
    {
        $key = (string) config('spoton.smart_location.google_api_key');

        if ($key === '') {
            throw new RuntimeException('Google Places API key is not configured');
        }

        return $key;
    }
}
