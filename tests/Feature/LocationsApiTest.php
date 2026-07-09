<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            ->assertJsonPath('data.0.city', 'Napoli');
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
