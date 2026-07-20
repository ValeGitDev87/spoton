<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PushToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PushTokenController extends Controller
{
    public function upsert(Request $request, string $deviceId): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'max:512', 'regex:/^ExponentPushToken\\[[A-Za-z0-9_\\-]+\\]$/'],
            'platform' => ['required', Rule::in(['ios', 'android'])],
            'app_version' => ['nullable', 'string', 'max:50'],
            'locale' => ['nullable', 'string', 'max:20'],
            'timezone' => ['nullable', 'string', 'max:80'],
        ]);

        $tokenHash = PushToken::hashToken($data['token']);

        PushToken::query()
            ->where('token_hash', $tokenHash)
            ->where('user_id', '!=', $request->user()->id)
            ->update([
                'is_active' => false,
                'revoked_at' => now(),
            ]);

        PushToken::query()
            ->where('user_id', $request->user()->id)
            ->where('device_id', $deviceId)
            ->where('token_hash', '!=', $tokenHash)
            ->update([
                'is_active' => false,
                'revoked_at' => now(),
            ]);

        $pushToken = PushToken::query()->updateOrCreate(
            ['token_hash' => $tokenHash],
            [
                'user_id' => $request->user()->id,
                'token' => $data['token'],
                'device_id' => $deviceId,
                'platform' => $data['platform'],
                'app_version' => $data['app_version'] ?? null,
                'locale' => $data['locale'] ?? null,
                'timezone' => $data['timezone'] ?? null,
                'is_active' => true,
                'last_seen_at' => now(),
                'revoked_at' => null,
            ],
        );

        return response()->json([
            'message' => 'OK',
            'data' => $this->payload($pushToken),
        ]);
    }

    public function destroy(Request $request, string $deviceId): JsonResponse
    {
        $updated = PushToken::query()
            ->where('user_id', $request->user()->id)
            ->where('device_id', $deviceId)
            ->update([
                'is_active' => false,
                'revoked_at' => now(),
            ]);

        return response()->json([
            'message' => 'OK',
            'data' => [
                'revoked' => $updated > 0,
            ],
        ]);
    }

    private function payload(PushToken $pushToken): array
    {
        return [
            'id' => $pushToken->id,
            'device_id' => $pushToken->device_id,
            'platform' => $pushToken->platform,
            'app_version' => $pushToken->app_version,
            'locale' => $pushToken->locale,
            'timezone' => $pushToken->timezone,
            'is_active' => $pushToken->is_active,
            'last_seen_at' => $pushToken->last_seen_at?->toISOString(),
            'revoked_at' => $pushToken->revoked_at?->toISOString(),
            'token_hash' => substr($pushToken->token_hash, 0, 12),
        ];
    }
}
