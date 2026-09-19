<?php

namespace App\Contracts;

interface PlaceProvider
{
    /**
     * @return array<int, array{
     *     provider: string,
     *     provider_place_id: string,
     *     name: string,
     *     city: string,
     *     type: string,
     *     location_kind: string,
     *     latitude: float,
     *     longitude: float,
     *     formatted_address: string|null
     * }>
     */
    public function nearby(float $latitude, float $longitude, int $radiusMeters, int $limit): array;

    public function cacheable(): bool;

    public function name(): string;
}
