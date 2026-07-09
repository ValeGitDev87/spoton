<?php

namespace Database\Seeders;

use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'display_name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        User::factory()->create([
            'display_name' => 'Admin SpotOn',
            'email' => 'admin@spoton.local',
            'password' => Hash::make('password123'),
        ]);

        $locations = [
            ['name' => 'Metro Mergellina', 'city' => 'Napoli', 'type' => 'metro', 'latitude' => 40.8319000, 'longitude' => 14.2193000, 'icon' => 'metro'],
            ['name' => 'Bar Nilo', 'city' => 'Napoli', 'type' => 'bar', 'latitude' => 40.8495000, 'longitude' => 14.2569000, 'icon' => 'coffee'],
            ['name' => 'Piazza Plebiscito', 'city' => 'Napoli', 'type' => 'piazza', 'latitude' => 40.8359000, 'longitude' => 14.2488000, 'icon' => 'landmark'],
            ['name' => 'Lungomare Caracciolo', 'city' => 'Napoli', 'type' => 'lungomare', 'latitude' => 40.8297000, 'longitude' => 14.2284000, 'icon' => 'waves'],
        ];

        foreach ($locations as $location) {
            Location::query()->updateOrCreate(
                ['name' => $location['name'], 'city' => $location['city']],
                $location + ['short' => $location['name'], 'geo_radius_meters' => 100, 'is_active' => true],
            );
        }
    }
}
