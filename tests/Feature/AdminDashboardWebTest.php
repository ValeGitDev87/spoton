<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\Post;
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
}
