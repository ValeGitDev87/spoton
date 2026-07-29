<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\SerializesPosts;
use App\Http\Controllers\Api\Concerns\SerializesUsers;
use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Models\Chat;
use App\Models\Favorite;
use App\Models\Like;
use App\Models\Post;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
            ->with(['author', 'location'])
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
