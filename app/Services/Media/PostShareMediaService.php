<?php

namespace App\Services\Media;

use App\Jobs\GeneratePostShareVideo;
use App\Models\Post;
use App\Models\PostShareMedia;
use App\Models\User;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

class PostShareMediaService
{
    public function request(Post $post, User $user): PostShareMedia
    {
        $this->assertPostAvailable($post);
        $template = (string) config('spoton.share_video.template_version', 'v1');
        $lockName = "post-share-video:{$post->id}:{$template}";

        try {
            return Cache::lock($lockName, 10)->block(3, function () use ($post, $user, $template): PostShareMedia {
                $media = PostShareMedia::query()
                    ->where('post_id', $post->id)
                    ->where('template_version', $template)
                    ->first();

                if ($media && $this->isReusable($media)) {
                    return $media;
                }

                if ($media && in_array($media->status, [
                    PostShareMedia::STATUS_PENDING,
                    PostShareMedia::STATUS_PROCESSING,
                ], true) && $media->updated_at->isAfter(now()->subMinutes(3))) {
                    return $media;
                }

                $rateLimitKey = 'share-video-generation:'.$user->id;
                $dailyLimit = (int) config('spoton.share_video.daily_limit', 3);

                if (RateLimiter::tooManyAttempts($rateLimitKey, $dailyLimit)) {
                    throw new TooManyRequestsHttpException(
                        RateLimiter::availableIn($rateLimitKey),
                        'Hai raggiunto il limite giornaliero di nuovi video.',
                    );
                }

                if ($media) {
                    $this->deleteStoredFile($media);
                    $media->update([
                        'status' => PostShareMedia::STATUS_PENDING,
                        'disk' => null,
                        'path' => null,
                        'mime' => null,
                        'size_bytes' => null,
                        'generated_at' => null,
                        'expires_at' => $post->expires_at,
                        'error_code' => null,
                    ]);
                } else {
                    $media = PostShareMedia::query()->create([
                        'post_id' => $post->id,
                        'template_version' => $template,
                        'status' => PostShareMedia::STATUS_PENDING,
                        'expires_at' => $post->expires_at,
                    ]);
                }

                RateLimiter::hit($rateLimitKey, 86400);
                GeneratePostShareVideo::dispatch($media->id)
                    ->onQueue((string) config('spoton.share_video.queue', 'media'));

                return $media->refresh();
            });
        } catch (LockTimeoutException) {
            $existing = PostShareMedia::query()
                ->where('post_id', $post->id)
                ->where('template_version', $template)
                ->first();

            if ($existing) {
                return $existing;
            }

            throw new HttpException(409, 'La generazione e gia in preparazione. Riprova tra poco.');
        }
    }

    public function current(Post $post): ?PostShareMedia
    {
        $this->assertPostAvailable($post);

        return PostShareMedia::query()
            ->where('post_id', $post->id)
            ->where('template_version', config('spoton.share_video.template_version', 'v1'))
            ->first();
    }

    public function assertPostAvailable(Post $post): void
    {
        $post->loadMissing('location');
        abort_unless($post->isActive() && $post->location?->isPubliclyVisible(), 404);

        if (! $post->audio_disk || ! $post->audio_path) {
            abort(422, 'Questo post non contiene una nota audio.');
        }
    }

    public function invalidate(Post $post): void
    {
        $post->shareMedia()->get()->each(function (PostShareMedia $media): void {
            $this->deleteStoredFile($media);
            $media->delete();
        });
    }

    public function deleteStoredFile(PostShareMedia $media): void
    {
        if ($media->disk && $media->path) {
            Storage::disk($media->disk)->delete($media->path);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(PostShareMedia $media): array
    {
        $url = null;

        if ($this->isReusable($media)) {
            $url = route('post-share-media.show', $media);
        }

        return [
            'id' => $media->id,
            'status' => $media->status,
            'template_version' => $media->template_version,
            'url' => $url,
            'mime' => $media->mime,
            'size_bytes' => $media->size_bytes,
            'generated_at' => $media->generated_at?->toISOString(),
            'expires_at' => $media->expires_at?->toISOString(),
            'error_code' => $media->error_code,
            'poll_after_seconds' => $media->status === PostShareMedia::STATUS_READY ? null : 2,
        ];
    }

    public function isReusable(PostShareMedia $media): bool
    {
        return $media->status === PostShareMedia::STATUS_READY
            && $media->expires_at?->isFuture()
            && is_string($media->disk)
            && is_string($media->path)
            && Storage::disk($media->disk)->exists($media->path);
    }
}
