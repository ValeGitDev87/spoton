<?php

namespace App\Services\Push;

use App\Jobs\Push\SendExpoPushNotification;
use App\Models\UserNotification;
use App\Models\User;
use App\Support\Push\PushNotificationType;

class PushNotificationService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function sendToUser(User $user, string $title, string $body, array $data = []): int
    {
        if (($data['type'] ?? null) !== PushNotificationType::TEST) {
            UserNotification::query()->create([
                'user_id' => $user->id,
                'type' => (string) ($data['type'] ?? 'general'),
                'title' => $title,
                'body' => $body,
                'data' => $data,
            ]);
        }

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
