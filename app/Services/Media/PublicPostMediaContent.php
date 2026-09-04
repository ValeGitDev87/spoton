<?php

namespace App\Services\Media;

use App\Models\Post;

class PublicPostMediaContent
{
    public function author(Post $post): string
    {
        return $post->is_anonymous
            ? 'Ghost'
            : ($post->author?->display_name ?: 'SpotOn');
    }

    public function text(Post $post): string
    {
        $post->loadMissing('mentions:id,display_name');
        $text = $post->text;

        foreach ($post->mentions as $mentionedUser) {
            $text = preg_replace(
                '/@'.preg_quote($mentionedUser->display_name, '/').'(?![\p{L}\p{N}_])/iu',
                '@utente',
                $text,
            ) ?? $text;
        }

        return preg_replace(
            '/(^|\s)@tutti(?![\p{L}\p{N}_])/iu',
            '$1@community',
            $text,
        ) ?? $text;
    }
}
