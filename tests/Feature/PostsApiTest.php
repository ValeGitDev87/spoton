<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PostsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_posts_are_protected(): void
    {
        $this->getJson('/api/posts')->assertUnauthorized();
        $this->postJson('/api/posts', [])->assertUnauthorized();
    }

    public function test_authenticated_user_can_create_post_with_musica(): void
    {
        Carbon::setTestNow('2026-07-09 12:00:00');

        $user = User::factory()->create();
        $location = $this->location();

        $this
            ->actingAs($user, 'sanctum')
            ->postJson('/api/posts', [
                'location_id' => $location->id,
                'text' => 'Ti ho vista alla metro con un libro blu.',
                'musica' => 'Quel ritornello che faceva la la la',
                'sighting_date' => '2026-07-09',
            ])
            ->assertCreated()
            ->assertJsonPath('data.text', 'Ti ho vista alla metro con un libro blu.')
            ->assertJsonPath('data.musica', 'Quel ritornello che faceva la la la')
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.is_owner', true);

        $this->assertDatabaseHas('posts', [
            'author_id' => $user->id,
            'location_id' => $location->id,
            'musica' => 'Quel ritornello che faceva la la la',
            'status' => 'active',
        ]);

        Carbon::setTestNow();
    }

    public function test_future_sighting_date_is_rejected(): void
    {
        Carbon::setTestNow('2026-07-09 12:00:00');

        $user = User::factory()->create();
        $location = $this->location();

        $this
            ->actingAs($user, 'sanctum')
            ->postJson('/api/posts', [
                'location_id' => $location->id,
                'text' => 'Messaggio valido.',
                'sighting_date' => '2026-07-10',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['sighting_date']);

        Carbon::setTestNow();
    }

    public function test_owner_can_update_and_remove_post(): void
    {
        $user = User::factory()->create();
        $post = Post::query()->create([
            'author_id' => $user->id,
            'location_id' => $this->location()->id,
            'text' => 'Vecchio testo',
            'musica' => null,
            'sighting_date' => '2026-07-09',
            'expires_at' => now()->addDay(),
            'status' => 'active',
        ]);

        $this
            ->actingAs($user, 'sanctum')
            ->patchJson("/api/posts/{$post->id}", [
                'text' => 'Nuovo testo',
                'musica' => 'Nuova musica',
                'sighting_date' => '2026-07-09',
            ])
            ->assertOk()
            ->assertJsonPath('data.text', 'Nuovo testo')
            ->assertJsonPath('data.musica', 'Nuova musica');

        $this
            ->actingAs($user, 'sanctum')
            ->deleteJson("/api/posts/{$post->id}")
            ->assertOk()
            ->assertJsonPath('data.removed', true);

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'status' => 'removed',
        ]);
    }

    public function test_non_owner_cannot_update_post(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $post = Post::query()->create([
            'author_id' => $owner->id,
            'location_id' => $this->location()->id,
            'text' => 'Testo owner',
            'sighting_date' => '2026-07-09',
            'expires_at' => now()->addDay(),
            'status' => 'active',
        ]);

        $this
            ->actingAs($other, 'sanctum')
            ->patchJson("/api/posts/{$post->id}", ['text' => 'Modifica non autorizzata'])
            ->assertForbidden();
    }

    private function location(): Location
    {
        return Location::query()->create([
            'name' => 'Metro Mergellina',
            'short' => 'Metro Mergellina',
            'city' => 'Napoli',
            'type' => 'metro',
            'latitude' => 40.8319000,
            'longitude' => 14.2193000,
            'geo_radius_meters' => 100,
            'is_active' => true,
        ]);
    }
}
