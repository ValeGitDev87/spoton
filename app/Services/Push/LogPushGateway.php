<?php

namespace App\Services\Push;

use App\Contracts\PushGateway;
use Illuminate\Support\Facades\Log;

class LogPushGateway implements PushGateway
{
    public function send(array $recipients, string $title, string $body, array $data = [], ?string $sound = 'default', ?string $channelId = null): array
    {
        Log::info('spoton.push.log', [
            'recipient_count' => count($recipients),
            'recipient_hashes' => collect($recipients)->pluck('token_hash')->map(fn (string $hash) => substr($hash, 0, 12))->all(),
            'title' => $title,
            'body' => $body,
            'data' => $data,
            'sound' => $sound,
            'channel_id' => $channelId,
        ]);

        return [
            'driver' => 'log',
            'sent' => count($recipients),
            'tickets' => [],
        ];
    }
}
