<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HiddenPost;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PostVisibilityController extends Controller
{
    public function store(Request $request, Post $post): JsonResponse
    {
        abort_if($post->author_id === $request->user()->id, 422, 'Non puoi nascondere un tuo post.');

        HiddenPost::query()->firstOrCreate([
            'user_id' => $request->user()->id,
            'post_id' => $post->id,
        ]);

        return response()->json([
            'message' => 'Post nascosto dal tuo feed.',
            'data' => ['post_id' => $post->id, 'hidden' => true],
        ]);
    }

    public function destroy(Request $request, Post $post): JsonResponse
    {
        HiddenPost::query()
            ->where('user_id', $request->user()->id)
            ->where('post_id', $post->id)
            ->delete();

        return response()->json([
            'message' => 'Post nuovamente visibile.',
            'data' => ['post_id' => $post->id, 'hidden' => false],
        ]);
    }
}
