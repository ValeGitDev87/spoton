<?php

namespace Tests\Feature;

use App\Jobs\GeneratePostShareVideo;
use App\Models\Location;
use App\Models\Post;
use App\Models\PostShareMedia;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PostShareVideoApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('spoton.share_video.enabled', true);
        config()->set('spoton.share_video.disk', 'public');
        config()->set('spoton.share_video.template_version', 'v1');
        Storage::fake('public');
        Queue::fake();
    }

    public function test_authenticated_user_can_queue_video_only_once(): void
    {
        $user = User::factory()->create();
        $post = $this->createPostWithAudio();
        Sanctum::actingAs($user);

        $this->postJson("/api/posts/{$post->id}/share-video")
            ->assertStatus(202)
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.template_version', 'v1');

        $this->postJson("/api/posts/{$post->id}/share-video")
            ->assertStatus(202)
            ->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseCount('post_share_media', 1);
        Queue::assertPushed(GeneratePostShareVideo::class, 1);
        Queue::assertPushed(GeneratePostShareVideo::class, fn (GeneratePostShareVideo $job) => $job->queue === 'media');
    }

    public function test_ready_video_is_reused_with_200_response(): void
    {
        $user = User::factory()->create();
        $post = $this->createPostWithAudio();
        Storage::disk('public')->put("share-videos/{$post->id}-v1.mp4", 'video');
        PostShareMedia::query()->create([
            'post_id' => $post->id,
            'template_version' => 'v1',
            'status' => PostShareMedia::STATUS_READY,
            'disk' => 'public',
            'path' => "share-videos/{$post->id}-v1.mp4",
            'mime' => 'video/mp4',
            'size_bytes' => 5,
            'generated_at' => now(),
            'expires_at' => $post->expires_at,
        ]);
        Sanctum::actingAs($user);

        $response = $this->postJson("/api/posts/{$post->id}/share-video")
            ->assertOk()
            ->assertJsonPath('data.status', 'ready')
            ->assertJsonPath('data.mime', 'video/mp4')
            ->assertJsonPath('data.size_bytes', 5);

        $this->get($response->json('data.url'))
            ->assertOk()
            ->assertHeader('Cache-Control', 'max-age=0, no-store, private');

        Queue::assertNothingPushed();
    }

    public function test_video_request_rejects_disabled_feature_unavailable_posts_and_missing_audio(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $withoutAudio = $this->createPostWithAudio(['audio_path' => null, 'audio_disk' => null]);
        $this->postJson("/api/posts/{$withoutAudio->id}/share-video")->assertStatus(422);

        $expired = $this->createPostWithAudio(['expires_at' => now()->subMinute()]);
        $this->postJson("/api/posts/{$expired->id}/share-video")->assertNotFound();

        $removed = $this->createPostWithAudio(['status' => 'removed']);
        $this->postJson("/api/posts/{$removed->id}/share-video")->assertNotFound();

        $inactiveLocation = $this->createPostWithAudio();
        $inactiveLocation->location->update(['is_active' => false]);
        $this->postJson("/api/posts/{$inactiveLocation->id}/share-video")->assertNotFound();

        config()->set('spoton.share_video.enabled', false);
        $active = $this->createPostWithAudio();
        $this->postJson("/api/posts/{$active->id}/share-video")->assertStatus(503);
    }

    public function test_daily_limit_counts_only_new_generations(): void
    {
        config()->set('spoton.share_video.daily_limit', 1);
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $first = $this->createPostWithAudio();
        $second = $this->createPostWithAudio();

        $this->postJson("/api/posts/{$first->id}/share-video")->assertStatus(202);
        $this->postJson("/api/posts/{$first->id}/share-video")->assertStatus(202);
        $this->postJson("/api/posts/{$second->id}/share-video")->assertStatus(429);
    }

    public function test_beta_allowlist_limits_new_generations_without_affecting_the_feature_flag(): void
    {
        config()->set('spoton.share_video.beta_user_emails', ['demo@spoton.test']);
        $post = $this->createPostWithAudio();
        $regularUser = User::factory()->create(['email' => 'regular@spoton.test']);
        Sanctum::actingAs($regularUser);

        $this->postJson("/api/posts/{$post->id}/share-video")->assertForbidden();

        $demoUser = User::factory()->create(['email' => 'demo@spoton.test']);
        Sanctum::actingAs($demoUser);

        $this->postJson("/api/posts/{$post->id}/share-video")->assertStatus(202);
    }

    public function test_post_change_invalidates_generated_video(): void
    {
        $post = $this->createPostWithAudio();
        $path = "share-videos/{$post->id}-v1.mp4";
        Storage::disk('public')->put($path, 'video');
        PostShareMedia::query()->create([
            'post_id' => $post->id,
            'template_version' => 'v1',
            'status' => PostShareMedia::STATUS_READY,
            'disk' => 'public',
            'path' => $path,
            'mime' => 'video/mp4',
            'size_bytes' => 5,
            'generated_at' => now(),
            'expires_at' => $post->expires_at,
        ]);

        $post->update(['text' => 'Testo modificato']);

        $this->assertDatabaseMissing('post_share_media', ['post_id' => $post->id]);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_expired_video_is_not_accessible_and_post_deletion_removes_media(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $expiredPost = $this->createPostWithAudio(['expires_at' => now()->subMinute()]);
        $expiredPath = "share-videos/{$expiredPost->id}-v1.mp4";
        Storage::disk('public')->put($expiredPath, 'expired-video');
        $expiredMedia = PostShareMedia::query()->create([
            'post_id' => $expiredPost->id,
            'template_version' => 'v1',
            'status' => PostShareMedia::STATUS_READY,
            'disk' => 'public',
            'path' => $expiredPath,
            'mime' => 'video/mp4',
            'size_bytes' => 13,
            'generated_at' => now()->subHour(),
            'expires_at' => $expiredPost->expires_at,
        ]);

        $this->getJson("/api/posts/{$expiredPost->id}/share-video")->assertNotFound();
        $this->get(route('post-share-media.show', $expiredMedia))->assertNotFound();

        $activePost = $this->createPostWithAudio();
        $activePath = "share-videos/{$activePost->id}-v1.mp4";
        Storage::disk('public')->put($activePath, 'active-video');
        PostShareMedia::query()->create([
            'post_id' => $activePost->id,
            'template_version' => 'v1',
            'status' => PostShareMedia::STATUS_READY,
            'disk' => 'public',
            'path' => $activePath,
            'mime' => 'video/mp4',
            'size_bytes' => 12,
            'generated_at' => now(),
            'expires_at' => $activePost->expires_at,
        ]);

        $activePost->delete();

        $this->assertDatabaseMissing('post_share_media', ['post_id' => $activePost->id]);
        Storage::disk('public')->assertMissing($activePath);
    }

    private function createPostWithAudio(array $overrides = []): Post
    {
        $author = User::factory()->create();
        $location = Location::query()->create([
            'name' => 'Piazza Audio',
            'short' => 'Piazza Audio',
            'city' => 'Napoli',
            'type' => 'piazza',
            'latitude' => 40.83,
            'longitude' => 14.24,
            'geo_radius_meters' => 100,
            'is_active' => true,
        ]);
        $path = 'post-audios/source-'.$author->id.'.m4a';
        Storage::disk('public')->put($path, 'audio');

        return Post::query()->create([
            'author_id' => $author->id,
            'location_id' => $location->id,
            'text' => 'Nota audio da condividere.',
            'category' => 'spotted_love',
            'sighting_date' => now()->toDateString(),
            'is_anonymous' => false,
            'audio_disk' => 'public',
            'audio_path' => $path,
            'audio_url' => '/storage/'.$path,
            'audio_mime' => 'audio/mp4',
            'audio_size_bytes' => 5,
            'audio_duration_seconds' => 8,
            'expires_at' => now()->addHours(48),
            'status' => 'active',
            ...$overrides,
        ])->load('location');
    }
}
