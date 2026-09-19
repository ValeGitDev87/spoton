<?php

namespace App\Services;

use App\Contracts\PlaceProvider;
use App\Models\Location;
use App\Models\User;
use App\Services\Places\PlaceCandidateToken;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class SmartLocationLookupService
{
    public function __construct(
        private readonly PlaceProvider $provider,
        private readonly PlaceCandidateToken $tokens,
    ) {}

    public function lookup(User $user, bool $includeProvider = false): array
    {
        [$latitude, $longitude] = $this->verifiedPosition($user);
        $existing = $this->existingLocations($latitude, $longitude);

        if ($existing !== [] && ! $includeProvider) {
            Log::info('smart_location.lookup', [
                'candidate_count' => count($existing),
                'cell' => $this->cell($latitude, $longitude),
                'source' => 'spoton',
                'user_id' => $user->id,
            ]);

            return [
                'existing' => $existing,
                'provider' => [],
                'provider_available' => $this->provider->name() !== 'none',
                'provider_failed' => false,
            ];
        }

        $providerLookup = $this->providerCandidates($latitude, $longitude, $user);

        return [
            'existing' => $existing,
            'provider' => $providerLookup['candidates'],
            'provider_available' => $this->provider->name() !== 'none',
            'provider_failed' => $providerLookup['failed'],
        ];
    }

    public function verifiedPosition(User $user): array
    {
        $maxAgeMinutes = (int) config('spoton.community_locations.position_max_age_minutes', 10);
        $maxAccuracyMeters = (int) config('spoton.community_locations.max_accuracy_meters', 100);

        if ($user->last_known_latitude === null
            || $user->last_known_longitude === null
            || ! $user->last_location_update?->isAfter(now()->subMinutes($maxAgeMinutes))) {
            throw ValidationException::withMessages([
                'position' => ['Rileva la posizione prima di aggiungere il luogo.'],
            ]);
        }

        if ($user->last_location_accuracy_meters === null
            || $user->last_location_accuracy_meters > $maxAccuracyMeters) {
            throw ValidationException::withMessages([
                'position' => ["La precisione GPS deve essere entro {$maxAccuracyMeters} metri."],
            ]);
        }

        return [(float) $user->last_known_latitude, (float) $user->last_known_longitude];
    }

    private function existingLocations(float $latitude, float $longitude): array
    {
        $radiusMeters = (int) config('spoton.smart_location.nearby_radius_meters', 150);
        $latitudeDelta = $radiusMeters / 111_320;
        $longitudeScale = max(0.01, abs(cos(deg2rad($latitude))));
        $longitudeDelta = $radiusMeters / (111_320 * $longitudeScale);

        return Location::query()
            ->publiclyVisible()
            ->whereBetween('latitude', [$latitude - $latitudeDelta, $latitude + $latitudeDelta])
            ->whereBetween('longitude', [$longitude - $longitudeDelta, $longitude + $longitudeDelta])
            ->get()
            ->map(fn (Location $location): array => [
                'distance_meters' => (int) round(GeoDistance::kilometers(
                    $latitude,
                    $longitude,
                    (float) $location->latitude,
                    (float) $location->longitude,
                ) * 1000),
                'location' => $location,
            ])
            ->filter(fn (array $candidate): bool => $candidate['distance_meters'] <= $radiusMeters)
            ->sortBy('distance_meters')
            ->take((int) config('spoton.smart_location.candidate_limit', 5))
            ->values()
            ->all();
    }

    private function providerCandidates(float $latitude, float $longitude, User $user): array
    {
        if ($this->provider->name() === 'none') {
            return ['candidates' => [], 'failed' => false];
        }

        $cell = $this->cell($latitude, $longitude);
        $cacheKey = "smart_location:provider:{$this->provider->name()}:{$cell}";
        $startedAt = hrtime(true);
        $cacheHit = $this->provider->cacheable() && Cache::has($cacheKey);

        try {
            $lookup = fn (): array => $this->provider->nearby(
                $latitude,
                $longitude,
                (int) config('spoton.smart_location.provider_radius_meters', 150),
                (int) config('spoton.smart_location.candidate_limit', 5),
            );
            $candidates = $this->provider->cacheable()
                ? Cache::remember(
                    $cacheKey,
                    now()->addSeconds((int) config('spoton.smart_location.cache_ttl_seconds', 86400)),
                    $lookup,
                )
                : $lookup();
        } catch (Throwable $exception) {
            Log::warning('smart_location.provider_failed', [
                'cell' => $cell,
                'provider' => $this->provider->name(),
                'user_id' => $user->id,
                'exception' => $exception::class,
            ]);

            return ['candidates' => [], 'failed' => true];
        }

        Log::info('smart_location.provider_lookup', [
            'cache' => $cacheHit ? 'hit' : 'miss',
            'candidate_count' => count($candidates),
            'cell' => $cell,
            'latency_ms' => (int) round((hrtime(true) - $startedAt) / 1_000_000),
            'provider' => $this->provider->name(),
            'user_id' => $user->id,
        ]);

        return [
            'candidates' => collect($candidates)
            ->take((int) config('spoton.smart_location.candidate_limit', 5))
            ->map(fn (array $candidate): array => [
                'candidate_token' => $this->tokens->encode($candidate, $user->id),
                'formatted_address' => $candidate['formatted_address'] ?? null,
                'location_kind' => $candidate['location_kind'],
                'name' => $candidate['name'],
                'city' => $candidate['city'],
                'type' => $candidate['type'],
            ])
            ->values()
            ->all(),
            'failed' => false,
        ];
    }

    private function cell(float $latitude, float $longitude): string
    {
        return sprintf('%.3f:%.3f', round($latitude, 3), round($longitude, 3));
    }
}
