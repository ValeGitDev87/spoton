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
            'photos' => $user->photos ?? [],
            'karma' => $user->karma ?? 0,
            'auth_provider' => $user->auth_provider ?? 'email',
            'email_verified' => $user->hasVerifiedEmail(),
            'is_admin' => $user->is_admin,
            'is_suspended' => (bool) $user->is_suspended,
        ];
    }
}
