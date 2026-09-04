<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Support\LocationIcon;
use App\Support\PostCategory;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class PublicLocationController extends Controller
{
    public function __invoke(Location $location): View
    {
        abort_unless($location->isPubliclyVisible(), 404);

        $cacheKey = 'public-location:'.$location->id.':'.($location->updated_at?->getTimestamp() ?: 0);
        $publicLocation = Cache::remember(
            $cacheKey,
            (int) config('spoton.public_cache_ttl_seconds', 300),
            fn (): array => [
                'id' => $location->id,
                'name' => $location->name,
                'short' => $location->short,
                'city' => $location->city,
                'type' => $location->type,
                'icon' => $location->icon ?: LocationIcon::DEFAULT,
            ],
        );
        $posts = $location->posts()
            ->with('author')
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn ($post): array => [
                'id' => $post->id,
                'author' => $post->is_anonymous ? 'Ghost' : $post->author?->display_name,
                'text' => $post->text,
                'category' => PostCategory::label($post->category),
                'has_audio' => (bool) $post->audio_path,
                'created_at' => $post->created_at,
            ]);

        return view('locations.public', [
            'appUrl' => "spoton://l/{$location->id}",
            'canonicalUrl' => route('locations.public', $location),
            'location' => $publicLocation,
            'posts' => $posts,
        ]);
    }
}
