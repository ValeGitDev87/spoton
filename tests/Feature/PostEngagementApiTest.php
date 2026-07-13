<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostEngagementApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_like_toggle_updates_counter(): void
    {
        $user = User::factory()->create();
        $post = $this->makePost();

        $this
            ->actingAs($user, 'sanctum')
            ->postJson("/api/posts/{$post->id}/like")
            ->assertOk()
            ->assertJsonPath('data.liked', true)
            ->assertJsonPath('data.like_count', 1);

        $this
            ->actingAs($user, 'sanctum')
            ->postJson("/api/posts/{$post->id}/like")
            ->assertOk()
            ->assertJsonPath('data.liked', false)
            ->assertJsonPath('data.like_count', 0);
    }

    public function test_io_cero_owner_is_forbidden_and_other_user_can_toggle(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $post = $this->makePost($owner);

        $this
            ->actingAs($owner, 'sanctum')
            ->postJson("/api/posts/{$post->id}/io-cero")
            ->assertForbidden();

        $this
            ->actingAs($other, 'sanctum')
            ->postJson("/api/posts/{$post->id}/io-cero")
            ->assertOk()
            ->assertJsonPath('data.io_cero', true)
            ->assertJsonPath('data.io_cero_count', 1);

        $this
            ->actingAs($other, 'sanctum')
            ->postJson("/api/posts/{$post->id}/io-cero")
            ->assertOk()
            ->assertJsonPath('data.io_cero', false)
            ->assertJsonPath('data.io_cero_count', 0);
    }

    public function test_only_owner_can_view_io_cero_users_without_sensitive_data(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        $other = User::factory()->create();
        $post = $this->makePost($owner);

        $this->actingAs($viewer, 'sanctum')->postJson("/api/posts/{$post->id}/io-cero");

        $this
            ->actingAs($other, 'sanctum')
            ->getJson("/api/posts/{$post->id}/io-cero-users")
            ->assertForbidden();

        $response = $this
            ->actingAs($owner, 'sanctum')
            ->getJson("/api/posts/{$post->id}/io-cero-users")
            ->assertOk()
            ->assertJsonPath('data.0.id', $viewer->id)
            ->assertJsonMissingPath('data.0.email')
            ->assertJsonMissingPath('data.0.last_known_latitude');

        $this->assertSame($viewer->display_name, $response->json('data.0.display_name'));
    }

    private function makePost(?User $owner = null): Post
    {
        $owner ??= User::factory()->create();
        $location = Location::query()->create([
            'name' => 'Bar Nilo',
            'short' => 'Bar Nilo',
            'city' => 'Napoli',
            'type' => 'bar',
            'latitude' => 40.8495000,
            'longitude' => 14.2569000,
            'geo_radius_meters' => 100,
            'is_active' => true,
        ]);

        return Post::query()->create([
            'author_id' => $owner->id,
            'location_id' => $location->id,
            'text' => 'Post engagement',
            'musica' => 'Ritornello',
            'sighting_date' => '2026-07-13',
            'expires_at' => now()->addDay(),
            'status' => 'active',
        ]);
    }
}
