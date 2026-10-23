<?php

namespace Tests\Feature;

use App\Models\Like;
use App\Models\Location;
use App\Models\Post;
use App\Models\PresenceSession;
use App\Models\User;
use App\Services\UserBlockService;
use App\Support\PostCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
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

    public function test_authenticated_user_can_view_paginated_post_likes_without_sensitive_data(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        $likers = User::factory()->count(3)->create();
        $post = $this->makePost($owner);

        foreach ($likers as $liker) {
            Like::query()->create([
                'post_id' => $post->id,
                'user_id' => $liker->id,
            ]);
        }

        $response = $this
            ->actingAs($viewer, 'sanctum')
            ->getJson("/api/posts/{$post->id}/likes?per_page=2")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.last_page', 2)
            ->assertJsonPath('meta.total', 3)
            ->assertJsonMissingPath('data.0.email')
            ->assertJsonMissingPath('data.0.last_known_latitude');

        $this->assertContains($response->json('data.0.id'), $likers->pluck('id')->all());
    }

    public function test_guest_cannot_view_post_likes(): void
    {
        $post = $this->makePost();

        $this
            ->getJson("/api/posts/{$post->id}/likes")
            ->assertUnauthorized();
    }

    public function test_empty_likes_list_has_zero_total(): void
    {
        $viewer = User::factory()->create();
        $post = $this->makePost();

        $this->actingAs($viewer, 'sanctum')
            ->getJson("/api/posts/{$post->id}/likes")
            ->assertOk()
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('meta.total', 0);
    }

    public function test_blocked_users_are_absent_from_likes_list(): void
    {
        $viewer = User::factory()->create();
        $visible = User::factory()->create();
        $blocked = User::factory()->create();
        $post = $this->makePost();
        Like::query()->create(['post_id' => $post->id, 'user_id' => $visible->id]);
        Like::query()->create(['post_id' => $post->id, 'user_id' => $blocked->id]);
        app(UserBlockService::class)->block($viewer, $blocked);

        $this->actingAs($viewer, 'sanctum')
            ->getJson("/api/posts/{$post->id}/likes")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $visible->id)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonMissing([$blocked->id]);
    }

    public function test_ghost_author_like_does_not_reveal_author_identity(): void
    {
        $owner = User::factory()->create([
            'display_name' => 'Autore Segreto',
            'avatar_url' => '/storage/profile-photos/secret.jpg',
        ]);
        $viewer = User::factory()->create();
        $post = $this->makePost($owner);
        $post->update(['is_anonymous' => true]);
        Like::query()->create(['post_id' => $post->id, 'user_id' => $owner->id]);

        $this->actingAs($viewer, 'sanctum')
            ->getJson("/api/posts/{$post->id}/likes")
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', null)
            ->assertJsonPath('data.0.display_name', 'Ghost')
            ->assertJsonPath('data.0.avatar_url', null)
            ->assertJsonPath('data.0.is_anonymous', true)
            ->assertDontSee('Autore Segreto')
            ->assertDontSee('profile-photos/secret.jpg');
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

    public function test_weather_vote_works_without_presence_or_nearby_coordinates(): void
    {
        $viewer = User::factory()->create(['last_known_latitude' => 45.46, 'last_known_longitude' => 9.19]);
        $post = $this->makePost(category: PostCategory::WEATHER_TRANSPORT);
        foreach (['confirm', 'false'] as $vote) {
            $this->actingAs($viewer, 'sanctum')
                ->postJson("/api/posts/{$post->id}/community-vote", ['vote' => $vote])
                ->assertOk()->assertJsonPath('data.community_vote_by_me', $vote);
        }
        $this->assertDatabaseCount('presence_sessions', 0);
        $this->postJson("/api/posts/{$post->id}/community-vote", ['vote' => 'false'])
            ->assertOk()->assertJsonPath('data.community_false_count', 1);
        $this->assertDatabaseCount('post_community_votes', 1);
    }

    public function test_official_persistent_info_cannot_be_removed_by_community_votes(): void
    {
        $official = User::factory()->create(['is_system' => true]);
        $viewer = User::factory()->create();
        $post = $this->makePost($official, PostCategory::WEATHER_TRANSPORT);
        $post->update(['is_persistent_info' => true, 'expires_at' => null]);
        $this->markPresent($viewer, $post->location);

        $this->actingAs($viewer, 'sanctum')
            ->postJson("/api/posts/{$post->id}/community-vote", ['vote' => 'false'])
            ->assertUnprocessable();
        $this->assertSame('active', $post->refresh()->status);
        $this->assertSame(0, $post->community_false_count);
    }

    public function test_many_weather_reactions_do_not_remove_post_or_media(): void
    {
        Storage::fake('public');
        $post = $this->makePost(category: PostCategory::WEATHER_TRANSPORT);
        Storage::disk('public')->put('post-images/keep.jpg', 'photo');
        $post->update(['image_disk' => 'public', 'image_path' => 'post-images/keep.jpg', 'image_url' => '/storage/post-images/keep.jpg']);
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
            ->assertJsonPath('data.community_status', 'pending')
            ->assertJsonPath('data.status', 'active');

        $falseVoters = User::factory()->count(8)->create();

        foreach ($falseVoters as $index => $user) {
            $this->markPresent($user, $post->location);
            $response = $this
                ->actingAs($user, 'sanctum')
                ->postJson("/api/posts/{$post->id}/community-vote", ['vote' => 'false'])
                ->assertOk()
                ->assertJsonPath('data.community_false_count', $index + 1);
        }

        $response
            ->assertJsonPath('data.community_status', 'pending')
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.is_active', true);

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'community_confirm_count' => 4,
            'community_false_count' => 8,
            'community_status' => 'pending',
            'status' => 'active',
        ]);
        Storage::disk('public')->assertExists('post-images/keep.jpg');
        $this->getJson('/api/posts/feed')->assertOk()->assertJsonFragment(['id' => $post->id]);
        $this->getJson("/api/locations/{$post->location_id}/stories")->assertOk()->assertJsonFragment(['id' => $post->id]);
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

        $this->postJson("/api/posts/{$post->id}/community-vote", ['vote' => 'false'])
            ->assertOk()->assertJsonPath('data.community_false_count', 1);
        $this->assertDatabaseCount('post_community_votes', 1);
    }

    public function test_owner_can_view_likes_and_react_to_accessible_own_post(): void
    {
        $owner = User::factory()->create();
        $post = $this->makePost($owner, PostCategory::WEATHER_TRANSPORT);
        $this->actingAs($owner, 'sanctum')->getJson("/api/posts/{$post->id}/likes")->assertOk();
        $this->postJson("/api/posts/{$post->id}/community-vote", ['vote' => 'confirm'])->assertOk();
    }

    public function test_inaccessible_posts_reject_details_likes_comments_and_reactions(): void
    {
        $viewer = User::factory()->create();
        $post = $this->makePost(category: PostCategory::WEATHER_TRANSPORT);
        $this->actingAs($viewer, 'sanctum');
        foreach (['expired', 'elapsed', 'removed', 'hidden', 'location'] as $reason) {
            $post->update(['status' => in_array($reason, ['expired', 'removed']) ? $reason : 'active']);
            $post->update(['expires_at' => $reason === 'elapsed' ? now()->subSecond() : now()->addHour()]);
            if ($reason === 'hidden') $viewer->hiddenPosts()->create(['post_id' => $post->id]);
            if ($reason === 'location') { $viewer->hiddenPosts()->delete(); $post->location->update(['is_active' => false]); }
            $this->getJson("/api/posts/{$post->id}")->assertNotFound();
            $this->getJson("/api/posts/{$post->id}/likes")->assertNotFound();
            $this->getJson("/api/posts/{$post->id}/comments")->assertNotFound();
            $this->postJson("/api/posts/{$post->id}/community-vote", ['vote' => 'false'])->assertNotFound();
        }
    }

    public function test_block_between_author_and_viewer_blocks_reactions_and_likes_list(): void
    {
        $viewer = User::factory()->create();
        $post = $this->makePost(category: PostCategory::WEATHER_TRANSPORT);
        app(UserBlockService::class)->block($viewer, $post->author);
        $this->actingAs($viewer, 'sanctum')->getJson("/api/posts/{$post->id}/likes")->assertForbidden();
        $this->postJson("/api/posts/{$post->id}/community-vote", ['vote' => 'confirm'])->assertForbidden();
    }

    public function test_suspended_user_cannot_react(): void
    {
        $viewer = User::factory()->create(['is_suspended' => true]);
        $post = $this->makePost(category: PostCategory::WEATHER_TRANSPORT);
        $this->actingAs($viewer, 'sanctum')->postJson("/api/posts/{$post->id}/community-vote", ['vote' => 'false'])->assertForbidden();
        $this->assertDatabaseCount('post_community_votes', 0);
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
