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

        $payload = [
            'id' => $post->id,
            'author' => [
                'id' => $post->author->id,
                'display_name' => $post->author->display_name,
                'avatar_color' => $post->author->avatar_color,
            ],
            'location' => $this->locationPayload($post->location),
            'text' => $post->text,
            'musica' => $post->musica,
            'sighting_date' => $post->sighting_date->toDateString(),
            'created_at' => $post->created_at?->toISOString(),
            'expires_at' => $post->expires_at->toISOString(),
            'like_count' => $post->like_count ?? 0,
            'comment_count' => $post->comment_count ?? 0,
            'share_count' => $post->share_count ?? 0,
            'io_cero_count' => $post->io_cero_count ?? 0,
            'liked_by_me' => $post->likes()
                ->where('user_id', $viewer->id)
                ->exists(),
            'io_cero_by_me' => $post->iWasThere()
                ->where('user_id', $viewer->id)
                ->exists(),
            'is_owner' => $post->author_id === $viewer->id,
            'status' => $post->status,
            'is_active' => $post->isActive(),
        ];

        if ($distanceKm !== null) {
            $payload['distance_km'] = round($distanceKm, 2);
        }

        if ($detail && $payload['is_owner']) {
            $payload['owner_private'] = [
                'io_cero_users_count' => $post->io_cero_count ?? 0,
            ];
        }

        return $payload;
    }
}
