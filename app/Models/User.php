<?php

namespace App\Models;

use App\Notifications\Auth\ResetPasswordNotification;
use App\Notifications\Auth\VerifyEmailNotification;
use Database\Factories\UserFactory;
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmailContract
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasUuids, MustVerifyEmail, Notifiable;

    protected $fillable = [
        'email',
        'password',
        'display_name',
        'avatar_color',
        'avatar_url',
        'bio',
        'motto',
        'favorite_song',
        'show_bio',
        'show_motto',
        'show_favorite_song',
        'photos',
        'public_photo_urls',
        'karma',
        'auth_provider',
        'is_admin',
        'can_mention_everyone',
        'is_suspended',
        'suspended_at',
        'suspension_reason',
        'last_known_latitude',
        'last_known_longitude',
        'last_location_accuracy_meters',
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
            'welcome_email_sent_at' => 'datetime',
            'password_changed_at' => 'datetime',
            'is_admin' => 'boolean',
            'can_mention_everyone' => 'boolean',
            'is_suspended' => 'boolean',
            'suspended_at' => 'datetime',
            'photos' => 'array',
            'public_photo_urls' => 'array',
            'show_bio' => 'boolean',
            'show_motto' => 'boolean',
            'show_favorite_song' => 'boolean',
            'karma' => 'integer',
            'last_location_update' => 'datetime',
            'last_location_accuracy_meters' => 'integer',
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

    public function createdLocations(): HasMany
    {
        return $this->hasMany(Location::class, 'created_by_user_id');
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

    public function pushTokens(): HasMany
    {
        return $this->hasMany(PushToken::class);
    }

    public function appNotifications(): HasMany
    {
        return $this->hasMany(UserNotification::class);
    }

    public function reportsMade(): HasMany
    {
        return $this->hasMany(Report::class, 'reporter_id');
    }

    public function reportsReceived(): MorphMany
    {
        return $this->morphMany(Report::class, 'reportable');
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmailNotification);
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }
}
