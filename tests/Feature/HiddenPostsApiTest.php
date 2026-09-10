<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HiddenPostsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_hidden_post_disappears_from_feed_and_stories_and_persists(): void
    {
        $viewer = User::factory()->create();
        $post = $this->makePost(User::factory()->create(), true);

        $this->actingAs($viewer, 'sanctum')->getJson('/api/posts/feed')
            ->assertOk()
            ->assertJsonPath('data.posts.0.id', $post->id);

        $this->actingAs($viewer, 'sanctum')->postJson("/api/posts/{$post->id}/hide")
            ->assertOk()
            ->assertExactJson([
                'message' => 'Post nascosto dal tuo feed.',
                'data' => ['post_id' => $post->id, 'hidden' => true],
            ]);

        $this->assertDatabaseHas('hidden_posts', ['user_id' => $viewer->id, 'post_id' => $post->id]);
        $this->actingAs($viewer->fresh(), 'sanctum')->getJson('/api/posts/feed')
            ->assertOk()
            ->assertJsonMissing(['id' => $post->id]);
        $this->actingAs($viewer->fresh(), 'sanctum')->getJson("/api/locations/{$post->location_id}/stories")
            ->assertOk()
            ->assertJsonMissing(['id' => $post->id]);

        $this->actingAs($viewer, 'sanctum')->deleteJson("/api/posts/{$post->id}/hide")
            ->assertOk()
            ->assertJsonPath('data.hidden', false);
        $this->actingAs($viewer, 'sanctum')->getJson('/api/posts/feed')
            ->assertJsonPath('data.posts.0.id', $post->id);
    }

    public function test_user_cannot_hide_own_post(): void
    {
        $owner = User::factory()->create();
        $post = $this->makePost($owner);

        $this->actingAs($owner, 'sanctum')->postJson("/api/posts/{$post->id}/hide")
            ->assertUnprocessable();
    }

    private function makePost(User $author, bool $ghost = false): Post
    {
        $location = Location::query()->create([
            'name' => 'Luogo hide',
            'short' => 'Hide',
            'city' => 'Napoli',
            'type' => 'altro',
            'latitude' => 40.8518,
            'longitude' => 14.2681,
            'geo_radius_meters' => 100,
            'is_active' => true,
        ]);

        return Post::query()->create([
            'author_id' => $author->id,
            'location_id' => $location->id,
            'text' => 'Post da nascondere',
            'is_anonymous' => $ghost,
            'sighting_date' => now()->toDateString(),
            'expires_at' => now()->addHours(48),
            'status' => 'active',
        ]);
    }
}
