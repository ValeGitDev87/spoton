<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\Message;
use App\Models\Post;
use App\Services\Chat\ConversationService;
use App\Services\Push\PushNotificationService;
use App\Support\Push\PushNotificationType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChatController extends Controller
{
    public function __construct(private readonly ConversationService $conversations) {}

    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $chats = Chat::query()
            ->with(['userOne', 'userTwo', 'latestMessage.sender'])
            ->withCount(['messages as unread_count' => fn (Builder $query) => $query
                ->where('sender_id', '!=', $userId)
                ->whereNull('read_at')
                ->whereRaw(
                    "messages.sent_at > COALESCE(CASE WHEN chats.user_one_id = ? THEN chats.user_one_cleared_at ELSE chats.user_two_cleared_at END, '1970-01-01 00:00:00')",
                    [$userId],
                )])
            ->where(function (Builder $query) use ($userId): void {
                $query
                    ->where(function (Builder $one) use ($userId): void {
                        $one->where('user_one_id', $userId)
                            ->where(function (Builder $visible): void {
                                $visible->whereNull('user_one_cleared_at')
                                    ->orWhereHas('messages', fn (Builder $messages) => $messages
                                        ->whereColumn('messages.sent_at', '>', 'chats.user_one_cleared_at'));
                            });
                    })
                    ->orWhere(function (Builder $two) use ($userId): void {
                        $two->where('user_two_id', $userId)
                            ->where(function (Builder $visible): void {
                                $visible->whereNull('user_two_cleared_at')
                                    ->orWhereHas('messages', fn (Builder $messages) => $messages
                                        ->whereColumn('messages.sent_at', '>', 'chats.user_two_cleared_at'));
                            });
                    });
            })
            ->latest('updated_at')
            ->paginate((int) $request->query('per_page', 25));

        return response()->json([
            'message' => 'OK',
            'data' => collect($chats->items())->map(fn (Chat $chat) => $this->conversations->chatPayload($chat, $request->user()))->values(),
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
            'post_id' => ['nullable', 'uuid', 'exists:posts,id'],
        ]);

        abort_if($data['user_id'] === $request->user()->id, 422, 'Non puoi aprire una chat con te stesso.');

        $post = isset($data['post_id']) ? Post::query()->findOrFail($data['post_id']) : null;

        if ($post) {
            abort_unless($post->author_id === $data['user_id'], 422, 'Il post non appartiene all\'utente indicato.');
        }

        $chat = $post
            ? $this->conversations->openForPost(
                $post,
                $request->user()->id,
                $data['user_id'],
                ['origin_post_id' => $post->id],
            )
            : $this->conversations->openDirect($request->user()->id, $data['user_id']);

        $chat->load(['userOne', 'userTwo', 'latestMessage.sender']);

        return response()->json([
            'message' => 'OK',
            'data' => $this->conversations->chatPayload($chat, $request->user()),
        ], 201);
    }

    public function messages(Request $request, Chat $chat): JsonResponse
    {
        abort_unless($chat->hasParticipant($request->user()->id), 403);
        $clearedAt = $chat->clearedAtFor($request->user()->id);

        $chat->messages()
            ->where('sender_id', '!=', $request->user()->id)
            ->whereNull('read_at')
            ->when($clearedAt, fn (Builder $query) => $query->where('sent_at', '>', $clearedAt))
            ->update(['read_at' => now()]);

        $messages = $chat->messages()
            ->with('sender')
            ->when($clearedAt, fn (Builder $query) => $query->where('sent_at', '>', $clearedAt))
            ->latest('sent_at')
            ->paginate((int) $request->query('per_page', 50));

        return response()->json([
            'message' => 'OK',
            'data' => collect($messages->items())
                ->reverse()
                ->map(fn (Message $message) => $this->conversations->messagePayload($message, $request->user(), $chat))
                ->values(),
            'meta' => [
                'current_page' => $messages->currentPage(),
                'last_page' => $messages->lastPage(),
                'per_page' => $messages->perPage(),
                'total' => $messages->total(),
            ],
        ]);
    }

    public function send(Request $request, Chat $chat, PushNotificationService $pushNotificationService): JsonResponse
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

        $recipient = $chat->user_one_id === $request->user()->id
            ? $chat->userTwo
            : $chat->userOne;
        $maskedGhostSender = $chat->shouldMaskIdentityOf($request->user()->id, $recipient);
        $pushData = [
            'type' => PushNotificationType::NEW_MESSAGE,
            'chat_id' => $chat->id,
            'message_id' => $message->id,
        ];

        if (! $maskedGhostSender) {
            $pushData['sender_id'] = $request->user()->id;
        }

        $pushNotificationService->sendToUser(
            $recipient,
            'Nuovo messaggio',
            ($maskedGhostSender ? 'Ghost' : $request->user()->display_name).' ti ha scritto su SpotOn.',
            $pushData,
        );

        return response()->json([
            'message' => 'OK',
            'data' => $this->conversations->messagePayload($message, $request->user(), $chat),
        ], 201);
    }

    public function revealIdentity(Request $request, Chat $chat, PushNotificationService $pushNotificationService): JsonResponse
    {
        abort_unless($chat->hasParticipant($request->user()->id), 403);
        abort_unless($chat->isGhost(), 422, 'Questa chat non e Ghost.');
        abort_unless($chat->ghost_owner_id === $request->user()->id, 403);

        $revealedNow = false;
        $chat = DB::transaction(function () use ($chat, &$revealedNow): Chat {
            $lockedChat = Chat::query()->lockForUpdate()->findOrFail($chat->id);

            if ($lockedChat->ghost_identity_revealed_at === null) {
                $lockedChat->update(['ghost_identity_revealed_at' => now()]);
                $revealedNow = true;
            }

            return $lockedChat->refresh();
        });

        if ($revealedNow) {
            $recipient = $chat->user_one_id === $request->user()->id
                ? $chat->userTwo
                : $chat->userOne;

            $pushNotificationService->sendToUser(
                $recipient,
                'Identita rivelata',
                'Ghost ha rivelato la sua identita.',
                [
                    'type' => PushNotificationType::GHOST_IDENTITY_REVEALED,
                    'chat_id' => $chat->id,
                ],
            );
        }

        return response()->json([
            'message' => 'OK',
            'data' => $this->conversations->chatPayload($chat, $request->user()),
        ]);
    }

    public function destroy(Request $request, Chat $chat): JsonResponse
    {
        abort_unless($chat->hasParticipant($request->user()->id), 403);
        $permanentlyDeleted = false;

        DB::transaction(function () use ($chat, $request, &$permanentlyDeleted): void {
            $lockedChat = Chat::query()->lockForUpdate()->findOrFail($chat->id);
            $clearedAt = now();
            $lockedChat->update([
                $lockedChat->clearedColumnFor($request->user()->id) => $clearedAt,
            ]);
            $lockedChat->refresh();
            $latestMessageAt = $lockedChat->messages()->max('sent_at');
            $oneCleared = $lockedChat->user_one_cleared_at;
            $twoCleared = $lockedChat->user_two_cleared_at;

            if (
                $oneCleared
                && $twoCleared
                && (
                    ! $latestMessageAt
                    || (
                        $oneCleared->greaterThanOrEqualTo($latestMessageAt)
                        && $twoCleared->greaterThanOrEqualTo($latestMessageAt)
                    )
                )
            ) {
                $lockedChat->delete();
                $permanentlyDeleted = true;
            }
        });

        return response()->json([
            'message' => 'Conversazione eliminata.',
            'data' => [
                'removed' => true,
                'permanently_deleted' => $permanentlyDeleted,
            ],
        ]);
    }
}
