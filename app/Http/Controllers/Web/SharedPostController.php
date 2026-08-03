<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Support\PostCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;

class SharedPostController extends Controller
{
    public function __invoke(Post $post): View
    {
        $post->loadMissing(['author', 'location']);
        $available = $post->isActive() && $post->location->is_active;
        $authorName = $post->is_anonymous ? 'Ghost' : $post->author->display_name;
        $description = $available
            ? Str::limit(preg_replace('/\s+/', ' ', trim($post->text)) ?: 'Un nuovo annuncio SpotOn.', 180)
            : 'Questo annuncio SpotOn non e piu disponibile.';
        $audioUrl = null;

        if ($available && $post->audio_url) {
            $audioUrl = Str::startsWith($post->audio_url, ['http://', 'https://'])
                ? $post->audio_url
                : asset(ltrim($post->audio_url, '/'));
        }

        return view('posts.share', [
            'appUrl' => "spoton://p/{$post->id}",
            'audioUrl' => $audioUrl,
            'authorName' => $authorName,
            'available' => $available,
            'canonicalUrl' => route('posts.share', $post),
            'description' => $description,
            'imageUrl' => asset('images/share/spoton-share.png'),
            'post' => $post,
            'categoryLabel' => PostCategory::label($post->category),
            'title' => $available ? "{$authorName} su SpotOn" : 'Annuncio non disponibile',
        ]);
    }
}
