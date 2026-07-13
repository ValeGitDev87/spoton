<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\SerializesLocations;
use App\Http\Controllers\Controller;
use App\Http\Requests\Presence\PingPresenceRequest;
use App\Models\Location;
use App\Models\PresenceSession;
use App\Services\GeoDistance;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class PresenceController extends Controller
{
    use SerializesLocations;

    public function ping(PingPresenceRequest $request): JsonResponse
    {
        $lat = (float) $request->validated('lat');
        $lng = (float) $request->validated('lng');
        $user = $request->user();
        $now = now();

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

                return [
                    'model' => $location,
                    'distance_km' => $distanceKm,
                    'inside' => ($distanceKm * 1000) <= $location->geo_radius_meters,
                ];
            });

        $insideLocationIds = $locations
            ->filter(fn (array $location) => $location['inside'])
            ->pluck('model.id')
            ->values();

        DB::transaction(function () use ($user, $lat, $lng, $now, $insideLocationIds): void {
            $user->update([
                'last_known_latitude' => $lat,
                'last_known_longitude' => $lng,
                'last_location_update' => $now,
            ]);

            PresenceSession::query()
                ->where('user_id', $user->id)
                ->whereNull('ended_at')
                ->whereNotIn('location_id', $insideLocationIds)
                ->update(['ended_at' => $now]);

            foreach ($insideLocationIds as $locationId) {
                $session = PresenceSession::query()
                    ->where('user_id', $user->id)
                    ->where('location_id', $locationId)
                    ->whereNull('ended_at')
                    ->first();

                if ($session) {
                    $session->update(['last_ping_at' => $now]);

                    continue;
                }

                PresenceSession::query()->create([
                    'user_id' => $user->id,
                    'location_id' => $locationId,
                    'started_at' => $now,
                    'last_ping_at' => $now,
                ]);
            }

            PresenceSession::query()
                ->whereNull('ended_at')
                ->where('last_ping_at', '<', $now->copy()->subMinutes(5))
                ->update(['ended_at' => $now]);
        });

        $payloadLocations = $locations
            ->filter(fn (array $location) => $location['inside'])
            ->map(fn (array $location) => $this->locationPayload($location['model'], $location['distance_km']))
            ->values();

        return response()->json([
            'message' => 'OK',
            'data' => [
                'origin' => [
                    'lat' => $lat,
                    'lng' => $lng,
                ],
                'locations' => $payloadLocations,
            ],
        ]);
    }
}
