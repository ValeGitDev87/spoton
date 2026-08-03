<?php

namespace App\Support;

final class PostCategory
{
    public const SPOTTED_LOVE = 'spotted_love';

    public const WEATHER_TRANSPORT = 'weather_transport';

    public const GOSSIP_EVENTS = 'gossip_events';

    /** @return array<int, string> */
    public static function values(): array
    {
        return [
            self::SPOTTED_LOVE,
            self::WEATHER_TRANSPORT,
            self::GOSSIP_EVENTS,
        ];
    }

    public static function label(string $category): string
    {
        return match ($category) {
            self::WEATHER_TRANSPORT => 'Meteo e trasporti',
            self::GOSSIP_EVENTS => 'Gossip ed eventi',
            default => 'Spotted / Amore',
        };
    }
}
