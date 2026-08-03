<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Models\User;

trait SerializesUsers
{
    private function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'email' => $user->email,
            'display_name' => $user->display_name,
            'avatar_color' => $user->avatar_color,
            'avatar_url' => $user->avatar_url,
            'bio' => $user->bio,
            'motto' => $user->motto,
            'favorite_song' => $user->favorite_song,
            'show_bio' => (bool) $user->show_bio,
            'show_motto' => (bool) $user->show_motto,
            'show_favorite_song' => (bool) $user->show_favorite_song,
            'photos' => $user->photos ?? [],
            'public_photo_urls' => $user->public_photo_urls ?? [],
            'karma' => $user->karma ?? 0,
            'auth_provider' => $user->auth_provider ?? 'email',
            'email_verified' => $user->hasVerifiedEmail(),
            'is_admin' => $user->is_admin,
            'is_suspended' => (bool) $user->is_suspended,
        ];
    }

    private function publicUserProfilePayload(User $user): array
    {
        $photos = array_values(array_intersect(
            $user->photos ?? [],
            $user->public_photo_urls ?? [],
        ));

        return [
            'id' => $user->id,
            'display_name' => $user->display_name,
            'avatar_color' => $user->avatar_color,
            'avatar_url' => $user->avatar_url,
            'bio' => $user->show_bio ? $user->bio : null,
            'motto' => $user->show_motto ? $user->motto : null,
            'favorite_song' => $user->show_favorite_song ? $user->favorite_song : null,
            'photos' => $photos,
        ];
    }
}
