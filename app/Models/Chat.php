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

    protected $fillable = [
        'user_one_id',
        'user_two_id',
    ];

    protected $keyType = 'string';

    public $incrementing = false;

    public static function sortedPair(string $firstUserId, string $secondUserId): array
    {
        return strcmp($firstUserId, $secondUserId) < 0
            ? [$firstUserId, $secondUserId]
            : [$secondUserId, $firstUserId];
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

    public function hasParticipant(string $userId): bool
    {
        return $this->user_one_id === $userId || $this->user_two_id === $userId;
    }
}
