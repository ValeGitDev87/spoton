<?php

namespace App\Services\Places;

use App\Contracts\PlaceProvider;

class NullPlaceProvider implements PlaceProvider
{
    public function nearby(float $latitude, float $longitude, int $radiusMeters, int $limit): array
    {
        return [];
    }

    public function cacheable(): bool
    {
        return true;
    }

    public function name(): string
    {
        return 'none';
    }
}
