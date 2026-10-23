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

    public function test_database_locations_are_paginated_by_distance_and_mine_is_scoped_to_owner(): void
    {
        $viewer = User::factory()->create();
        $other = User::factory()->create();
        $ids = [];
        for ($i = 0; $i < 9; $i++) {
            $ids[] = Location::query()->create([
                'name' => 'Luogo '.(9 - $i), 'short' => 'Luogo', 'city' => 'Napoli', 'type' => 'bar',
                'latitude' => 40.85 + $i * 0.005, 'longitude' => 14.25,
                'geo_radius_meters' => 100, 'is_active' => true,
                'tier' => $i % 2 ? Location::TIER_COMMUNITY : Location::TIER_PARTNER,
                'moderation_status' => Location::MODERATION_APPROVED,
                'created_by_user_id' => $i < 2 ? $viewer->id : $other->id,
            ])->id;
        }
        $this->actingAs($viewer, 'sanctum')
            ->getJson('/api/locations?lat=40.85&lng=14.25&per_page=6')
            ->assertOk()->assertJsonCount(6, 'data')->assertJsonPath('meta.total', 9)
            ->assertJsonPath('meta.last_page', 2)->assertJsonPath('data.0.id', $ids[0])
            ->assertJsonPath('data.5.id', $ids[5])->assertJsonPath('data.0.distance_km', 0);
        $this->getJson('/api/locations?lat=40.85&lng=14.25&per_page=6&page=2')
            ->assertOk()->assertJsonCount(3, 'data')->assertJsonPath('data.0.id', $ids[6]);
        $this->getJson('/api/locations/mine?per_page=1')->assertOk()
            ->assertJsonCount(1, 'data')->assertJsonPath('meta.total', 2)->assertJsonPath('meta.last_page', 2);
        $this->getJson('/api/locations?lat=40.85&per_page=6')->assertUnprocessable();
        $this->getJson('/api/locations?per_page=500')->assertUnprocessable();
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
