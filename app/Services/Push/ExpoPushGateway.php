<?php

namespace App\Services\Push;

use App\Contracts\PushGateway;
use Illuminate\Support\Facades\Http;

class ExpoPushGateway implements PushGateway
{
    public function send(array $recipients, string $title, string $body, array $data = [], ?string $sound = 'default', ?string $channelId = null): array
    {
        if ($recipients === []) {
            return ['driver' => 'expo', 'sent' => 0, 'tickets' => []];
        }

        $messages = collect($recipients)->map(function (array $recipient) use ($title, $body, $data, $sound, $channelId): array {
            return array_filter([
                'to' => $recipient['token'],
                'title' => $title,
                'body' => $body,
                'data' => $data,
                'sound' => $sound,
                'channelId' => $channelId,
            ], fn ($value) => $value !== null);
        })->values()->all();

        $request = Http::acceptJson();
        $accessToken = config('services.expo.access_token');

        if ($accessToken) {
            $request = $request->withToken($accessToken);
        }

        $response = $request->post((string) config('services.expo.push_endpoint'), $messages);
        $response->throw();

        return [
            'driver' => 'expo',
            'sent' => count($messages),
            'tickets' => $response->json('data', []),
        ];
    }
}
