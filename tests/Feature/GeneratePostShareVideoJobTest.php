<?php

namespace Tests\Feature;

use App\Contracts\PostShareVideoRenderer;
use App\Jobs\GeneratePostShareVideo;
use App\Models\Location;
use App\Models\Post;
use App\Models\PostShareMedia;
use App\Models\User;
use App\Services\Media\FfmpegPostShareVideoRenderer;
use App\Services\Media\PublicPostMediaContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class GeneratePostShareVideoJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('spoton.share_video.enabled', true);
        Storage::fake('public');
    }

    public function test_job_marks_rendered_video_ready(): void
    {
        $media = $this->createPendingMedia();
        $renderer = new class implements PostShareVideoRenderer
        {
            public function render(PostShareMedia $media): array
            {
                $path = "share-videos/{$media->post_id}-v1.mp4";
                Storage::disk('public')->put($path, 'video-ready');

                return [
                    'disk' => 'public',
                    'path' => $path,
                    'mime' => 'video/mp4',
                    'size_bytes' => 11,
                ];
            }
        };

        (new GeneratePostShareVideo($media->id))->handle($renderer);

        $media->refresh();
        $this->assertSame(PostShareMedia::STATUS_READY, $media->status);
        $this->assertSame('video/mp4', $media->mime);
        $this->assertSame(11, $media->size_bytes);
        $this->assertNull($media->error_code);
    }

    public function test_job_records_safe_error_code_without_leaving_ready_media(): void
    {
        $media = $this->createPendingMedia();
        $renderer = new class implements PostShareVideoRenderer
        {
            public function render(PostShareMedia $media): array
            {
                throw new RuntimeException('errore con comando sensibile');
            }
        };

        (new GeneratePostShareVideo($media->id))->handle($renderer);

        $media->refresh();
        $this->assertSame(PostShareMedia::STATUS_FAILED, $media->status);
        $this->assertSame('render_failed', $media->error_code);
        $this->assertNull($media->path);
    }

    public function test_job_has_stable_unique_key_for_the_same_media(): void
    {
        $media = $this->createPendingMedia();

        $this->assertSame(
            (new GeneratePostShareVideo($media->id))->uniqueId(),
            (new GeneratePostShareVideo($media->id))->uniqueId(),
        );
    }

    public function test_ffmpeg_failure_cleans_temporary_files_and_does_not_interpret_user_text(): void
    {
        $media = $this->createPendingMedia();
        $media->post->update([
            'text' => "Testo con 'virgolette'; touch /tmp/spoton-share-injection",
            'is_anonymous' => true,
        ]);
        config()->set('spoton.share_video.ffmpeg_binary', '/missing/spoton-ffmpeg');
        config()->set(
            'spoton.share_video.font_path',
            PHP_OS_FAMILY === 'Darwin'
                ? '/System/Library/Fonts/Supplemental/Arial.ttf'
                : '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
        );
        config()->set(
            'spoton.share_video.font_bold_path',
            PHP_OS_FAMILY === 'Darwin'
                ? '/System/Library/Fonts/Supplemental/Arial Bold.ttf'
                : '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
        );
        $temporaryDirectory = storage_path('app/share-video-temp/'.$media->id);

        try {
            (new FfmpegPostShareVideoRenderer(new PublicPostMediaContent))->render($media->fresh());
            $this->fail('Il renderer doveva fallire con un binario FFmpeg inesistente.');
        } catch (\Throwable) {
            $this->assertDirectoryDoesNotExist($temporaryDirectory);
            $this->assertFileDoesNotExist('/tmp/spoton-share-injection');
            $this->assertSame([], Storage::disk('public')->files('share-videos'));
        } finally {
            File::delete('/tmp/spoton-share-injection');
        }
    }

    private function createPendingMedia(): PostShareMedia
    {
        $user = User::factory()->create();
        $location = Location::query()->create([
            'name' => 'Stazione Test',
            'short' => 'Stazione Test',
            'city' => 'Napoli',
            'type' => 'metro',
            'latitude' => 40.83,
            'longitude' => 14.24,
            'geo_radius_meters' => 100,
            'is_active' => true,
        ]);
        Storage::disk('public')->put('post-audios/job.m4a', 'audio');
        $post = Post::query()->create([
            'author_id' => $user->id,
            'location_id' => $location->id,
            'text' => 'Testo con virgolette, due punti: e apostrofo.',
            'sighting_date' => now()->toDateString(),
            'audio_disk' => 'public',
            'audio_path' => 'post-audios/job.m4a',
            'audio_duration_seconds' => 7,
            'expires_at' => now()->addHours(48),
            'status' => 'active',
        ]);

        return PostShareMedia::query()->create([
            'post_id' => $post->id,
            'template_version' => 'v1',
            'status' => PostShareMedia::STATUS_PENDING,
            'expires_at' => $post->expires_at,
        ]);
    }
}
