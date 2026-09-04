<?php

namespace App\Support;

final class LocationType
{
    public static function options(): array
    {
        return [
            'metro' => ['label' => 'Metropolitana', 'icon' => 'subway-outline'],
            'bus' => ['label' => 'Bus', 'icon' => 'bus-outline'],
            'ristorante' => ['label' => 'Ristorante', 'icon' => 'restaurant-outline'],
            'bar' => ['label' => 'Bar', 'icon' => 'beer-outline'],
            'universita' => ['label' => 'Universita', 'icon' => 'school-outline'],
            'locale' => ['label' => 'Locale', 'icon' => 'musical-notes-outline'],
            'discoteca' => ['label' => 'Discoteca', 'icon' => 'musical-notes-outline'],
            'piazza' => ['label' => 'Piazza', 'icon' => 'business-outline'],
            'lungomare' => ['label' => 'Lungomare', 'icon' => 'water-outline'],
            'parco' => ['label' => 'Parco', 'icon' => 'leaf-outline'],
            'altro' => ['label' => 'Altro', 'icon' => LocationIcon::DEFAULT],
        ];
    }

    public static function codes(): array
    {
        return array_keys(self::options());
    }

    public static function icon(string $type): string
    {
        return self::options()[$type]['icon'] ?? LocationIcon::DEFAULT;
    }
}
