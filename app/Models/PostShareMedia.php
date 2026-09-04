<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostShareMedia extends Model
{
    use HasFactory, HasUuids;

    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_READY = 'ready';

    public const STATUS_FAILED = 'failed';

    protected $table = 'post_share_media';

    protected $fillable = [
        'post_id',
        'template_version',
        'status',
        'disk',
        'path',
        'mime',
        'size_bytes',
        'generated_at',
        'expires_at',
        'error_code',
    ];

    protected $keyType = 'string';

    public $incrementing = false;

    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
            'generated_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function scopeExpiredOrUnavailable(Builder $query): Builder
    {
        return $query->where(function (Builder $query): void {
            $query
                ->where('expires_at', '<=', now())
                ->orWhereHas('post', function (Builder $post): void {
                    $post
                        ->where('status', '!=', 'active')
                        ->orWhere('expires_at', '<=', now())
                        ->orWhereHas('location', fn (Builder $location) => $location
                            ->where('is_active', false));
                });
        });
    }
}
