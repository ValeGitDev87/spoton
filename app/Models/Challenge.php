<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Challenge extends Model
{
    use HasFactory, HasUuids;

    public const ORIGIN_CLASSIC = 'classic';
    public const ORIGIN_INVERTED = 'inverted';

    public const TARGET_POST_AUTHOR = 'post_author';
    public const TARGET_COMMENT_AUTHOR = 'comment_author';

    public const STATUS_PENDING = 'pending';
    public const STATUS_UNLOCKED = 'unlocked';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_COUNTER_PENDING = 'counter_pending';

    protected $fillable = [
        'post_id',
        'origin',
        'challenger_id',
        'target_type',
        'target_user_id',
        'source_comment_id',
        'question',
        'answer_hash',
        'status',
        'counter_text',
        'counter_proposed_by',
        'resolved_at',
    ];

    protected $keyType = 'string';

    public $incrementing = false;

    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
        ];
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function challenger(): BelongsTo
    {
        return $this->belongsTo(User::class, 'challenger_id');
    }

    public function targetUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    public function sourceComment(): BelongsTo
    {
        return $this->belongsTo(Comment::class, 'source_comment_id');
    }

    public function counterProposer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'counter_proposed_by');
    }

    public function chats(): HasMany
    {
        return $this->hasMany(Chat::class, 'origin_challenge_id');
    }
}
