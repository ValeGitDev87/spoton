<?php

namespace App\Contracts;

interface PushGateway
{
    /**
     * @param  array<int, array{token: string, token_hash: string}>  $recipients
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function send(array $recipients, string $title, string $body, array $data = [], ?string $sound = 'default', ?string $channelId = null): array;
}
