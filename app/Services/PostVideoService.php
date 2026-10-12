<?php

namespace App\Services;

use App\Models\Post;
use Illuminate\Support\Facades\Storage;

class PostVideoService
{
    public function deleteForPost(Post $post): void
    {
        if (! $post->video_disk || ! $post->video_path) {
            return;
        }

        Storage::disk($post->video_disk)->delete($post->video_path);
    }

    public function emptyPayload(): array
    {
        return [
            'video_disk' => null,
            'video_path' => null,
            'video_url' => null,
            'video_mime' => null,
            'video_size_bytes' => null,
            'video_duration_seconds' => null,
        ];
    }

}
