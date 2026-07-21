<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\SerializesPosts;
use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StoryController extends Controller
{
    use SerializesPosts;

    public function index(Request $request, Location $location): JsonResponse
    {
        $posts = Post::query()
            ->with(['author', 'location'])
            ->where('location_id', $location->id)
            ->where('status', 'active')
            ->where('created_at', '>', now()->subDay())
            ->where('expires_at', '>', now())
            ->oldest()
            ->get()
            ->map(fn (Post $post) => $this->postPayload($post, $request->user()))
            ->values();

        $location->setAttribute('active_stories_count', $posts->count());

        return response()->json([
            'message' => 'OK',
            'data' => [
                'location' => $this->locationPayload($location),
                'stories' => $posts,
            ],
        ]);
    }
}
