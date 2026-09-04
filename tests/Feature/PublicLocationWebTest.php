<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PublicLocationWebTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_location_page_shows_active_posts_without_coordinates_or_ghost_identity(): void
    {
        $location = $this->createLocation();
        $author = User::factory()->create(['display_name' => 'Identita Segreta']);
        Post::query()->create([
            'author_id' => $author->id,
            'location_id' => $location->id,
            'text' => 'Messaggio pubblico del luogo.',
            'category' => 'spotted_love',
            'sighting_date' => now()->toDateString(),
            'is_anonymous' => true,
            'expires_at' => now()->addDay(),
            'status' => 'active',
        ]);

        $this->get("/l/{$location->id}")
            ->assertOk()
            ->assertSee('Bacheca del luogo')
            ->assertSee('Messaggio pubblico del luogo.')
            ->assertSee('Ghost')
            ->assertSee("spoton://l/{$location->id}", false)
            ->assertDontSee('Identita Segreta')
            ->assertDontSee('40.8319')
            ->assertDontSee('14.2193');
    }

    public function test_inactive_location_is_not_public(): void
    {
        $location = $this->createLocation(['is_active' => false]);

        $this->get("/l/{$location->id}")->assertNotFound();
    }

    public function test_authenticated_api_returns_location_for_deep_link_resolution(): void
    {
        $location = $this->createLocation();
        Sanctum::actingAs(User::factory()->create());

        $this->getJson("/api/locations/{$location->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $location->id)
            ->assertJsonPath('data.name', 'Piazza Link');
    }

    private function createLocation(array $overrides = []): Location
    {
        return Location::query()->create([
            'name' => 'Piazza Link',
            'short' => 'Piazza Link',
            'city' => 'Napoli',
            'type' => 'piazza',
            'latitude' => 40.8319,
            'longitude' => 14.2193,
            'geo_radius_meters' => 100,
            'is_active' => true,
            ...$overrides,
        ]);
    }
}
