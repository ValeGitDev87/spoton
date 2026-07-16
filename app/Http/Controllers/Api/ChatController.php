<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $chats = Chat::query()
            ->with(['userOne', 'userTwo', 'latestMessage.sender'])
            ->where(fn (Builder $query) => $query
                ->where('user_one_id', $request->user()->id)
                ->orWhere('user_two_id', $request->user()->id))
            ->latest('updated_at')
            ->paginate((int) $request->query('per_page', 25));

        return response()->json([
            'message' => 'OK',
            'data' => collect($chats->items())->map(fn (Chat $chat) => $this->chatPayload($chat, $request->user()))->values(),
            'meta' => [
                'current_page' => $chats->currentPage(),
                'last_page' => $chats->lastPage(),
                'per_page' => $chats->perPage(),
                'total' => $chats->total(),
            ],
        ]);
    }

    public function open(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'uuid', 'exists:users,id'],
        ]);

        abort_if($data['user_id'] === $request->user()->id, 422, 'Non puoi aprire una chat con te stesso.');

        [$one, $two] = Chat::sortedPair($request->user()->id, $data['user_id']);

        $chat = Chat::query()->firstOrCreate([
            'user_one_id' => $one,
            'user_two_id' => $two,
        ])->load(['userOne', 'userTwo', 'latestMessage.sender']);

        return response()->json([
            'message' => 'OK',
            'data' => $this->chatPayload($chat, $request->user()),
        ], 201);
    }

    public function messages(Request $request, Chat $chat): JsonResponse
    {
        abort_unless($chat->hasParticipant($request->user()->id), 403);

        $messages = $chat->messages()
            ->with('sender')
            ->oldest('sent_at')
            ->paginate((int) $request->query('per_page', 50));

        return response()->json([
            'message' => 'OK',
            'data' => collect($messages->items())->map(fn (Message $message) => $this->messagePayload($message))->values(),
            'meta' => [
                'current_page' => $messages->currentPage(),
                'last_page' => $messages->lastPage(),
                'per_page' => $messages->perPage(),
                'total' => $messages->total(),
            ],
        ]);
    }

    public function send(Request $request, Chat $chat): JsonResponse
    {
        abort_unless($chat->hasParticipant($request->user()->id), 403);

        $data = $request->validate([
            'text' => ['required', 'string', 'min:1', 'max:2000'],
        ]);

        $message = $chat->messages()->create([
            'sender_id' => $request->user()->id,
            'text' => $data['text'],
            'sent_at' => now(),
        ])->load('sender');

        $chat->touch();

        return response()->json([
            'message' => 'OK',
            'data' => $this->messagePayload($message),
        ], 201);
    }

    private function chatPayload(Chat $chat, User $viewer): array
    {
        $chat->loadMissing(['userOne', 'userTwo', 'latestMessage.sender']);
        $other = $chat->user_one_id === $viewer->id ? $chat->userTwo : $chat->userOne;
        $lastMessage = $chat->latestMessage;

        return [
            'id' => $chat->id,
            'participant' => [
                'id' => $other->id,
                'display_name' => $other->display_name,
                'avatar_color' => $other->avatar_color,
            ],
            'last_message' => $lastMessage ? $this->messagePayload($lastMessage) : null,
            'origin_challenge_id' => $chat->origin_challenge_id,
            'origin_post_id' => $chat->origin_post_id,
            'created_at' => $chat->created_at?->toISOString(),
            'updated_at' => $chat->updated_at?->toISOString(),
        ];
    }

    private function messagePayload(Message $message): array
    {
        $message->loadMissing('sender');

        return [
            'id' => $message->id,
            'chat_id' => $message->chat_id,
            'sender' => [
                'id' => $message->sender->id,
                'display_name' => $message->sender->display_name,
                'avatar_color' => $message->sender->avatar_color,
            ],
            'text' => $message->text,
            'sent_at' => $message->sent_at?->toISOString(),
            'read_at' => $message->read_at?->toISOString(),
        ];
    }
}
