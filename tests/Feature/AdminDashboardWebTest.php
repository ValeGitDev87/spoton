<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\Post;
use App\Models\PushToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardWebTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_dashboard_summary(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create(['display_name' => 'Mario Rossi']);
        $location = Location::query()->create([
            'name' => 'Bar Nilo',
            'short' => 'Bar Nilo',
            'city' => 'Napoli',
            'type' => 'bar',
            'latitude' => 40.8495000,
            'longitude' => 14.2569000,
            'geo_radius_meters' => 100,
            'is_active' => true,
        ]);

        Post::query()->create([
            'author_id' => $user->id,
            'location_id' => $location->id,
            'text' => 'Post dashboard',
            'musica' => 'Ritornello dashboard',
            'sighting_date' => '2026-07-10',
            'expires_at' => now()->addDay(),
            'status' => 'active',
        ]);

        $this
            ->actingAs($admin)
            ->get('/admin')
            ->assertOk()
            ->assertSee('Dashboard')
            ->assertSee('Utenti')
            ->assertSee('Luoghi')
            ->assertSee('Post dashboard');
    }

    public function test_non_admin_cannot_view_dashboard(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this
            ->actingAs($user)
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_admin_sees_push_tokens_without_exposing_the_full_token(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create(['display_name' => 'Lucia Bianchi']);
        $rawToken = 'ExpoPushToken[private_ios_token]';

        PushToken::query()->create([
            'user_id' => $user->id,
            'token' => $rawToken,
            'token_hash' => PushToken::hashToken($rawToken),
            'device_id' => 'iphone-lucia',
            'platform' => 'ios',
            'app_version' => '1.5.0',
            'locale' => 'it-IT',
            'timezone' => 'Europe/Rome',
            'is_active' => true,
            'last_seen_at' => now(),
        ]);

        $this
            ->actingAs($admin)
            ->get('/admin')
            ->assertOk()
            ->assertSee('Token push salvati')
            ->assertSee('1 totali')
            ->assertSee('Lucia Bianchi')
            ->assertSee('iphone-lucia')
            ->assertSee('1.5.0')
            ->assertSee(substr(PushToken::hashToken($rawToken), 0, 12))
            ->assertDontSee($rawToken);
    }

    public function test_dashboard_paginates_push_tokens(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        for ($index = 0; $index < 21; $index++) {
            $rawToken = "ExpoPushToken[dashboard_token_{$index}]";
            PushToken::query()->create([
                'user_id' => $admin->id,
                'token' => $rawToken,
                'token_hash' => PushToken::hashToken($rawToken),
                'device_id' => "dashboard-device-{$index}",
                'platform' => 'android',
                'is_active' => $index % 2 === 0,
            ]);
        }

        $this
            ->actingAs($admin)
            ->get('/admin')
            ->assertOk()
            ->assertSee('21 totali')
            ->assertSee('push_page=2');

        $this
            ->actingAs($admin)
            ->get('/admin?push_page=2')
            ->assertOk()
            ->assertSee('Token push salvati');
    }
}
