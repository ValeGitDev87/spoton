<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\Post;
use App\Models\PostShareMedia;
use App\Models\User;
use App\Services\Media\ExpiredPostShareMediaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminExpiredMediaWebTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_only_admin_can_view_expired_media(): void
    {
        $this->get('/admin/expired-media')->assertRedirect('/login');

        $user = User::factory()->create(['is_admin' => false]);
        $this->actingAs($user)->get('/admin/expired-media')->assertForbidden();

        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin)->get('/admin/expired-media')->assertOk();
    }

    public function test_admin_page_shows_only_expired_or_unavailable_media(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $expired = $this->createMedia(now()->subMinute(), 'expired-video');
        $missing = $this->createMedia(now()->subMinutes(2), 'missing-video');
        Storage::disk('public')->delete($missing->path);
        $active = $this->createMedia(now()->addHour(), 'active-video');

        $this->actingAs($admin)
            ->get('/admin/expired-media')
            ->assertOk()
            ->assertSee('Media scaduti')
            ->assertSee('expired-video')
            ->assertDontSee('active-video')
            ->assertSee('Mancante')
            ->assertSee('11 B');

        $this->assertDatabaseHas('post_share_media', ['id' => $active->id]);
        $this->assertDatabaseHas('post_share_media', ['id' => $expired->id]);
        $this->assertSame(
            ['records' => 2, 'files' => 1, 'bytes' => 11],
            app(ExpiredPostShareMediaService::class)->summary(),
        );
    }

    public function test_admin_purge_deletes_only_expired_media_and_files(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $expired = $this->createMedia(now()->subMinute(), 'expired-video');
        $active = $this->createMedia(now()->addHour(), 'active-video');

        $this->actingAs($admin)
            ->post('/admin/expired-media/purge')
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseMissing('post_share_media', ['id' => $expired->id]);
        $this->assertDatabaseHas('post_share_media', ['id' => $active->id]);
        Storage::disk('public')->assertMissing($expired->path);
        Storage::disk('public')->assertExists($active->path);
    }

    private function createMedia(\DateTimeInterface $expiration, string $text): PostShareMedia
    {
        $user = User::factory()->create();
        $location = Location::query()->create([
            'name' => 'Luogo Media',
            'short' => 'Luogo Media',
            'city' => 'Napoli',
            'type' => 'altro',
            'latitude' => 40.83,
            'longitude' => 14.24,
            'geo_radius_meters' => 100,
            'is_active' => true,
        ]);
        $post = Post::query()->create([
            'author_id' => $user->id,
            'location_id' => $location->id,
            'text' => $text,
            'sighting_date' => now()->toDateString(),
            'audio_disk' => 'public',
            'audio_path' => 'post-audios/'.$text.'.m4a',
            'audio_duration_seconds' => 8,
            'expires_at' => $expiration,
            'status' => $expiration < now() ? 'expired' : 'active',
        ]);
        $path = 'share-videos/'.$post->id.'.mp4';
        Storage::disk('public')->put($path, 'video-bytes');

        return PostShareMedia::query()->create([
            'post_id' => $post->id,
            'template_version' => 'v1',
            'status' => PostShareMedia::STATUS_READY,
            'disk' => 'public',
            'path' => $path,
            'mime' => 'video/mp4',
            'size_bytes' => 11,
            'generated_at' => now(),
            'expires_at' => $expiration,
        ]);
    }
}
