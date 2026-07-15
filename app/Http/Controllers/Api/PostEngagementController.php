<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Like;
use App\Models\Post;
use App\Models\PostIWasThere;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PostEngagementController extends Controller
{
    public function toggleLike(Request $request, Post $post): JsonResponse
    {
        $liked = DB::transaction(function () use ($request, $post): bool {
            $lockedPost = Post::query()->lockForUpdate()->findOrFail($post->id);

            $like = Like::query()
                ->where('post_id', $lockedPost->id)
                ->where('user_id', $request->user()->id)
                ->first();

            if ($like) {
                $like->delete();
                $lockedPost->update(['like_count' => max(0, $lockedPost->likes()->count())]);

                return false;
            }

            Like::query()->create([
                'post_id' => $lockedPost->id,
                'user_id' => $request->user()->id,
            ]);

            $lockedPost->update(['like_count' => $lockedPost->likes()->count()]);

            return true;
        });

        $post->refresh();

        return response()->json([
            'message' => 'OK',
            'data' => [
                'liked' => $liked,
                'like_count' => $post->like_count,
            ],
        ]);
    }

    public function toggleIoCero(Request $request, Post $post): JsonResponse
    {
        abort_if($post->author_id === $request->user()->id, 403, 'Il proprietario non puo usare Io c\'ero sul proprio post.');

        $ioCero = DB::transaction(function () use ($request, $post): bool {
            $lockedPost = Post::query()->lockForUpdate()->findOrFail($post->id);

            $record = PostIWasThere::query()
                ->where('post_id', $lockedPost->id)
                ->where('user_id', $request->user()->id)
                ->first();

            if ($record) {
                $record->delete();
                $count = max(0, $lockedPost->iWasThere()->count());
                $lockedPost->update([
                    'io_cero_count' => $count,
                    'spot_on_count' => $count,
                ]);

                return false;
            }

            PostIWasThere::query()->create([
                'post_id' => $lockedPost->id,
                'user_id' => $request->user()->id,
            ]);

            $count = $lockedPost->iWasThere()->count();
            $lockedPost->update([
                'io_cero_count' => $count,
                'spot_on_count' => $count,
            ]);

            return true;
        });

        $post->refresh();

        return response()->json([
            'message' => 'OK',
            'data' => [
                'io_cero' => $ioCero,
                'io_cero_count' => $post->io_cero_count,
                'spot_on_count' => $post->spot_on_count,
            ],
        ]);
    }

    public function ioCeroUsers(Request $request, Post $post): JsonResponse
    {
        abort_unless($post->author_id === $request->user()->id || $request->user()->is_admin, 403);

        $users = $post->iWasThere()
            ->with('user')
            ->latest()
            ->paginate((int) $request->query('per_page', 25));

        return response()->json([
            'message' => 'OK',
            'data' => collect($users->items())->map(fn (PostIWasThere $record) => [
                'id' => $record->user->id,
                'display_name' => $record->user->display_name,
                'avatar_color' => $record->user->avatar_color,
                'created_at' => $record->created_at?->toISOString(),
            ])->values(),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
        ]);
    }
}
