<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Location\StoreLocationRequest;
use App\Http\Requests\Location\UpdateLocationRequest;
use App\Models\Location;
use App\Support\LocationIcon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class LocationController extends Controller
{
    public function index(Request $request): View
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
                'is_locked' => false,
                'tier' => Location::TIER_PARTNER,
                'moderation_status' => Location::MODERATION_APPROVED,
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
        $data['is_locked'] = $request->boolean('is_locked');
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
        $originalModerationStatus = $location->moderation_status;
        $data['is_active'] = $request->boolean('is_active');
        $data['is_locked'] = $request->boolean('is_locked');
        if (($data['tier'] ?? $location->tier) === Location::TIER_COMMUNITY) {
            $data['is_locked'] = false;
        }
        $password = trim((string) ($data['access_password'] ?? ''));
        unset($data['access_password']);

        if (! $data['is_locked']) {
            $data['access_password_hash'] = null;
        } elseif ($password !== '') {
            $data['access_password_hash'] = Hash::make($password);
        }

        if (isset($data['moderation_status'])
            && $data['moderation_status'] !== $originalModerationStatus) {
            $data['moderated_by_user_id'] = $request->user()->id;
            $data['moderated_at'] = now();

            if (in_array($data['moderation_status'], [
                Location::MODERATION_REJECTED,
                Location::MODERATION_SUSPENDED,
            ], true)) {
                $data['is_active'] = false;
            }
        }

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
