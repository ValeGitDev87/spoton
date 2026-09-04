<?php

namespace App\Observers;

use App\Models\Post;
use App\Services\Media\PostShareMediaService;
use App\Services\Media\PostSocialCardService;

class PostObserver
{
    public function __construct(
        private readonly PostShareMediaService $shareMedia,
        private readonly PostSocialCardService $socialCards,
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
            'expires_at',
        ]);
        $becameUnavailable = $post->wasChanged('status')
            && in_array($post->status, ['removed', 'flagged'], true);
        $expired = $post->wasChanged('status') && $post->status === 'expired';

        if ($contentChanged || $becameUnavailable) {
            $this->shareMedia->invalidate($post);
        }

        if ($contentChanged || $becameUnavailable || $expired) {
            $this->socialCards->invalidate($post);
        }
    }

    public function deleting(Post $post): void
    {
        $this->shareMedia->invalidate($post);
        $this->socialCards->invalidate($post);
    }
}
