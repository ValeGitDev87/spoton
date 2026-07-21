<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoUsersSeeder extends Seeder
{
    public const PASSWORD = 'password123';

    public const USERS = [
        ['display_name' => 'Luca Test', 'email' => 'luca@test.it', 'avatar_color' => '#0ea5e9'],
        ['display_name' => 'Sara Blu', 'email' => 'sara@test.it', 'avatar_color' => '#ec4899'],
        ['display_name' => 'Marco Verdi', 'email' => 'marco@test.it', 'avatar_color' => '#10b981'],
        ['display_name' => 'Giulia Rossa', 'email' => 'giulia@test.it', 'avatar_color' => '#ef4444'],
        ['display_name' => 'Elena Sole', 'email' => 'elena@test.it', 'avatar_color' => '#f59e0b'],
    ];

    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@spoton.local'],
            [
                'display_name' => 'Admin SpotOn',
                'password' => Hash::make(self::PASSWORD),
                'is_admin' => true,
                'is_suspended' => false,
                'suspended_at' => null,
                'suspension_reason' => null,
                'avatar_color' => '#111827',
                'auth_provider' => 'email',
                'karma' => 0,
            ],
        );

        foreach (self::USERS as $index => $user) {
            User::query()->updateOrCreate(
                ['email' => $user['email']],
                [
                    'display_name' => $user['display_name'],
                    'password' => Hash::make(self::PASSWORD),
                    'is_admin' => false,
                    'is_suspended' => false,
                    'suspended_at' => null,
                    'suspension_reason' => null,
                    'avatar_color' => $user['avatar_color'],
                    'auth_provider' => 'email',
                    'bio' => $index === 0 ? 'Account test per provare SpotOn.' : 'Profilo demo SpotOn.',
                    'photos' => ["https://example.test/photos/{$user['email']}.jpg"],
                    'karma' => $index === 0 ? 2 : 0,
                ],
            );
        }
    }
}
