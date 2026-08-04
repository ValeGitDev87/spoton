<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostCommunityVote extends Model
{
    use HasFactory, HasUuids;

    public const VOTE_CONFIRM = 'confirm';

    public const VOTE_FALSE = 'false';

    protected $fillable = [
        'post_id',
        'user_id',
        'vote',
    ];

    protected $keyType = 'string';

    public $incrementing = false;

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
