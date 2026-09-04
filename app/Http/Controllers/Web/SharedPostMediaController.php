<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\PostShareMedia;
use App\Services\Media\PostShareMediaService;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SharedPostMediaController extends Controller
{
    public function __invoke(
        PostShareMedia $media,
        PostShareMediaService $mediaService,
    ): StreamedResponse {
        abort_unless($mediaService->isReusable($media), 404);

        $media->loadMissing('post.location');
        abort_unless(
            $media->post?->isActive() && $media->post->location?->isPubliclyVisible(),
            404,
        );

        return Storage::disk($media->disk)->response(
            $media->path,
            'spoton-'.$media->post_id.'.mp4',
            [
                'Cache-Control' => 'private, no-store, max-age=0',
                'Content-Type' => $media->mime ?: 'video/mp4',
                'X-Content-Type-Options' => 'nosniff',
            ],
        );
    }
}
