<?php

namespace App\Contracts;

use App\Models\PostShareMedia;

interface PostShareVideoRenderer
{
    /**
     * @return array{disk: string, path: string, mime: string, size_bytes: int}
     */
    public function render(PostShareMedia $media): array;
}
