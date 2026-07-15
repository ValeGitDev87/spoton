<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Comment extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'post_id',
        'author_id',
        'tagged_user_id',
        'text',
    ];

    protected $keyType = 'string';

    public $incrementing = false;

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function taggedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tagged_user_id');
    }
}
