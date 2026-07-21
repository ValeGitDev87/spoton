<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Location\StoreLocationRequest;
use App\Http\Requests\Location\UpdateLocationRequest;
use App\Models\Location;
use App\Support\LocationIcon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LocationController extends Controller
{
    public function index(Request $request): View
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
            ->paginate(20)
            ->withQueryString();

        return view('admin.locations.index', [
            'locations' => $locations,
            'search' => $request->query('search', ''),
        ]);
    }

    public function create(): View
    {
        return view('admin.locations.create', [
            'location' => new Location([
                'type' => 'altro',
                'icon' => LocationIcon::DEFAULT,
                'geo_radius_meters' => 100,
                'is_active' => true,
            ]),
            'iconOptions' => LocationIcon::options(),
        ]);
    }

    public function store(StoreLocationRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['short'] = $data['short'] ?? $data['name'];
        $data['geo_radius_meters'] = $data['geo_radius_meters'] ?? 100;
        $data['is_active'] = $request->boolean('is_active');

        Location::query()->create($data);

        return redirect()
            ->route('admin.locations.index')
            ->with('status', 'Luogo creato.');
    }

    public function edit(Location $location): View
    {
        return view('admin.locations.edit', [
            'location' => $location,
            'iconOptions' => LocationIcon::options(),
        ]);
    }

    public function update(UpdateLocationRequest $request, Location $location): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');

        $location->update($data);

        return redirect()
            ->route('admin.locations.index')
            ->with('status', 'Luogo aggiornato.');
    }

    public function destroy(Location $location): RedirectResponse
    {
        $location->delete();

        return redirect()
            ->route('admin.locations.index')
            ->with('status', 'Luogo eliminato.');
    }
}
