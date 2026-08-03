<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
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
                'icon' => 'leaf-outline',
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

    public function test_admin_cannot_save_an_unknown_location_icon(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this
            ->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/locations', [
                'name' => 'Luogo senza icona valida',
                'city' => 'Napoli',
                'type' => 'altro',
                'latitude' => 40.8331,
                'longitude' => 14.2294,
                'icon' => 'codice-inventato',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('icon');
    }

    public function test_admin_api_hashes_restricted_location_password(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this
            ->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/locations', [
                'name' => 'Area Staff',
                'city' => 'Napoli',
                'type' => 'altro',
                'latitude' => 40.8331,
                'longitude' => 14.2294,
                'icon' => 'location-outline',
                'is_locked' => true,
                'access_password' => 'staff123',
            ])
            ->assertCreated()
            ->assertJsonPath('data.is_locked', true)
            ->assertJsonMissingPath('data.access_password_hash');

        $location = Location::query()->findOrFail($response->json('data.id'));

        $this->assertTrue(Hash::check('staff123', $location->access_password_hash));
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
