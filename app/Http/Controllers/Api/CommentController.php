<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Favorite;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CommentController extends Controller
{
    public function index(Request $request, Post $post): JsonResponse
    {
        $comments = $post->comments()
            ->with(['author', 'taggedUser'])
            ->oldest()
            ->paginate((int) $request->query('per_page', 50));

        return response()->json([
            'message' => 'OK',
            'data' => collect($comments->items())->map(fn (Comment $comment) => $this->commentPayload($comment))->values(),
            'meta' => [
                'current_page' => $comments->currentPage(),
                'last_page' => $comments->lastPage(),
                'per_page' => $comments->perPage(),
                'total' => $comments->total(),
            ],
        ]);
    }

    public function store(Request $request, Post $post): JsonResponse
    {
        $data = $request->validate([
            'text' => ['required', 'string', 'min:1', 'max:1000'],
        ]);

        $taggedUserId = $this->resolveTaggedUserId($request->user(), $data['text']);

        $comment = DB::transaction(function () use ($request, $post, $data, $taggedUserId): Comment {
            $comment = $post->comments()->create([
                'author_id' => $request->user()->id,
                'tagged_user_id' => $taggedUserId,
                'text' => $data['text'],
            ]);

            $post->update(['comment_count' => $post->comments()->count()]);

            return $comment;
        });

        $comment->load(['author', 'taggedUser']);

        return response()->json([
            'message' => 'OK',
            'data' => $this->commentPayload($comment),
        ], 201);
    }

    private function resolveTaggedUserId(User $author, string $text): ?string
    {
        if (! preg_match('/@([^\s@]+)/u', $text, $matches)) {
            return null;
        }

        $targetName = trim($matches[1]);
        $normalizedTargetName = mb_strtolower($targetName);

        $favorite = Favorite::query()
            ->where('owner_id', $author->id)
            ->where(function ($query) use ($normalizedTargetName): void {
                $query
                    ->whereRaw('LOWER(target_name) = ?', [$normalizedTargetName])
                    ->orWhereRaw('LOWER(target_name) LIKE ?', [$normalizedTargetName.' %']);
            })
            ->first();

        if (! $favorite) {
            throw ValidationException::withMessages([
                'text' => ['Puoi taggare solo persone presenti nei tuoi preferiti.'],
            ]);
        }

        return User::query()
            ->whereRaw('LOWER(display_name) = ?', [mb_strtolower($favorite->target_name)])
            ->value('id');
    }

    private function commentPayload(Comment $comment): array
    {
        return [
            'id' => $comment->id,
            'post_id' => $comment->post_id,
            'author' => [
                'id' => $comment->author->id,
                'display_name' => $comment->author->display_name,
                'avatar_color' => $comment->author->avatar_color,
                'avatar_url' => $comment->author->avatar_url,
            ],
            'tagged_user' => $comment->taggedUser ? [
                'id' => $comment->taggedUser->id,
                'display_name' => $comment->taggedUser->display_name,
                'avatar_color' => $comment->taggedUser->avatar_color,
                'avatar_url' => $comment->taggedUser->avatar_url,
            ] : null,
            'text' => $comment->text,
            'created_at' => $comment->created_at?->toISOString(),
        ];
    }
}
