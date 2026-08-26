<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\SerializesPosts;
use App\Http\Controllers\Api\Concerns\SerializesUsers;
use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Http\Requests\Profile\UpdatePublicProfileRequest;
use App\Models\Chat;
use App\Models\Favorite;
use App\Models\Like;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    use SerializesPosts, SerializesUsers;

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $request->user()->update($request->validated());

        return response()->json([
            'message' => 'Profilo aggiornato.',
            'data' => [
                'user' => $this->userPayload($request->user()->refresh()),
            ],
        ]);
    }

    public function updatePublicProfile(UpdatePublicProfileRequest $request): JsonResponse
    {
        $request->user()->update($request->validated());

        return response()->json([
            'message' => 'Visibilità del profilo aggiornata.',
            'data' => [
                'user' => $this->userPayload($request->user()->refresh()),
            ],
        ]);
    }

    public function searchUsers(Request $request): JsonResponse
    {
        $data = $request->validate([
            'query' => ['sometimes', 'nullable', 'string', 'max:120'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:30'],
        ]);
        $query = trim((string) ($data['query'] ?? ''));
        $perPage = (int) ($data['per_page'] ?? 20);
        $interactionScores = $this->interactionScoresQuery($request->user()->id);
        $users = User::query()
            ->leftJoinSub($interactionScores, 'interaction_scores', function ($join): void {
                $join->on('interaction_scores.target_user_id', '=', 'users.id');
            })
            ->select('users.*')
            ->selectRaw('COALESCE(interaction_scores.interaction_score, 0) AS interaction_score')
            ->where('users.id', '!=', $request->user()->id)
            ->where('users.is_admin', false)
            ->where('users.is_suspended', false)
            ->when($query !== '', fn (Builder $builder) => $builder
                ->whereRaw('LOWER(users.display_name) LIKE ?', ['%'.mb_strtolower($query).'%']))
            ->orderByDesc('interaction_score')
            ->orderBy('users.display_name')
            ->paginate($perPage);

        return response()->json([
            'message' => 'OK',
            'data' => collect($users->items())->map(fn (User $user) => [
                'id' => $user->id,
                'display_name' => $user->display_name,
                'avatar_color' => $user->avatar_color,
                'avatar_url' => $user->avatar_url,
            ])->values(),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
        ]);
    }

    public function publicProfile(Request $request, User $user): JsonResponse
    {
        abort_if($user->is_admin || $user->is_suspended, 404);

        $isFavorite = Favorite::query()
            ->where('owner_id', $request->user()->id)
            ->where('target_user_id', $user->id)
            ->exists();

        return response()->json([
            'message' => 'OK',
            'data' => $this->publicUserProfilePayload($user) + [
                'is_favorite' => $isFavorite,
            ],
        ]);
    }

    private function interactionScoresQuery(string $viewerId): QueryBuilder
    {
        $favorites = DB::table('favorites')
            ->selectRaw('target_user_id, 30 AS weight')
            ->where('owner_id', $viewerId)
            ->whereNotNull('target_user_id');
        $chatsAsFirstUser = DB::table('chats')
            ->selectRaw('user_two_id AS target_user_id, 10 AS weight')
            ->where('user_one_id', $viewerId);
        $chatsAsSecondUser = DB::table('chats')
            ->selectRaw('user_one_id AS target_user_id, 10 AS weight')
            ->where('user_two_id', $viewerId);
        $messagesAsFirstUser = DB::table('messages')
            ->join('chats', 'chats.id', '=', 'messages.chat_id')
            ->selectRaw('chats.user_two_id AS target_user_id, 1 AS weight')
            ->where('chats.user_one_id', $viewerId);
        $messagesAsSecondUser = DB::table('messages')
            ->join('chats', 'chats.id', '=', 'messages.chat_id')
            ->selectRaw('chats.user_one_id AS target_user_id, 1 AS weight')
            ->where('chats.user_two_id', $viewerId);
        $likes = DB::table('likes')
            ->join('posts', 'posts.id', '=', 'likes.post_id')
            ->selectRaw('posts.author_id AS target_user_id, 3 AS weight')
            ->where('likes.user_id', $viewerId)
            ->whereColumn('posts.author_id', '!=', 'likes.user_id');
        $comments = DB::table('comments')
            ->join('posts', 'posts.id', '=', 'comments.post_id')
            ->selectRaw('posts.author_id AS target_user_id, 4 AS weight')
            ->where('comments.author_id', $viewerId)
            ->whereColumn('posts.author_id', '!=', 'comments.author_id');

        $interactions = $favorites
            ->unionAll($chatsAsFirstUser)
            ->unionAll($chatsAsSecondUser)
            ->unionAll($messagesAsFirstUser)
            ->unionAll($messagesAsSecondUser)
            ->unionAll($likes)
            ->unionAll($comments);

        return DB::query()
            ->fromSub($interactions, 'interactions')
            ->select('target_user_id')
            ->selectRaw('SUM(weight) AS interaction_score')
            ->groupBy('target_user_id');
    }

    public function karma(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->query('per_page', 25), 1), 50);
        $likes = Like::query()
            ->with(['user', 'post.location'])
            ->whereHas('post', fn (Builder $query) => $query
                ->where('author_id', $request->user()->id))
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'message' => 'OK',
            'data' => [
                'karma' => $request->user()->karma ?? 0,
                'likes_received_count' => $likes->total(),
                'likes' => collect($likes->items())->map(fn (Like $like) => [
                    'id' => $like->id,
                    'user' => [
                        'id' => $like->user->id,
                        'display_name' => $like->user->display_name,
                        'avatar_color' => $like->user->avatar_color,
                        'avatar_url' => $like->user->avatar_url,
                    ],
                    'post' => [
                        'id' => $like->post->id,
                        'text' => $like->post->text,
                        'location' => $this->locationPayload($like->post->location),
                    ],
                    'created_at' => $like->created_at?->toISOString(),
                ])->values(),
            ],
            'meta' => [
                'current_page' => $likes->currentPage(),
                'last_page' => $likes->lastPage(),
                'per_page' => $likes->perPage(),
                'total' => $likes->total(),
            ],
        ]);
    }

    public function stats(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        return response()->json([
            'message' => 'OK',
            'data' => [
                'posts_count' => Post::query()
                    ->where('author_id', $userId)
                    ->where('status', '!=', 'removed')
                    ->count(),
                'chats_count' => Chat::query()
                    ->where(fn (Builder $query) => $query
                        ->where('user_one_id', $userId)
                        ->orWhere('user_two_id', $userId))
                    ->count(),
                'karma' => $request->user()->karma ?? 0,
                'favorites_count' => Favorite::query()
                    ->where('owner_id', $userId)
                    ->count(),
                'likes_received_count' => Like::query()
                    ->whereHas('post', fn (Builder $query) => $query
                        ->where('author_id', $userId))
                    ->count(),
            ],
        ]);
    }

    public function posts(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->query('per_page', 20), 1), 50);
        $posts = Post::query()
            ->with(['author', 'location', 'communityVotes'])
            ->where('author_id', $request->user()->id)
            ->where('status', '!=', 'removed')
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'message' => 'OK',
            'data' => collect($posts->items())
                ->map(fn (Post $post) => $this->postPayload($post, $request->user(), detail: true))
                ->values(),
            'meta' => [
                'current_page' => $posts->currentPage(),
                'last_page' => $posts->lastPage(),
                'per_page' => $posts->perPage(),
                'total' => $posts->total(),
            ],
        ]);
    }

    public function storePhoto(Request $request): JsonResponse
    {
        $data = $request->validate([
            'photo_url' => ['required_without:photo', 'nullable', 'url', 'max:2048'],
            'photo' => ['required_without:photo_url', 'nullable', 'image', 'max:4096'],
        ]);

        $user = $request->user();
        $photos = array_values($user->photos ?? []);

        if (count($photos) >= 10) {
            throw ValidationException::withMessages([
                'photo' => ['Limite di 10 foto raggiunto.'],
            ]);
        }

        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('profile-photos', 'public');
            $photos[] = Storage::disk('public')->url($path);
        } else {
            $photos[] = $data['photo_url'];
        }

        $user->update(['photos' => $photos]);

        return response()->json([
            'message' => 'OK',
            'data' => [
                'photos' => $this->photoPayload($photos),
            ],
        ], 201);
    }

    public function destroyPhoto(Request $request, int $photoId): JsonResponse
    {
        $user = $request->user();
        $photos = array_values($user->photos ?? []);

        abort_unless(array_key_exists($photoId, $photos), 404);

        $removedUrl = $photos[$photoId];
        unset($photos[$photoId]);
        $photos = array_values($photos);

        $this->deleteLocalPublicPhoto($removedUrl);
        $updates = ['photos' => $photos];
        $updates['public_photo_urls'] = array_values(array_intersect(
            $user->public_photo_urls ?? [],
            $photos,
        ));

        if ($user->avatar_url === $removedUrl) {
            $updates['avatar_url'] = null;
        }

        $user->update($updates);

        return response()->json([
            'message' => 'OK',
            'data' => [
                'removed' => true,
                'photos' => $this->photoPayload($photos),
            ],
        ]);
    }

    private function photoPayload(array $photos): array
    {
        return collect(array_values($photos))
            ->map(fn (string $url, int $index) => [
                'id' => $index,
                'url' => $url,
            ])
            ->values()
            ->all();
    }

    private function deleteLocalPublicPhoto(string $url): void
    {
        if (! str_starts_with($url, '/storage/')) {
            return;
        }

        Storage::disk('public')->delete(substr($url, strlen('/storage/')));
    }
}
