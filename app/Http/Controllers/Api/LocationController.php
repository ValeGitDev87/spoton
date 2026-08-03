<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\SerializesLocations;
use App\Http\Controllers\Controller;
use App\Http\Requests\Location\NearbyLocationsRequest;
use App\Http\Requests\Location\StoryFeedLocationsRequest;
use App\Http\Requests\Location\VerifyLocationAccessRequest;
use App\Models\Location;
use App\Services\GeoDistance;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class LocationController extends Controller
{
    use SerializesLocations;

    private const NEAREST_PRIORITY_COUNT = 10;

    public function index(Request $request): JsonResponse
    {
        $locations = Location::query()
            ->withCount(['posts as active_stories_count' => fn (Builder $query) => $query
                ->where('status', 'active')
                ->where('created_at', '>', now()->subDay())
                ->where('expires_at', '>', now())])
            ->where('is_active', true)
            ->when($request->query('search'), function ($query, string $search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('city', 'like', "%{$search}%");
                });
            })
            ->when($request->query('city'), fn ($query, string $city) => $query->where('city', $city))
            ->when($request->query('type'), fn ($query, string $type) => $query->where('type', $type))
            ->orderBy('city')
            ->orderBy('name')
            ->get()
            ->map(fn (Location $location) => $this->locationPayload($location));

        return response()->json([
            'message' => 'OK',
            'data' => $locations,
        ]);
    }

    public function verifyAccess(
        VerifyLocationAccessRequest $request,
        Location $location,
    ): JsonResponse {
        abort_unless($location->is_active, 404);

        if (! $location->accessPasswordMatches($request->validated('password'))) {
            throw ValidationException::withMessages([
                'password' => ['Password del luogo non corretta.'],
            ]);
        }

        return response()->json([
            'message' => 'Luogo sbloccato.',
            'data' => [
                'unlocked' => true,
            ],
        ]);
    }

    public function nearby(NearbyLocationsRequest $request): JsonResponse
    {
        $lat = (float) $request->validated('lat');
        $lng = (float) $request->validated('lng');
        $radiusKm = (float) ($request->validated('radius_km') ?? 200);

        $locations = Location::query()
            ->withCount(['posts as active_stories_count' => fn (Builder $query) => $query
                ->where('status', 'active')
                ->where('created_at', '>', now()->subDay())
                ->where('expires_at', '>', now())])
            ->where('is_active', true)
            ->get()
            ->map(function (Location $location) use ($lat, $lng): array {
                return $this->locationPayload($location) + [
                    'distance_km' => round(GeoDistance::kilometers(
                        $lat,
                        $lng,
                        (float) $location->latitude,
                        (float) $location->longitude,
                    ), 2),
                ];
            })
            ->filter(fn (array $location) => $location['distance_km'] <= $radiusKm)
            ->sortBy('distance_km')
            ->values();

        return response()->json([
            'message' => 'OK',
            'data' => [
                'origin' => [
                    'lat' => $lat,
                    'lng' => $lng,
                ],
                'radius_km' => $radiusKm,
                'locations' => $locations,
            ],
        ]);
    }

    public function storyFeed(StoryFeedLocationsRequest $request): JsonResponse
    {
        $hasCoordinates = $request->filled('lat') && $request->filled('lng');
        $lat = $hasCoordinates ? (float) $request->validated('lat') : null;
        $lng = $hasCoordinates ? (float) $request->validated('lng') : null;
        $page = (int) ($request->validated('page') ?? 1);
        $perPage = (int) ($request->validated('per_page') ?? 20);
        $activeStories = fn (Builder $query) => $query
            ->where('status', 'active')
            ->where('created_at', '>', now()->subDay())
            ->where('expires_at', '>', now());

        $locations = Location::query()
            ->withCount(['posts as active_stories_count' => $activeStories])
            ->withMax(['posts as latest_story_at' => $activeStories], 'created_at')
            ->where('is_active', true)
            ->whereHas('posts', $activeStories)
            ->get()
            ->map(function (Location $location) use ($lat, $lng): array {
                $distanceKm = $lat !== null && $lng !== null
                    ? GeoDistance::kilometers(
                        $lat,
                        $lng,
                        (float) $location->latitude,
                        (float) $location->longitude,
                    )
                    : null;

                return [
                    'location' => $location,
                    'distance_km' => $distanceKm,
                    'latest_story_at' => $location->latest_story_at,
                ];
            });

        if ($hasCoordinates) {
            $nearest = $locations
                ->sort(fn (array $left, array $right) => $left['distance_km'] <=> $right['distance_km']
                    ?: $right['latest_story_at'] <=> $left['latest_story_at'])
                ->take(self::NEAREST_PRIORITY_COUNT)
                ->values();
            $nearestIds = $nearest
                ->map(fn (array $item) => $item['location']->id)
                ->all();
            $remaining = $locations
                ->reject(fn (array $item) => in_array($item['location']->id, $nearestIds, true))
                ->sortByDesc('latest_story_at')
                ->values();
            $locations = $nearest->concat($remaining)->values();
        } else {
            $locations = $locations->sortByDesc('latest_story_at')->values();
        }

        $pageLocations = $locations
            ->forPage($page, $perPage)
            ->map(fn (array $item) => $this->locationPayload(
                $item['location'],
                $item['distance_km'],
            ))
            ->values();
        $lastPage = max(1, (int) ceil($locations->count() / $perPage));

        return response()->json([
            'message' => 'OK',
            'data' => [
                'origin' => $hasCoordinates ? [
                    'lat' => $lat,
                    'lng' => $lng,
                ] : null,
                'nearest_priority_count' => $hasCoordinates
                    ? min(self::NEAREST_PRIORITY_COUNT, $locations->count())
                    : 0,
                'locations' => $pageLocations,
            ],
            'meta' => [
                'current_page' => $page,
                'last_page' => $lastPage,
                'per_page' => $perPage,
                'total' => $locations->count(),
            ],
        ]);
    }
}
