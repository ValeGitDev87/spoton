<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        ];
    }
}
