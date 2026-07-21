<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(DemoUsersSeeder::class);

        $locations = [
            ['name' => 'Metro Mergellina', 'city' => 'Napoli', 'type' => 'metro', 'latitude' => 40.8319000, 'longitude' => 14.2193000, 'icon' => 'subway-outline'],
            ['name' => 'Bar Nilo', 'city' => 'Napoli', 'type' => 'bar', 'latitude' => 40.8495000, 'longitude' => 14.2569000, 'icon' => 'cafe-outline'],
            ['name' => 'Piazza Plebiscito', 'city' => 'Napoli', 'type' => 'piazza', 'latitude' => 40.8359000, 'longitude' => 14.2488000, 'icon' => 'business-outline'],
            ['name' => 'Lungomare Caracciolo', 'city' => 'Napoli', 'type' => 'lungomare', 'latitude' => 40.8297000, 'longitude' => 14.2284000, 'icon' => 'water-outline'],
        ];

        foreach ($locations as $location) {
            Location::query()->updateOrCreate(
                ['name' => $location['name'], 'city' => $location['city']],
                $location + ['short' => $location['name'], 'geo_radius_meters' => 100, 'is_active' => true],
            );
        }
    }
}
