<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Location\ModerateLocationRequest;
use App\Http\Requests\Location\StoreLocationRequest;
use App\Http\Requests\Location\UpdateLocationRequest;
use App\Models\Location;
use App\Support\LocationIcon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminLocationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $locations = Location::query()
            ->with('creator:id,display_name,email')
            ->when($request->query('search'), function ($query, string $search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('city', 'like', "%{$search}%");
                });
            })
            ->when($request->query('city'), fn ($query, string $city) => $query->where('city', $city))
            ->when($request->query('type'), fn ($query, string $type) => $query->where('type', $type))
            ->when($request->query('tier'), fn ($query, string $tier) => $query->where('tier', $tier))
            ->when($request->query('moderation_status'), fn ($query, string $status) => $query->where('moderation_status', $status))
            ->orderBy('city')
            ->orderBy('name')
            ->paginate((int) $request->query('per_page', 25));

        return response()->json([
            'message' => 'OK',
            'data' => $locations->items(),
            'meta' => [
                'current_page' => $locations->currentPage(),
                'last_page' => $locations->lastPage(),
                'per_page' => $locations->perPage(),
                'total' => $locations->total(),
            ],
        ]);
    }

    public function store(StoreLocationRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['short'] = $data['short'] ?? $data['name'];
        $data['geo_radius_meters'] = $data['geo_radius_meters'] ?? 100;
        $data['icon'] = $data['icon'] ?? LocationIcon::DEFAULT;
        $data['is_active'] = $data['is_active'] ?? true;
        $data['is_locked'] = $data['is_locked'] ?? false;
        $data['tier'] = $data['tier'] ?? Location::TIER_PARTNER;
        $data['moderation_status'] = $data['moderation_status'] ?? Location::MODERATION_APPROVED;
        if ($data['tier'] === Location::TIER_COMMUNITY) {
            $data['is_locked'] = false;
        }
        $password = trim((string) ($data['access_password'] ?? ''));
        unset($data['access_password']);
        $data['access_password_hash'] = $data['is_locked']
            ? Hash::make($password)
            : null;

        $location = Location::query()->create($data);

        return response()->json([
            'message' => 'OK',
            'data' => $location,
        ], 201);
    }

    public function show(Location $location): JsonResponse
    {
        return response()->json([
            'message' => 'OK',
            'data' => $location,
        ]);
    }

    public function update(UpdateLocationRequest $request, Location $location): JsonResponse
    {
        $data = $request->validated();
        $tier = $data['tier'] ?? $location->tier;
        $isLocked = array_key_exists('is_locked', $data)
            ? (bool) $data['is_locked']
            : $location->is_locked;
        if ($tier === Location::TIER_COMMUNITY) {
            $isLocked = false;
            $data['is_locked'] = false;
        }
        $password = trim((string) ($data['access_password'] ?? ''));
        unset($data['access_password']);

        if (! $isLocked) {
            $data['access_password_hash'] = null;
        } elseif ($password !== '') {
            $data['access_password_hash'] = Hash::make($password);
        }

        $location->update($data);

        return response()->json([
            'message' => 'OK',
            'data' => $location->refresh(),
        ]);
    }

    public function moderate(
        ModerateLocationRequest $request,
        Location $location,
    ): JsonResponse {
        $status = $request->validated('status');

        $location->update([
            'moderation_status' => $status,
            'moderation_note' => $request->validated('note'),
            'moderated_by_user_id' => $request->user()->id,
            'moderated_at' => now(),
            'is_active' => in_array($status, [
                Location::MODERATION_PENDING,
                Location::MODERATION_APPROVED,
            ], true),
        ]);

        return response()->json([
            'message' => 'Moderazione del luogo aggiornata.',
            'data' => $location->refresh(),
        ]);
    }

    public function destroy(Location $location): JsonResponse
    {
        $location->delete();

        return response()->json([
            'message' => 'OK',
            'data' => [
                'deleted' => true,
            ],
        ]);
    }
}
