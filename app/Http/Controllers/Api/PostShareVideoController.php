<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\PostShareMedia;
use App\Services\Media\PostShareMediaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PostShareVideoController extends Controller
{
    public function store(
        Request $request,
        Post $post,
        PostShareMediaService $mediaService,
    ): JsonResponse {
        abort_unless(config('spoton.share_video.enabled'), 503, 'La generazione video non e temporaneamente disponibile.');
        $betaEmails = config('spoton.share_video.beta_user_emails', []);
        abort_unless(
            $betaEmails === []
                || $request->user()->is_admin
                || in_array($request->user()->email, $betaEmails, true),
            403,
            'La generazione video non e ancora attiva per questo account.',
        );

        $media = $mediaService->request($post, $request->user());
        $status = $media->status === PostShareMedia::STATUS_READY ? 200 : 202;

        return response()->json([
            'message' => $status === 200 ? 'Video pronto.' : 'Video in preparazione.',
            'data' => $mediaService->payload($media),
        ], $status);
    }

    public function show(Post $post, PostShareMediaService $mediaService): JsonResponse
    {
        abort_unless(config('spoton.share_video.enabled'), 503, 'La generazione video non e temporaneamente disponibile.');

        $media = $mediaService->current($post);
        abort_unless($media, 404, 'Nessun video richiesto per questo post.');

        return response()->json([
            'message' => 'OK',
            'data' => $mediaService->payload($media),
        ]);
    }
}
