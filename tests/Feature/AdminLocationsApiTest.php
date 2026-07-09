<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLocationsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_cannot_manage_locations(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this
            ->actingAs($user, 'sanctum')
            ->postJson('/api/admin/locations', [
                'name' => 'Nuovo luogo',
                'city' => 'Napoli',
                'type' => 'altro',
                'latitude' => 40.8518,
                'longitude' => 14.2681,
            ])
            ->assertForbidden();
    }

    public function test_admin_can_create_location(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this
            ->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/locations', [
                'name' => 'Villa Comunale',
                'city' => 'Napoli',
                'type' => 'parco',
                'latitude' => 40.8331,
                'longitude' => 14.2294,
                'geo_radius_meters' => 250,
                'icon' => 'trees',
            ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Villa Comunale')
            ->assertJsonPath('data.city', 'Napoli');

        $this->assertDatabaseHas('locations', [
            'name' => 'Villa Comunale',
            'city' => 'Napoli',
            'type' => 'parco',
            'geo_radius_meters' => 250,
        ]);
    }

    public function test_admin_can_update_and_delete_location(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $location = Location::query()->create([
            'name' => 'Vecchio Nome',
            'short' => 'Vecchio Nome',
            'city' => 'Napoli',
            'type' => 'altro',
            'latitude' => 40.8518,
            'longitude' => 14.2681,
            'geo_radius_meters' => 100,
            'is_active' => true,
        ]);

        $this
            ->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/locations/{$location->id}", [
                'name' => 'Nuovo Nome',
                'is_active' => false,
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Nuovo Nome')
            ->assertJsonPath('data.is_active', false);

        $this->assertDatabaseHas('locations', [
            'id' => $location->id,
            'name' => 'Nuovo Nome',
            'is_active' => false,
        ]);

        $this
            ->actingAs($admin, 'sanctum')
            ->deleteJson("/api/admin/locations/{$location->id}")
            ->assertOk()
            ->assertJsonPath('data.deleted', true);

        $this->assertDatabaseMissing('locations', [
            'id' => $location->id,
        ]);
    }
}
