<?php

namespace App\Services\Chat;

use App\Models\Chat;
use App\Models\Message;
use App\Models\Post;
use App\Models\User;

class ConversationService
{
    /**
     * @param  array<string, string|null>  $origin
     */
    public function openDirect(string $firstUserId, string $secondUserId, array $origin = []): Chat
    {
        [$one, $two] = Chat::sortedPair($firstUserId, $secondUserId);
        $key = Chat::directConversationKey($one, $two);

        $chat = Chat::query()->firstOrCreate(
            ['conversation_key' => $key],
            [
                'context_type' => Chat::CONTEXT_DIRECT,
                'user_one_id' => $one,
                'user_two_id' => $two,
            ],
        );

        return $this->applyOrigin($chat, $origin);
    }

    /**
     * @param  array<string, string|null>  $origin
     */
    public function openForPost(Post $post, string $firstUserId, string $secondUserId, array $origin = []): Chat
    {
        if ($post->is_anonymous && in_array($post->author_id, [$firstUserId, $secondUserId], true)) {
            return $this->openGhostPost($post, $firstUserId, $secondUserId, $origin);
        }

        return $this->openDirect($firstUserId, $secondUserId, $origin);
    }

    /**
     * @param  array<string, string|null>  $origin
     */
    public function openGhostPost(Post $post, string $firstUserId, string $secondUserId, array $origin = []): Chat
    {
        [$one, $two] = Chat::sortedPair($firstUserId, $secondUserId);
        $key = Chat::ghostPostConversationKey($post->id, $one, $two);

        $chat = Chat::query()->firstOrCreate(
            ['conversation_key' => $key],
            [
                'context_type' => Chat::CONTEXT_GHOST_POST,
                'user_one_id' => $one,
                'user_two_id' => $two,
                'origin_post_id' => $post->id,
                'ghost_owner_id' => $post->author_id,
            ],
        );

        return $this->applyOrigin($chat, ['origin_post_id' => $post->id] + $origin);
    }

    public function chatPayload(Chat $chat, User $viewer): array
    {
        $chat->loadMissing(['userOne', 'userTwo', 'latestMessage.sender']);
        $other = $chat->user_one_id === $viewer->id ? $chat->userTwo : $chat->userOne;
        $lastMessage = $chat->latestMessage;
        $clearedAt = $chat->clearedAtFor($viewer->id);

        if ($lastMessage && $clearedAt && $lastMessage->sent_at->lessThanOrEqualTo($clearedAt)) {
            $lastMessage = null;
        }

        return [
            'id' => $chat->id,
            'context_type' => $chat->context_type,
            'is_ghost' => $chat->isGhost(),
            'identity_revealed' => $chat->identityRevealed(),
            'can_reveal_identity' => $chat->isGhost()
                && ! $chat->identityRevealed()
                && $chat->ghost_owner_id === $viewer->id,
            'participant' => $this->userPayload(
                $other,
                $chat->shouldMaskIdentityOf($other->id, $viewer),
            ),
            'last_message' => $lastMessage ? $this->messagePayload($lastMessage, $viewer, $chat) : null,
            'origin_challenge_id' => $chat->origin_challenge_id,
            'origin_post_id' => $chat->origin_post_id,
            'unread_count' => (int) ($chat->unread_count ?? 0),
            'created_at' => $chat->created_at?->toISOString(),
            'updated_at' => $chat->updated_at?->toISOString(),
        ];
    }

    public function messagePayload(Message $message, User $viewer, ?Chat $chat = null): array
    {
        $message->loadMissing('sender');
        $chat ??= $message->chat()->firstOrFail();

        return [
            'id' => $message->id,
            'chat_id' => $message->chat_id,
            'sender' => $this->userPayload(
                $message->sender,
                $chat->shouldMaskIdentityOf($message->sender_id, $viewer),
            ),
            'text' => $message->text,
            'sent_at' => $message->sent_at?->toISOString(),
            'read_at' => $message->read_at?->toISOString(),
        ];
    }

    /**
     * @param  array<string, string|null>  $origin
     */
    private function applyOrigin(Chat $chat, array $origin): Chat
    {
        $allowed = array_intersect_key($origin, array_flip([
            'origin_challenge_id',
            'origin_post_id',
        ]));
        $values = array_filter($allowed, fn (?string $value): bool => $value !== null && $value !== '');

        if ($values !== []) {
            $chat->fill($values)->save();
        }

        return $chat->refresh();
    }

    private function userPayload(User $user, bool $masked): array
    {
        if ($masked) {
            return [
                'id' => null,
                'display_name' => 'Ghost',
                'avatar_color' => null,
                'avatar_url' => null,
                'is_ghost' => true,
            ];
        }

        return [
            'id' => $user->id,
            'display_name' => $user->display_name,
            'avatar_color' => $user->avatar_color,
            'avatar_url' => $user->avatar_url,
            'is_ghost' => false,
        ];
    }
}
