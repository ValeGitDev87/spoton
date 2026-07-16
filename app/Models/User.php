<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasUuids, Notifiable;

    protected $fillable = [
        'email',
        'password',
        'display_name',
        'avatar_color',
        'avatar_url',
        'bio',
        'photos',
        'karma',
        'auth_provider',
        'is_admin',
        'last_known_latitude',
        'last_known_longitude',
        'last_location_update',
    ];

    protected $keyType = 'string';

    public $incrementing = false;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_admin' => 'boolean',
            'photos' => 'array',
            'karma' => 'integer',
            'last_location_update' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class, 'author_id');
    }

    public function likes(): HasMany
    {
        return $this->hasMany(Like::class);
    }

    public function iWasThere(): HasMany
    {
        return $this->hasMany(PostIWasThere::class);
    }

    public function presenceSessions(): HasMany
    {
        return $this->hasMany(PresenceSession::class);
    }

    public function sentMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class, 'owner_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class, 'author_id');
    }

    public function challenges(): HasMany
    {
        return $this->hasMany(Challenge::class, 'challenger_id');
    }

    public function challengeTargets(): HasMany
    {
        return $this->hasMany(Challenge::class, 'target_user_id');
    }
}
