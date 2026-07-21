<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Post extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'author_id',
        'location_id',
        'text',
        'musica',
        'song_quote',
        'audio_disk',
        'audio_path',
        'audio_url',
        'audio_mime',
        'audio_size_bytes',
        'audio_duration_seconds',
        'sighting_date',
        'is_anonymous',
        'secret_question',
        'secret_answer_hash',
        'expires_at',
        'like_count',
        'comment_count',
        'share_count',
        'io_cero_count',
        'spot_on_count',
        'status',
    ];

    protected $keyType = 'string';

    public $incrementing = false;

    protected function casts(): array
    {
        return [
            'sighting_date' => 'date',
            'is_anonymous' => 'boolean',
            'expires_at' => 'datetime',
            'like_count' => 'integer',
            'comment_count' => 'integer',
            'share_count' => 'integer',
            'io_cero_count' => 'integer',
            'spot_on_count' => 'integer',
            'audio_size_bytes' => 'integer',
            'audio_duration_seconds' => 'integer',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function likes(): HasMany
    {
        return $this->hasMany(Like::class);
    }

    public function iWasThere(): HasMany
    {
        return $this->hasMany(PostIWasThere::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function challenges(): HasMany
    {
        return $this->hasMany(Challenge::class);
    }

    public function reports(): MorphMany
    {
        return $this->morphMany(Report::class, 'reportable');
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && $this->expires_at->isFuture();
    }
}
