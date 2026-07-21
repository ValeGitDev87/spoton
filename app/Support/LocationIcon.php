<?php

namespace App\Support;

final class LocationIcon
{
    public const DEFAULT = 'location-outline';

    public static function options(): array
    {
        return [
            'location-outline' => 'Luogo generico',
            'subway-outline' => 'Metro',
            'train-outline' => 'Treno / stazione',
            'bus-outline' => 'Autobus',
            'airplane-outline' => 'Aeroporto',
            'boat-outline' => 'Porto / traghetto',
            'cafe-outline' => 'Bar / caffe',
            'restaurant-outline' => 'Ristorante',
            'beer-outline' => 'Pub',
            'storefront-outline' => 'Negozio',
            'business-outline' => 'Edificio / monumento',
            'library-outline' => 'Biblioteca / cultura',
            'school-outline' => 'Scuola / universita',
            'musical-notes-outline' => 'Musica / locale',
            'football-outline' => 'Sport',
            'fitness-outline' => 'Palestra',
            'leaf-outline' => 'Parco / natura',
            'water-outline' => 'Mare / lungomare',
            'medical-outline' => 'Ospedale / salute',
            'cart-outline' => 'Centro commerciale',
        ];
    }

    public static function codes(): array
    {
        return array_keys(self::options());
    }
}
