<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Models\Post;
use App\Models\User;

trait SerializesPosts
{
    use SerializesLocations;

    private function postPayload(Post $post, User $viewer, ?float $distanceKm = null, bool $detail = false): array
    {
        $post->loadMissing(['author', 'location']);
        $isOwner = $post->author_id === $viewer->id;
        $canSeeAuthor = ! $post->is_anonymous || $isOwner || $viewer->is_admin;

        $payload = [
            'id' => $post->id,
            'author' => $canSeeAuthor ? [
                'id' => $post->author->id,
                'display_name' => $post->author->display_name,
                'avatar_color' => $post->author->avatar_color,
                'avatar_url' => $post->author->avatar_url,
                'is_anonymous' => false,
            ] : [
                'id' => null,
                'display_name' => 'Ghost',
                'avatar_color' => null,
                'avatar_url' => null,
                'is_anonymous' => true,
            ],
            'location' => $this->locationPayload($post->location),
            'text' => $post->text,
            'musica' => $post->musica,
            'song_quote' => $post->song_quote ?? $post->musica,
            'sighting_date' => $post->sighting_date->toDateString(),
            'is_anonymous' => $post->is_anonymous,
            'secret_question' => $post->secret_question,
            'has_secret_answer' => (bool) $post->secret_answer_hash,
            'created_at' => $post->created_at?->toISOString(),
            'expires_at' => $post->expires_at->toISOString(),
            'like_count' => $post->like_count ?? 0,
            'comment_count' => $post->comment_count ?? 0,
            'share_count' => $post->share_count ?? 0,
            'io_cero_count' => $post->io_cero_count ?? 0,
            'spot_on_count' => $post->spot_on_count ?? $post->io_cero_count ?? 0,
            'liked_by_me' => $post->likes()
                ->where('user_id', $viewer->id)
                ->exists(),
            'io_cero_by_me' => $post->iWasThere()
                ->where('user_id', $viewer->id)
                ->exists(),
            'is_owner' => $isOwner,
            'status' => $post->status,
            'is_active' => $post->isActive(),
        ];

        if ($distanceKm !== null) {
            $payload['distance_km'] = round($distanceKm, 2);
        }

        if ($detail && $payload['is_owner']) {
            $payload['owner_private'] = [
                'io_cero_users_count' => $post->io_cero_count ?? 0,
                'secret_answer_configured' => (bool) $post->secret_answer_hash,
            ];
        }

        return $payload;
    }
}
