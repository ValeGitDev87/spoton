<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\SerializesLocations;
use App\Http\Controllers\Controller;
use App\Http\Requests\Post\NearbyPostsRequest;
use App\Models\Location;
use App\Services\GeoDistance;
use Illuminate\Http\JsonResponse;

class MapController extends Controller
{
    use SerializesLocations;

    public function __invoke(NearbyPostsRequest $request, PostController $postController): JsonResponse
    {
        $lat = (float) $request->validated('lat');
        $lng = (float) $request->validated('lng');
        $radiusKm = (float) ($request->validated('radius_km') ?? 200);

        $locations = Location::query()
            ->where('is_active', true)
            ->get()
            ->map(function (Location $location) use ($lat, $lng): array {
                $distanceKm = GeoDistance::kilometers(
                    $lat,
                    $lng,
                    (float) $location->latitude,
                    (float) $location->longitude,
                );

                return $this->locationPayload($location, $distanceKm);
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
                'posts' => $postController->nearbyPosts($request, $lat, $lng, $radiusKm),
            ],
        ]);
    }
}
