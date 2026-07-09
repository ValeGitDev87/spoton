<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\SerializesPosts;
use App\Http\Controllers\Controller;
use App\Http\Requests\Post\NearbyPostsRequest;
use App\Http\Requests\Post\StorePostRequest;
use App\Http\Requests\Post\UpdatePostRequest;
use App\Models\Post;
use App\Services\GeoDistance;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class PostController extends Controller
{
    use SerializesPosts;

    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->query('per_page', 15), 50);

        $posts = Post::query()
            ->with(['author', 'location'])
            ->when(! $request->query('status'), fn (Builder $query) => $query->where('status', 'active'))
            ->when($request->query('status'), fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($request->query('location_id'), fn (Builder $query, string $locationId) => $query->where('location_id', $locationId))
            ->when($request->query('search'), function (Builder $query, string $search): void {
                $query->where(function (Builder $inner) use ($search): void {
                    $inner
                        ->where('text', 'like', "%{$search}%")
                        ->orWhere('musica', 'like', "%{$search}%")
                        ->orWhereHas('location', fn (Builder $location) => $location
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('city', 'like', "%{$search}%"))
                        ->orWhereHas('author', fn (Builder $author) => $author
                            ->where('display_name', 'like', "%{$search}%"));
                });
            })
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'message' => 'OK',
            'data' => collect($posts->items())
                ->map(fn (Post $post) => $this->postPayload($post, $request->user()))
                ->values(),
            'meta' => [
                'current_page' => $posts->currentPage(),
                'last_page' => $posts->lastPage(),
                'per_page' => $posts->perPage(),
                'total' => $posts->total(),
            ],
        ]);
    }

    public function nearby(NearbyPostsRequest $request): JsonResponse
    {
        $lat = (float) $request->validated('lat');
        $lng = (float) $request->validated('lng');
        $radiusKm = (float) ($request->validated('radius_km') ?? 200);

        $posts = $this->nearbyPosts($request, $lat, $lng, $radiusKm);

        return response()->json([
            'message' => 'OK',
            'data' => [
                'origin' => [
                    'lat' => $lat,
                    'lng' => $lng,
                ],
                'radius_km' => $radiusKm,
                'posts' => $posts,
            ],
        ]);
    }

    public function store(StorePostRequest $request): JsonResponse
    {
        $post = Post::query()->create([
            ...$request->validated(),
            'author_id' => $request->user()->id,
            'expires_at' => now()->addHours(24),
            'status' => 'active',
        ]);

        $post->refresh()->load(['author', 'location']);

        return response()->json([
            'message' => 'OK',
            'data' => $this->postPayload($post, $request->user(), detail: true),
        ], 201);
    }

    public function show(Request $request, Post $post): JsonResponse
    {
        return response()->json([
            'message' => 'OK',
            'data' => $this->postPayload($post, $request->user(), detail: true),
        ]);
    }

    public function update(UpdatePostRequest $request, Post $post): JsonResponse
    {
        abort_unless($post->author_id === $request->user()->id || $request->user()->is_admin, 403);

        $post->update($request->validated());

        return response()->json([
            'message' => 'OK',
            'data' => $this->postPayload($post->refresh(), $request->user(), detail: true),
        ]);
    }

    public function destroy(Request $request, Post $post): JsonResponse
    {
        abort_unless($post->author_id === $request->user()->id || $request->user()->is_admin, 403);

        $post->update(['status' => 'removed']);

        return response()->json([
            'message' => 'OK',
            'data' => [
                'removed' => true,
            ],
        ]);
    }

    public function nearbyPosts(Request $request, float $lat, float $lng, float $radiusKm): array
    {
        return Post::query()
            ->with(['author', 'location'])
            ->where('status', 'active')
            ->where('expires_at', '>', Carbon::now())
            ->when($request->query('location_id'), fn (Builder $query, string $locationId) => $query->where('location_id', $locationId))
            ->when($request->query('search'), function (Builder $query, string $search): void {
                $query->where(function (Builder $inner) use ($search): void {
                    $inner
                        ->where('text', 'like', "%{$search}%")
                        ->orWhere('musica', 'like', "%{$search}%");
                });
            })
            ->get()
            ->map(function (Post $post) use ($request, $lat, $lng): array {
                $distanceKm = GeoDistance::kilometers(
                    $lat,
                    $lng,
                    (float) $post->location->latitude,
                    (float) $post->location->longitude,
                );

                return $this->postPayload($post, $request->user(), $distanceKm);
            })
            ->filter(fn (array $post) => $post['distance_km'] <= $radiusKm)
            ->sortBy([
                ['distance_km', 'asc'],
                ['created_at', 'desc'],
            ])
            ->values()
            ->all();
    }
}
