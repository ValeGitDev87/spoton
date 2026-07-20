<?php

namespace App\Jobs\Push;

use App\Contracts\PushGateway;
use App\Models\PushToken;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable as FoundationQueueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendExpoPushNotification implements ShouldQueue
{
    use FoundationQueueable;

    public int $tries = 3;

    public int $timeout = 30;

    /**
     * @param  array<int, string>  $pushTokenIds
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        private readonly array $pushTokenIds,
        private readonly string $title,
        private readonly string $body,
        private readonly array $data = [],
        private readonly ?string $sound = 'default',
        private readonly ?string $channelId = null,
    ) {
        $this->onQueue('notifications');
        $this->afterCommit();
    }

    public function backoff(): array
    {
        return [10, 60, 300];
    }

    public function handle(PushGateway $gateway): void
    {
        $tokens = PushToken::query()
            ->whereIn('id', $this->pushTokenIds)
            ->where('is_active', true)
            ->get();

        $recipients = $tokens
            ->map(fn (PushToken $token) => [
                'token' => $token->token,
                'token_hash' => $token->token_hash,
            ])
            ->values()
            ->all();

        $result = $gateway->send($recipients, $this->title, $this->body, $this->data, $this->sound, $this->channelId);

        $this->deactivateDeviceNotRegisteredTokens($tokens, $result['tickets'] ?? []);
    }

    public function failed(?Throwable $exception): void
    {
        Log::warning('spoton.push.failed', [
            'token_count' => count($this->pushTokenIds),
            'title' => $this->title,
            'error' => $exception?->getMessage(),
        ]);
    }

    private function deactivateDeviceNotRegisteredTokens($tokens, array $tickets): void
    {
        foreach ($tickets as $index => $ticket) {
            $error = data_get($ticket, 'details.error');

            if ($error !== 'DeviceNotRegistered') {
                continue;
            }

            $token = $tokens->values()->get($index);

            if ($token) {
                $token->update([
                    'is_active' => false,
                    'revoked_at' => now(),
                ]);
            }
        }
    }
}
