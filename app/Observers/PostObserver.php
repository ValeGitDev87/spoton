<?php

namespace App\Observers;

use App\Models\Post;
use App\Services\Media\PostShareMediaService;
use App\Services\Media\PostSocialCardService;
use App\Services\PostVideoService;
use App\Services\PostImageService;
use App\Services\PostAudioService;

class PostObserver
{
    public function __construct(
        private readonly PostShareMediaService $shareMedia,
        private readonly PostSocialCardService $socialCards,
        private readonly PostVideoService $postVideoService,
        private readonly PostImageService $postImageService,
        private readonly PostAudioService $postAudioService,
    ) {}

    public function updated(Post $post): void
    {
        $contentChanged = $post->wasChanged([
            'text',
            'category',
            'location_id',
            'is_anonymous',
            'audio_disk',
            'audio_path',
            'audio_duration_seconds',
            'video_path',
            'video_duration_seconds',
            'image_path',
            'expires_at',
        ]);
        $becameUnavailable = $post->wasChanged('status')
            && in_array($post->status, ['removed', 'flagged'], true);
        $expired = $post->wasChanged('status') && $post->status === 'expired';

        if ($contentChanged || $becameUnavailable || $expired) {
            $this->shareMedia->invalidate($post);
        }

        if ($contentChanged || $becameUnavailable || $expired) {
            $this->socialCards->invalidate($post);
        }
        if ($post->wasChanged('status') && in_array($post->status, ['removed', 'expired', 'flagged'], true)) {
            $this->postAudioService->deleteForPost($post);
            $this->postVideoService->deleteForPost($post);
            $this->postImageService->deleteForPost($post);
            $post->fill([
                ...$this->postAudioService->emptyPayload(),
                ...$this->postVideoService->emptyPayload(),
                ...$this->postImageService->emptyPayload(),
            ])->saveQuietly();
        }
    }

    public function deleting(Post $post): void
    {
        $this->shareMedia->invalidate($post);
        $this->socialCards->invalidate($post);
        $this->postVideoService->deleteForPost($post);
        $this->postImageService->deleteForPost($post);
        $this->postAudioService->deleteForPost($post);
    }
}
