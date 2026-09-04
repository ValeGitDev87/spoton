<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Services\Media\PostSocialCardService;
use App\Services\Media\PublicPostMediaContent;
use App\Support\PostCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;

class SharedPostController extends Controller
{
    public function __invoke(
        Post $post,
        PostSocialCardService $socialCards,
        PublicPostMediaContent $publicContent,
    ): View {
        $post->loadMissing(['author', 'location']);
        $available = $post->isActive() && $post->location->is_active;
        $authorName = $post->is_anonymous ? 'Ghost' : $post->author->display_name;
        $description = $available
            ? Str::limit(preg_replace('/\s+/', ' ', trim($publicContent->text($post))) ?: 'Un nuovo annuncio SpotOn.', 180)
            : 'Questo annuncio SpotOn non e piu disponibile.';
        $audioUrl = null;

        if ($available && $post->audio_url) {
            $audioUrl = Str::startsWith($post->audio_url, ['http://', 'https://'])
                ? $post->audio_url
                : asset(ltrim($post->audio_url, '/'));
        }

        $imageUrl = $available ? $socialCards->urlFor($post) : asset('images/share/spoton-share.png');
        $imageUrl = Str::startsWith($imageUrl, ['http://', 'https://'])
            ? $imageUrl
            : asset(ltrim($imageUrl, '/'));

        return view('posts.share', [
            'appUrl' => "spoton://p/{$post->id}",
            'audioUrl' => $audioUrl,
            'authorName' => $authorName,
            'available' => $available,
            'canonicalUrl' => route('posts.share', $post),
            'description' => $description,
            'imageUrl' => $imageUrl,
            'locationAppUrl' => "spoton://l/{$post->location_id}",
            'locationUrl' => route('locations.public', $post->location),
            'post' => $post,
            'categoryLabel' => PostCategory::label($post->category),
            'title' => $available
                ? ($audioUrl ? "Nota audio di {$authorName} da {$post->location->name}" : "{$authorName} su SpotOn")
                : 'Annuncio non disponibile',
        ]);
    }
}
