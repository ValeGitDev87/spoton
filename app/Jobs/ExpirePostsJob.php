<?php

namespace App\Jobs;

use App\Models\Post;
use App\Services\PostVideoService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ExpirePostsJob implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        Post::query()
            ->where('status', 'active')
            ->where('expires_at', '<=', now())
            ->chunkById(100, function ($posts): void {
                $postVideoService = app(PostVideoService::class);

                $posts->each(function (Post $post) use ($postVideoService): void {
                    $postVideoService->deleteForPost($post);
                    $post->update([
                        'status' => 'expired',
                        ...$postVideoService->emptyPayload(),
                    ]);
                });
            });
    }
}
