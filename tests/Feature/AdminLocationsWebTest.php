<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLocationsWebTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this
            ->get('/admin/locations')
            ->assertRedirect('/login');
    }

    public function test_non_admin_cannot_access_locations_crud(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this
            ->actingAs($user)
            ->get('/admin/locations')
            ->assertForbidden();
    }

    public function test_admin_can_view_locations_index(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

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
            ->actingAs($admin)
            ->get('/admin/locations')
            ->assertOk()
            ->assertSee('Metro Mergellina')
            ->assertSee('40.8319000')
            ->assertSee('14.2193000');
    }

    public function test_admin_can_create_location_from_web_form(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this
            ->actingAs($admin)
            ->post('/admin/locations', [
                'name' => 'Villa Comunale',
                'short' => 'Villa',
                'city' => 'Napoli',
                'type' => 'parco',
                'latitude' => 40.8331000,
                'longitude' => 14.2294000,
                'geo_radius_meters' => 250,
                'icon' => 'leaf-outline',
                'is_active' => '1',
            ])
            ->assertRedirect('/admin/locations');

        $this->assertDatabaseHas('locations', [
            'name' => 'Villa Comunale',
            'city' => 'Napoli',
            'type' => 'parco',
            'latitude' => 40.8331000,
            'longitude' => 14.2294000,
            'geo_radius_meters' => 250,
            'is_active' => true,
        ]);
    }

    public function test_admin_can_update_location_coordinates_from_web_form(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $location = Location::query()->create([
            'name' => 'Luogo Vecchio',
            'short' => 'Vecchio',
            'city' => 'Napoli',
            'type' => 'altro',
            'latitude' => 40.0000000,
            'longitude' => 14.0000000,
            'geo_radius_meters' => 100,
            'is_active' => true,
        ]);

        $this
            ->actingAs($admin)
            ->patch("/admin/locations/{$location->id}", [
                'name' => 'Luogo Nuovo',
                'short' => 'Nuovo',
                'city' => 'Salerno',
                'type' => 'piazza',
                'latitude' => 40.6824000,
                'longitude' => 14.7681000,
                'geo_radius_meters' => 500,
                'icon' => 'business-outline',
            ])
            ->assertRedirect('/admin/locations');

        $this->assertDatabaseHas('locations', [
            'id' => $location->id,
            'name' => 'Luogo Nuovo',
            'city' => 'Salerno',
            'latitude' => 40.6824000,
            'longitude' => 14.7681000,
            'geo_radius_meters' => 500,
            'is_active' => false,
        ]);
    }
}
