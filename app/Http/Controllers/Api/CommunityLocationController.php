<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\SerializesLocations;
use App\Http\Controllers\Controller;
use App\Http\Requests\Location\FindLocationDuplicatesRequest;
use App\Http\Requests\Location\StoreCommunityLocationRequest;
use App\Models\Location;
use App\Services\GeoDistance;
use App\Services\LocationDuplicateFinder;
use App\Support\LocationType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CommunityLocationController extends Controller
{
    use SerializesLocations;

    public function store(
        StoreCommunityLocationRequest $request,
        LocationDuplicateFinder $duplicateFinder,
    ): JsonResponse {
        $user = $request->user();

        abort_unless($user->hasVerifiedEmail(), 403, 'Verifica la tua email prima di aggiungere un luogo.');

        $this->ensureRecentAccuratePosition($request);

        $latitude = (float) $request->validated('latitude');
        $longitude = (float) $request->validated('longitude');
        $distanceMeters = GeoDistance::kilometers(
            (float) $user->last_known_latitude,
            (float) $user->last_known_longitude,
            $latitude,
            $longitude,
        ) * 1000;
        $maxDistanceMeters = (int) config('spoton.community_locations.max_distance_meters', 1000);

        if ($distanceMeters > $maxDistanceMeters) {
            throw ValidationException::withMessages([
                'latitude' => ["Puoi aggiungere soltanto luoghi entro {$maxDistanceMeters} metri dalla tua posizione."],
            ]);
        }

        $dailyLimit = (int) config('spoton.community_locations.daily_limit', 3);
        $createdToday = Location::query()
            ->where('created_by_user_id', $user->id)
            ->where('created_at', '>=', now()->subDay())
            ->count();

        abort_if(
            $createdToday >= $dailyLimit,
            429,
            "Hai raggiunto il limite di {$dailyLimit} luoghi nelle ultime 24 ore.",
        );

        $duplicates = $duplicateFinder->find(
            $latitude,
            $longitude,
            $request->validated('name'),
            $request->validated('type'),
        );

        if ($duplicates->isNotEmpty()) {
            return response()->json([
                'message' => 'Esiste gia un luogo simile nelle vicinanze.',
                'errors' => [
                    'location' => ['Scegli il luogo esistente oppure modifica nome e posizione.'],
                ],
                'data' => [
                    'candidates' => $this->duplicatePayload($duplicates),
                ],
            ], 409);
        }

        $location = DB::transaction(fn (): Location => Location::query()->create([
            'name' => $request->validated('name'),
            'short' => $request->validated('name'),
            'city' => $request->validated('city'),
            'type' => $request->validated('type'),
            'tier' => Location::TIER_COMMUNITY,
            'moderation_status' => Location::MODERATION_PENDING,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'geo_radius_meters' => (int) config('spoton.community_locations.default_radius_meters', 100),
            'icon' => LocationType::icon($request->validated('type')),
            'is_active' => true,
            'is_locked' => false,
            'access_password_hash' => null,
            'created_by_user_id' => $user->id,
        ]));

        return response()->json([
            'message' => 'Luogo aggiunto e inviato al controllo amministrativo.',
            'data' => $this->locationPayload($location) + [
                'moderation_status' => $location->moderation_status,
            ],
        ], 201);
    }

    public function mine(Request $request): JsonResponse
    {
        $locations = Location::query()
            ->where('created_by_user_id', $request->user()->id)
            ->latest()
            ->paginate(min(50, max(1, (int) $request->query('per_page', 20))));

        return response()->json([
            'message' => 'OK',
            'data' => collect($locations->items())
                ->map(fn (Location $location): array => $this->locationPayload($location) + [
                    'moderation_status' => $location->moderation_status,
                    'moderation_note' => $location->moderation_note,
                ])
                ->values(),
            'meta' => [
                'current_page' => $locations->currentPage(),
                'last_page' => $locations->lastPage(),
                'per_page' => $locations->perPage(),
                'total' => $locations->total(),
            ],
        ]);
    }

    public function duplicates(
        FindLocationDuplicatesRequest $request,
        LocationDuplicateFinder $duplicateFinder,
    ): JsonResponse {
        $duplicates = $duplicateFinder->find(
            (float) $request->validated('latitude'),
            (float) $request->validated('longitude'),
            $request->validated('name'),
            $request->validated('type'),
        );

        return response()->json([
            'message' => 'OK',
            'data' => [
                'candidates' => $this->duplicatePayload($duplicates),
            ],
        ]);
    }

    private function ensureRecentAccuratePosition(StoreCommunityLocationRequest $request): void
    {
        $user = $request->user();
        $maxAgeMinutes = (int) config('spoton.community_locations.position_max_age_minutes', 10);
        $maxAccuracyMeters = (int) config('spoton.community_locations.max_accuracy_meters', 100);
        $hasRecentPosition = $user->last_known_latitude !== null
            && $user->last_known_longitude !== null
            && $user->last_location_update?->isAfter(now()->subMinutes($maxAgeMinutes));

        if (! $hasRecentPosition) {
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
    }

    private function duplicatePayload(iterable $duplicates): array
    {
        return collect($duplicates)
            ->map(fn (array $candidate): array => $this->locationPayload($candidate['location']) + [
                'distance_meters' => $candidate['distance_meters'],
            ])
            ->values()
            ->all();
    }
}
