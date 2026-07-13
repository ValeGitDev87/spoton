<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PresenceSession extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'location_id',
        'started_at',
        'last_ping_at',
        'ended_at',
    ];

    protected $keyType = 'string';

    public $incrementing = false;

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'last_ping_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }
}
