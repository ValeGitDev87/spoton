<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\SerializesLocations;
use App\Http\Controllers\Controller;
use App\Http\Requests\Location\NearbyLocationsRequest;
use App\Models\Location;
use App\Services\GeoDistance;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    use SerializesLocations;

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
}
