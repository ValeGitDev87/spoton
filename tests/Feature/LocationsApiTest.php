<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LocationsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_locations_are_protected(): void
    {
        $this->getJson('/api/locations')->assertUnauthorized();
        $this->getJson('/api/locations/nearby?lat=40.8518&lng=14.2681')->assertUnauthorized();
    }

    public function test_authenticated_user_can_list_locations(): void
    {
        $user = User::factory()->create();

        Location::query()->create([
            'name' => 'Metro Mergellina',
            'short' => 'Metro Mergellina',
            'city' => 'Napoli',
            'type' => 'metro',
            'latitude' => 40.8319000,
            'longitude' => 14.2193000,
            'geo_radius_meters' => 100,
            'is_active' => true,
        ]);

        $this
            ->actingAs($user, 'sanctum')
            ->getJson('/api/locations')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Metro Mergellina')
            ->assertJsonPath('data.0.city', 'Napoli')
            ->assertJsonPath('data.0.icon', 'location-outline')
            ->assertJsonPath('data.0.icon_library', 'ionicons')
            ->assertJsonPath('data.0.tier', Location::TIER_PARTNER)
            ->assertJsonPath('data.0.is_partner', true)
            ->assertJsonPath('data.0.is_locked', false);
    }

    public function test_restricted_location_can_be_unlocked_with_its_password(): void
    {
        $user = User::factory()->create();
        $location = Location::query()->create([
            'name' => 'Club Riservato',
            'short' => 'Club',
            'city' => 'Napoli',
            'type' => 'altro',
            'latitude' => 40.8518000,
            'longitude' => 14.2681000,
            'geo_radius_meters' => 100,
            'is_active' => true,
            'is_locked' => true,
            'access_password_hash' => Hash::make('club1234'),
        ]);

        $this
            ->actingAs($user, 'sanctum')
            ->getJson('/api/locations')
            ->assertOk()
            ->assertJsonPath('data.0.is_locked', true)
            ->assertJsonMissingPath('data.0.access_password_hash');

        $this
            ->actingAs($user, 'sanctum')
            ->postJson("/api/locations/{$location->id}/verify-access", [
                'password' => 'errata',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('password');

        $this
            ->actingAs($user, 'sanctum')
            ->postJson("/api/locations/{$location->id}/verify-access", [
                'password' => 'club1234',
            ])
            ->assertOk()
            ->assertJsonPath('data.unlocked', true);
    }

    public function test_nearby_locations_are_filtered_and_sorted_by_distance(): void
    {
        $user = User::factory()->create();

        Location::query()->create([
            'name' => 'Bar Nilo',
            'short' => 'Bar Nilo',
            'city' => 'Napoli',
            'type' => 'bar',
            'latitude' => 40.8495000,
            'longitude' => 14.2569000,
            'geo_radius_meters' => 100,
            'is_active' => true,
        ]);

        Location::query()->create([
            'name' => 'Milano Centrale',
            'short' => 'Milano Centrale',
            'city' => 'Milano',
            'type' => 'metro',
            'latitude' => 45.4863000,
            'longitude' => 9.2025000,
            'geo_radius_meters' => 100,
            'is_active' => true,
        ]);

        $this
            ->actingAs($user, 'sanctum')
            ->getJson('/api/locations/nearby?lat=40.8518&lng=14.2681&radius_km=200')
            ->assertOk()
            ->assertJsonPath('data.radius_km', 200)
            ->assertJsonCount(1, 'data.locations')
            ->assertJsonPath('data.locations.0.name', 'Bar Nilo');
    }
}
