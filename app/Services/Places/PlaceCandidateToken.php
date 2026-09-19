<?php

namespace App\Services\Places;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\ValidationException;

class PlaceCandidateToken
{
    public function encode(array $candidate, string $userId): string
    {
        return Crypt::encryptString(json_encode([
            'candidate' => $candidate,
            'expires_at' => now()->addMinutes(10)->timestamp,
            'user_id' => $userId,
        ], JSON_THROW_ON_ERROR));
    }

    public function decode(string $token, string $userId): array
    {
        try {
            $payload = json_decode(Crypt::decryptString($token), true, flags: JSON_THROW_ON_ERROR);
        } catch (DecryptException|\JsonException) {
            throw ValidationException::withMessages([
                'candidate_token' => ['Il luogo proposto non e piu valido. Ripeti la ricerca.'],
            ]);
        }

        if (($payload['user_id'] ?? null) !== $userId
            || ! is_int($payload['expires_at'] ?? null)
            || $payload['expires_at'] < now()->timestamp
            || ! is_array($payload['candidate'] ?? null)) {
            throw ValidationException::withMessages([
                'candidate_token' => ['Il luogo proposto e scaduto. Ripeti la ricerca.'],
            ]);
        }

        return $payload['candidate'];
    }
}
