<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Location\StoreLocationRequest;
use App\Http\Requests\Location\UpdateLocationRequest;
use App\Models\Location;
use App\Support\LocationIcon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminLocationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $locations = Location::query()
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
        $location->update($request->validated());

        return response()->json([
            'message' => 'OK',
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
