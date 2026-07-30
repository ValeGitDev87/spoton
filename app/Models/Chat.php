<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Chat extends Model
{
    use HasFactory, HasUuids;

    public const CONTEXT_DIRECT = 'direct';

    public const CONTEXT_GHOST_POST = 'ghost_post';

    protected $fillable = [
        'conversation_key',
        'context_type',
        'user_one_id',
        'user_two_id',
        'origin_challenge_id',
        'origin_post_id',
        'ghost_owner_id',
        'ghost_identity_revealed_at',
    ];

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function booted(): void
    {
        static::creating(function (Chat $chat): void {
            [$one, $two] = self::sortedPair($chat->user_one_id, $chat->user_two_id);

            $chat->user_one_id = $one;
            $chat->user_two_id = $two;
            $chat->context_type ??= self::CONTEXT_DIRECT;

            if (! $chat->conversation_key) {
                $chat->conversation_key = $chat->context_type === self::CONTEXT_GHOST_POST
                    ? self::ghostPostConversationKey((string) $chat->origin_post_id, $one, $two)
                    : self::directConversationKey($one, $two);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'ghost_identity_revealed_at' => 'datetime',
        ];
    }

    public static function sortedPair(string $firstUserId, string $secondUserId): array
    {
        return strcmp($firstUserId, $secondUserId) < 0
            ? [$firstUserId, $secondUserId]
            : [$secondUserId, $firstUserId];
    }

    public static function directConversationKey(string $firstUserId, string $secondUserId): string
    {
        [$one, $two] = self::sortedPair($firstUserId, $secondUserId);

        return "direct:{$one}:{$two}";
    }

    public static function ghostPostConversationKey(string $postId, string $firstUserId, string $secondUserId): string
    {
        [$one, $two] = self::sortedPair($firstUserId, $secondUserId);

        return "ghost_post:{$postId}:{$one}:{$two}";
    }

    public function userOne(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_one_id');
    }

    public function userTwo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_two_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latest('sent_at');
    }

    public function originChallenge(): BelongsTo
    {
        return $this->belongsTo(Challenge::class, 'origin_challenge_id');
    }

    public function originPost(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'origin_post_id');
    }

    public function ghostOwner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ghost_owner_id');
    }

    public function hasParticipant(string $userId): bool
    {
        return $this->user_one_id === $userId || $this->user_two_id === $userId;
    }

    public function isGhost(): bool
    {
        return $this->context_type === self::CONTEXT_GHOST_POST;
    }

    public function identityRevealed(): bool
    {
        return ! $this->isGhost() || $this->ghost_identity_revealed_at !== null;
    }

    public function shouldMaskIdentityOf(string $userId, User $viewer): bool
    {
        return $this->isGhost()
            && ! $this->identityRevealed()
            && $this->ghost_owner_id === $userId
            && $viewer->id !== $this->ghost_owner_id;
    }
}
