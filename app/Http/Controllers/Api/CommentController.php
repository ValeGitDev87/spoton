<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Favorite;
use App\Models\Post;
use App\Models\User;
use App\Services\Push\PushNotificationService;
use App\Support\Push\PushNotificationType;
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
        $this->sendCommentPush($request->user(), $post->refresh(), $comment);

        return response()->json([
            'message' => 'OK',
            'data' => $this->commentPayload($comment),
        ], 201);
    }

    private function resolveTaggedUserId(User $author, string $text): ?string
    {
        if (! str_contains($text, '@')) {
            return null;
        }

        $favorites = Favorite::query()
            ->where('owner_id', $author->id)
            ->get()
            ->sortByDesc(
                fn (Favorite $favorite): int => mb_strlen(trim($favorite->target_name))
            );

        foreach ($favorites as $favorite) {
            $targetName = trim($favorite->target_name);
            $escapedTargetName = preg_quote($targetName, '/');

            // Accetta punteggiatura dopo il nome e prova prima i nomi più lunghi.
            $pattern = '/@'.$escapedTargetName.'(?![\\p{L}\\p{N}_])/iu';

            if (preg_match($pattern, $text) !== 1) {
                continue;
            }

            $taggedUserId = User::query()
                ->whereRaw('LOWER(display_name) = ?', [mb_strtolower($targetName)])
                ->value('id');

            if ($taggedUserId) {
                return $taggedUserId;
            }
        }

        throw ValidationException::withMessages([
            'text' => ['Puoi taggare solo persone presenti nei tuoi preferiti.'],
        ]);
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

    private function sendCommentPush(User $author, Post $post, Comment $comment): void
    {
        if ($comment->taggedUser && $comment->tagged_user_id !== $author->id) {
            app(PushNotificationService::class)->sendToUser(
                $comment->taggedUser,
                'Ti hanno taggato su SpotOn',
                $author->display_name.' ti ha menzionato in un commento.',
                [
                    'type' => PushNotificationType::USER_MENTIONED,
                    'post_id' => $post->id,
                    'comment_id' => $comment->id,
                ],
            );

            return;
        }

        $post->loadMissing('author');

        if ($post->author_id === $author->id) {
            return;
        }

        app(PushNotificationService::class)->sendToUser(
            $post->author,
            'Nuovo commento su SpotOn',
            $author->display_name.' ha commentato il tuo annuncio.',
            [
                'type' => PushNotificationType::NEW_COMMENT,
                'post_id' => $post->id,
                'comment_id' => $comment->id,
            ],
        );
    }
}
