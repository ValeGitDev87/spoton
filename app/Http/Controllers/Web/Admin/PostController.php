<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PostController extends Controller
{
    public function index(Request $request): View
    {
        $posts = Post::query()
            ->with(['author', 'location'])
            ->when($request->query('search'), function ($query, string $search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner
                        ->where('text', 'like', "%{$search}%")
                        ->orWhere('musica', 'like', "%{$search}%")
                        ->orWhere('song_quote', 'like', "%{$search}%")
                        ->orWhereHas('author', fn ($author) => $author->where('display_name', 'like', "%{$search}%"))
                        ->orWhereHas('location', fn ($location) => $location->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($request->query('status'), fn ($query, string $status) => $query->where('status', $status))
            ->when($request->query('location_id'), fn ($query, string $locationId) => $query->where('location_id', $locationId))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.posts.index', [
            'posts' => $posts,
            'search' => $request->query('search', ''),
            'status' => $request->query('status', ''),
        ]);
    }

    public function updateStatus(Request $request, Post $post): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:active,expired,removed,flagged'],
        ]);

        $post->update(['status' => $data['status']]);

        return back()->with('status', 'Stato post aggiornato.');
    }
}
