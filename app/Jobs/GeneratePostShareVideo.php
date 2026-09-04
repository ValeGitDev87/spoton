<?php

namespace App\Jobs;

use App\Contracts\PostShareVideoRenderer;
use App\Models\PostShareMedia;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class GeneratePostShareVideo implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout;

    public int $uniqueFor = 180;

    public function __construct(public readonly string $mediaId)
    {
        $this->timeout = (int) config('spoton.share_video.timeout', 60) + 10;
    }

    public function uniqueId(): string
    {
        return $this->mediaId;
    }

    public function handle(PostShareVideoRenderer $renderer): void
    {
        $media = PostShareMedia::query()
            ->with(['post.author', 'post.location'])
            ->find($this->mediaId);

        if (! $media || ! in_array($media->status, [
            PostShareMedia::STATUS_PENDING,
            PostShareMedia::STATUS_PROCESSING,
        ], true)) {
            return;
        }

        if (! config('spoton.share_video.enabled')) {
            $this->markFailed($media, 'feature_disabled');

            return;
        }

        $post = $media->post;

        if (! $post || ! $post->isActive() || ! $post->location?->isPubliclyVisible() || ! $post->audio_path) {
            $this->markFailed($media, 'post_unavailable');

            return;
        }

        $media->update([
            'status' => PostShareMedia::STATUS_PROCESSING,
            'error_code' => null,
        ]);

        try {
            $result = $renderer->render($media->fresh(['post.author', 'post.location']));
            $media->update([
                'status' => PostShareMedia::STATUS_READY,
                'disk' => $result['disk'],
                'path' => $result['path'],
                'mime' => $result['mime'],
                'size_bytes' => $result['size_bytes'],
                'generated_at' => now(),
                'expires_at' => $post->expires_at,
                'error_code' => null,
            ]);
        } catch (Throwable $exception) {
            $this->markFailed($media, 'render_failed');
            Log::warning('spoton.share_video.failed', [
                'media_id' => $media->id,
                'exception' => $exception::class,
            ]);
        }
    }

    private function markFailed(PostShareMedia $media, string $errorCode): void
    {
        $media->update([
            'status' => PostShareMedia::STATUS_FAILED,
            'disk' => null,
            'path' => null,
            'mime' => null,
            'size_bytes' => null,
            'generated_at' => null,
            'error_code' => $errorCode,
        ]);
    }
}
