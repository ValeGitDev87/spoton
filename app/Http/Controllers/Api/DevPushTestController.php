<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Push\PushNotificationService;
use App\Support\Push\PushNotificationType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DevPushTestController extends Controller
{
    public function __invoke(Request $request, PushNotificationService $pushNotificationService): JsonResponse
    {
        abort_if(app()->environment('production'), 404);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'body' => ['required', 'string', 'max:240'],
            'data' => ['nullable', 'array'],
        ]);

        $payload = array_merge([
            'type' => PushNotificationType::TEST,
            'route' => 'dev-push-test',
        ], $data['data'] ?? []);

        $recipients = $pushNotificationService->sendToUser(
            $request->user(),
            $data['title'],
            $data['body'],
            $payload,
        );

        return response()->json([
            'message' => 'OK',
            'data' => [
                'queued' => true,
                'recipients' => $recipients,
            ],
        ]);
    }
}
