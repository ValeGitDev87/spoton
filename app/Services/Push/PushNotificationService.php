<?php

namespace App\Services\Push;

use App\Jobs\Push\SendExpoPushNotification;
use App\Models\User;

class PushNotificationService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function sendToUser(User $user, string $title, string $body, array $data = []): int
    {
        $tokenIds = $user->pushTokens()
            ->where('is_active', true)
            ->pluck('id')
            ->all();

        if ($tokenIds === []) {
            return 0;
        }

        SendExpoPushNotification::dispatch($tokenIds, $title, $body, $data);

        return count($tokenIds);
    }
}
