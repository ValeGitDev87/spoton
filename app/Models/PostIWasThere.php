<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostIWasThere extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'post_i_was_there';

    protected $fillable = [
        'post_id',
        'user_id',
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
