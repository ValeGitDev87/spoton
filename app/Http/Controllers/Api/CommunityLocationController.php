<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\SerializesLocations;
use App\Http\Controllers\Controller;
use App\Http\Requests\Location\FindLocationDuplicatesRequest;
use App\Http\Requests\Location\NearbyLocationCandidatesRequest;
use App\Http\Requests\Location\StoreCommunityLocationRequest;
use App\Models\Location;
use App\Services\GeoDistance;
use App\Services\LocationDuplicateFinder;
use App\Services\LocationNameNormalizer;
use App\Services\Places\PlaceCandidateToken;
use App\Services\SmartLocationLookupService;
use App\Support\LocationType;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CommunityLocationController extends Controller
{
    use SerializesLocations;

    public function store(
        StoreCommunityLocationRequest $request,
        LocationDuplicateFinder $duplicateFinder,
        LocationNameNormalizer $normalizer,
        PlaceCandidateToken $tokens,
        SmartLocationLookupService $lookup,
    ): JsonResponse {
        $user = $request->user();

        abort_unless($user->hasVerifiedEmail(), 403, 'Verifica la tua email prima di aggiungere un luogo.');

        $lookup->verifiedPosition($user);

        if ($request->filled('candidate_token')) {
            $locationData = $tokens->decode($request->validated('candidate_token'), $user->id);
            $locationData = Validator::make($locationData, [
                'provider' => ['required', 'string', 'max:32'],
                'provider_place_id' => ['required', 'string', 'max:255'],
                'name' => ['required', 'string', 'min:2', 'max:100'],
                'city' => ['required', 'string', 'min:2', 'max:120'],
                'type' => ['required', 'string', Rule::in(LocationType::codes())],
                'location_kind' => ['required', Rule::in(['poi', 'area'])],
                'latitude' => ['required', 'numeric', 'between:-90,90'],
                'longitude' => ['required', 'numeric', 'between:-180,180'],
            ])->validate();
        } else {
            $locationData = [
                'provider' => null,
                'provider_place_id' => null,
                'name' => $request->validated('name'),
                'city' => $request->validated('city'),
                'type' => $request->validated('type'),
                'location_kind' => $request->validated('location_kind', 'poi'),
                'latitude' => (float) $request->validated('latitude'),
                'longitude' => (float) $request->validated('longitude'),
            ];
        }

        $locationData['name'] = $normalizer->displayName(
            $locationData['name'],
            $locationData['location_kind'],
        );
        $locationData['normalized_name'] = $normalizer->normalize(
            $locationData['name'],
            $locationData['location_kind'],
        );
        $latitude = (float) $locationData['latitude'];
        $longitude = (float) $locationData['longitude'];
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

        $duplicates = $duplicateFinder->find(
            $latitude,
            $longitude,
            $locationData['name'],
            $locationData['type'],
            $locationData['provider'],
            $locationData['provider_place_id'],
            $locationData['location_kind'],
        );

        if ($duplicates->isNotEmpty()) {
            if ($locationData['provider_place_id']
                && $duplicates->first()['location']->provider_place_id === $locationData['provider_place_id']) {
                return $this->resolvedExistingResponse($duplicates->first()['location']);
            }

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

        try {
            $location = DB::transaction(fn (): Location => Location::query()->create([
            'name' => $locationData['name'],
            'normalized_name' => $locationData['normalized_name'],
            'short' => $locationData['name'],
            'city' => $locationData['city'],
            'type' => $locationData['type'],
            'provider' => $locationData['provider'],
            'provider_place_id' => $locationData['provider_place_id'],
            'location_kind' => $locationData['location_kind'],
            'tier' => Location::TIER_COMMUNITY,
            'moderation_status' => Location::MODERATION_PENDING,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'geo_radius_meters' => (int) config('spoton.community_locations.default_radius_meters', 100),
            'icon' => LocationType::icon($locationData['type']),
            'is_active' => true,
            'is_locked' => false,
            'access_password_hash' => null,
            'created_by_user_id' => $user->id,
            ]));
        } catch (QueryException $exception) {
            $existing = $locationData['provider'] && $locationData['provider_place_id']
                ? $duplicateFinder->findAnyByProvider($locationData['provider'], $locationData['provider_place_id'])
                : null;

            if (! $existing) {
                throw $exception;
            }

            if (! $existing->isPubliclyVisible()) {
                throw ValidationException::withMessages([
                    'location' => ['Questo luogo e gia registrato ma non e al momento disponibile.'],
                ]);
            }

            Log::info('smart_location.duplicate_resolved', [
                'location_id' => $existing->id,
                'provider' => $locationData['provider'],
                'provider_place_id' => $locationData['provider_place_id'],
                'user_id' => $user->id,
            ]);

            return $this->resolvedExistingResponse($existing);
        }

        Log::info('smart_location.created', [
            'location_id' => $location->id,
            'provider' => $location->provider,
            'provider_place_id' => $location->provider_place_id,
            'user_id' => $user->id,
        ]);

        return response()->json([
            'message' => 'Luogo aggiunto e inviato al controllo amministrativo.',
            'data' => $this->locationPayload($location) + [
                'moderation_status' => $location->moderation_status,
            ],
        ], 201);
    }

    public function nearbyCandidates(
        NearbyLocationCandidatesRequest $request,
        SmartLocationLookupService $lookup,
    ): JsonResponse {
        abort_unless(
            $request->user()->hasVerifiedEmail(),
            403,
            'Verifica la tua email prima di aggiungere un luogo.',
        );

        $result = $lookup->lookup(
            $request->user(),
            (bool) $request->validated('include_provider', false),
        );

        return response()->json([
            'message' => 'OK',
            'data' => [
                'existing' => collect($result['existing'])
                    ->map(fn (array $candidate): array => $this->locationPayload($candidate['location']) + [
                        'distance_meters' => $candidate['distance_meters'],
                    ])
                    ->values(),
                'provider' => $result['provider'],
                'provider_available' => $result['provider_available'],
                'provider_failed' => $result['provider_failed'],
            ],
        ]);
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

    private function resolvedExistingResponse(Location $location): JsonResponse
    {
        return response()->json([
            'message' => 'Luogo gia presente: e stato selezionato quello esistente.',
            'data' => $this->locationPayload($location) + [
                'resolved_existing' => true,
            ],
        ]);
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
