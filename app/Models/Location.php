<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Hash;

class Location extends Model
{
    use HasFactory, HasUuids;

    public const TIER_COMMUNITY = 'community';

    public const TIER_PARTNER = 'partner';

    public const MODERATION_PENDING = 'pending';

    public const MODERATION_APPROVED = 'approved';

    public const MODERATION_REJECTED = 'rejected';

    public const MODERATION_SUSPENDED = 'suspended';

    protected $fillable = [
        'name',
        'short',
        'city',
        'type',
        'tier',
        'moderation_status',
        'latitude',
        'longitude',
        'geo_radius_meters',
        'icon',
        'is_active',
        'is_locked',
        'access_password_hash',
        'created_by_user_id',
        'moderated_by_user_id',
        'moderated_at',
        'moderation_note',
    ];

    protected $hidden = [
        'access_password_hash',
    ];

    protected $keyType = 'string';

    public $incrementing = false;

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'geo_radius_meters' => 'integer',
            'is_active' => 'boolean',
            'is_locked' => 'boolean',
            'moderated_at' => 'datetime',
        ];
    }

    public function accessPasswordMatches(?string $password): bool
    {
        if (! $this->is_locked) {
            return true;
        }

        return is_string($password)
            && $password !== ''
            && is_string($this->access_password_hash)
            && Hash::check($password, $this->access_password_hash);
    }

    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->whereIn('moderation_status', [
                self::MODERATION_PENDING,
                self::MODERATION_APPROVED,
            ]);
    }

    public function isPubliclyVisible(): bool
    {
        return $this->is_active
            && in_array($this->moderation_status, [
                self::MODERATION_PENDING,
                self::MODERATION_APPROVED,
            ], true);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderated_by_user_id');
    }

    public function isPartner(): bool
    {
        return $this->tier === self::TIER_PARTNER;
    }

    public function presenceSessions(): HasMany
    {
        return $this->hasMany(PresenceSession::class);
    }
}
