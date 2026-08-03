<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Hash;

class Location extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'short',
        'city',
        'type',
        'latitude',
        'longitude',
        'geo_radius_meters',
        'icon',
        'is_active',
        'is_locked',
        'access_password_hash',
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

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function presenceSessions(): HasMany
    {
        return $this->hasMany(PresenceSession::class);
    }
}
