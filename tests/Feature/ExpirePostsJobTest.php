<?php

namespace Tests\Feature;

use App\Jobs\ExpirePostsJob;
use App\Models\Location;
use App\Models\Post;
use App\Models\PostShareMedia;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExpirePostsJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_expire_posts_job_marks_due_active_posts_as_expired(): void
    {
        Carbon::setTestNow('2026-07-09 12:00:00');
        Storage::fake('public');

        $user = User::factory()->create();
        $location = Location::query()->create([
            'name' => 'Metro Mergellina',
            'short' => 'Metro Mergellina',
            'city' => 'Napoli',
            'type' => 'metro',
            'latitude' => 40.8319000,
            'longitude' => 14.2193000,
            'geo_radius_meters' => 100,
            'is_active' => true,
        ]);

        $postVideoPath = 'post-videos/expired-test.mp4';
        $expired = $this->makePost($user, $location, now()->subMinute(), 'active', [
            'video_disk' => 'public',
            'video_path' => $postVideoPath,
            'video_url' => '/storage/'.$postVideoPath,
            'video_mime' => 'video/mp4',
            'video_size_bytes' => 10,
            'video_duration_seconds' => 12,
        ]);
        $stillActive = $this->makePost($user, $location, now()->addMinute(), 'active');
        $removed = $this->makePost($user, $location, now()->subMinute(), 'removed');
        $cardPath = "share-cards/{$expired->id}-v1.png";
        $shareVideoPath = "share-videos/{$expired->id}-v1.mp4";
        Storage::disk('public')->put($cardPath, 'card');
        Storage::disk('public')->put($shareVideoPath, 'share-video');
        Storage::disk('public')->put($postVideoPath, 'post-video');
        PostShareMedia::query()->create([
            'post_id' => $expired->id,
            'template_version' => 'v1',
            'status' => PostShareMedia::STATUS_READY,
            'disk' => 'public',
            'path' => $shareVideoPath,
            'mime' => 'video/mp4',
            'size_bytes' => 5,
            'generated_at' => now()->subHour(),
            'expires_at' => $expired->expires_at,
        ]);

        (new ExpirePostsJob)->handle();

        $this->assertDatabaseHas('posts', [
            'id' => $expired->id,
            'status' => 'expired',
        ]);

        $this->assertDatabaseHas('posts', [
            'id' => $stillActive->id,
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('posts', [
            'id' => $removed->id,
            'status' => 'removed',
        ]);
        Storage::disk('public')->assertMissing($cardPath);
        Storage::disk('public')->assertMissing($postVideoPath);
        Storage::disk('public')->assertMissing($shareVideoPath);
        $this->assertDatabaseMissing('post_share_media', ['post_id' => $expired->id]);
        $this->assertDatabaseHas('posts', [
            'id' => $expired->id,
            'video_path' => null,
            'video_url' => null,
        ]);

        Carbon::setTestNow();
    }

    private function makePost(
        User $user,
        Location $location,
        Carbon $expiresAt,
        string $status,
        array $overrides = [],
    ): Post
    {
        return Post::query()->create([
            'author_id' => $user->id,
            'location_id' => $location->id,
            'text' => 'Post test',
            'musica' => null,
            'sighting_date' => '2026-07-09',
            'expires_at' => $expiresAt,
            'status' => $status,
            ...$overrides,
        ]);
    }
}
