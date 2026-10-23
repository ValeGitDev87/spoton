<?php

namespace App\Services;

use App\Models\Post;
use App\Models\User;

class PostAccessService
{
    public function ensureVisible(Post $post, User $viewer): void
    {
        app(UserBlockService::class)->ensureInteractionAllowed($viewer->id, $post->author_id);
        abort_unless(
            $post->isActive()
            && $post->location?->isPubliclyVisible()
            && ! $viewer->hiddenPosts()->where('post_id', $post->id)->exists(),
            404,
            'Questo post non è più disponibile.',
        );
    }
}
