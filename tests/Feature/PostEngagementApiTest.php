<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\Post;
use App\Models\PresenceSession;
use App\Models\User;
use App\Support\PostCategory;
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

    public function test_io_cero_is_not_available_for_weather_or_gossip_posts(): void
    {
        $viewer = User::factory()->create();

        foreach ([PostCategory::WEATHER_TRANSPORT, PostCategory::GOSSIP_EVENTS] as $category) {
            $post = $this->makePost(category: $category);

            $this
                ->actingAs($viewer, 'sanctum')
                ->postJson("/api/posts/{$post->id}/io-cero")
                ->assertUnprocessable();
        }
    }

    public function test_weather_vote_requires_a_recent_presence_at_the_post_location(): void
    {
        $viewer = User::factory()->create();
        $post = $this->makePost(category: PostCategory::WEATHER_TRANSPORT);

        $this
            ->actingAs($viewer, 'sanctum')
            ->postJson("/api/posts/{$post->id}/community-vote", ['vote' => 'confirm'])
            ->assertForbidden();

        $this->markPresent($viewer, $post->location);

        $this
            ->actingAs($viewer, 'sanctum')
            ->postJson("/api/posts/{$post->id}/community-vote", ['vote' => 'confirm'])
            ->assertOk()
            ->assertJsonPath('data.community_confirm_count', 1)
            ->assertJsonPath('data.community_vote_by_me', 'confirm');
    }

    public function test_weather_votes_verify_then_hide_the_post_using_community_thresholds(): void
    {
        $post = $this->makePost(category: PostCategory::WEATHER_TRANSPORT);
        $confirmers = User::factory()->count(4)->create();

        foreach ($confirmers as $index => $user) {
            $this->markPresent($user, $post->location);
            $response = $this
                ->actingAs($user, 'sanctum')
                ->postJson("/api/posts/{$post->id}/community-vote", ['vote' => 'confirm'])
                ->assertOk()
                ->assertJsonPath('data.community_confirm_count', $index + 1);
        }

        $response
            ->assertJsonPath('data.community_status', 'verified')
            ->assertJsonPath('data.status', 'active');

        $falseVoters = User::factory()->count(2)->create();

        foreach ($falseVoters as $index => $user) {
            $this->markPresent($user, $post->location);
            $response = $this
                ->actingAs($user, 'sanctum')
                ->postJson("/api/posts/{$post->id}/community-vote", ['vote' => 'false'])
                ->assertOk()
                ->assertJsonPath('data.community_false_count', $index + 1);
        }

        $response
            ->assertJsonPath('data.community_status', 'rejected')
            ->assertJsonPath('data.status', 'removed')
            ->assertJsonPath('data.is_active', false);

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'community_confirm_count' => 4,
            'community_false_count' => 2,
            'community_status' => 'rejected',
            'status' => 'removed',
        ]);
    }

    public function test_weather_user_can_change_their_vote_without_duplicating_it(): void
    {
        $viewer = User::factory()->create();
        $post = $this->makePost(category: PostCategory::WEATHER_TRANSPORT);
        $this->markPresent($viewer, $post->location);

        $this->actingAs($viewer, 'sanctum')
            ->postJson("/api/posts/{$post->id}/community-vote", ['vote' => 'confirm'])
            ->assertOk();

        $this->actingAs($viewer, 'sanctum')
            ->postJson("/api/posts/{$post->id}/community-vote", ['vote' => 'false'])
            ->assertOk()
            ->assertJsonPath('data.community_confirm_count', 0)
            ->assertJsonPath('data.community_false_count', 1)
            ->assertJsonPath('data.community_vote_by_me', 'false');

        $this->assertDatabaseCount('post_community_votes', 1);
    }

    private function makePost(?User $owner = null, string $category = PostCategory::SPOTTED_LOVE): Post
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
            'category' => $category,
            'musica' => 'Ritornello',
            'sighting_date' => '2026-07-13',
            'expires_at' => now()->addDay(),
            'status' => 'active',
        ]);
    }

    private function markPresent(User $user, Location $location): void
    {
        PresenceSession::query()->create([
            'user_id' => $user->id,
            'location_id' => $location->id,
            'started_at' => now(),
            'last_ping_at' => now(),
        ]);
    }
}
