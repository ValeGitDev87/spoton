<?php

namespace Tests\Feature;

use App\Jobs\CloseStalePresenceSessionsJob;
use App\Models\Location;
use App\Models\PresenceSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PresenceApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_presence_ping_updates_user_and_creates_active_session(): void
    {
        Carbon::setTestNow('2026-07-13 10:00:00');

        $user = User::factory()->create();
        $location = $this->location();

        $this
            ->actingAs($user, 'sanctum')
            ->postJson('/api/presence/ping', [
                'lat' => 40.8495000,
                'lng' => 14.2569000,
            ])
            ->assertOk()
            ->assertJsonPath('data.locations.0.id', $location->id)
            ->assertJsonPath('data.locations.0.connected_now_count', 1);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'last_known_latitude' => 40.8495000,
            'last_known_longitude' => 14.2569000,
        ]);

        $this->assertDatabaseHas('presence_sessions', [
            'user_id' => $user->id,
            'location_id' => $location->id,
            'ended_at' => null,
        ]);

        Carbon::setTestNow();
    }

    public function test_stale_presence_sessions_are_closed_by_job(): void
    {
        Carbon::setTestNow('2026-07-13 10:00:00');

        $user = User::factory()->create();
        $location = $this->location();
        $session = PresenceSession::query()->create([
            'user_id' => $user->id,
            'location_id' => $location->id,
            'started_at' => now()->subMinutes(10),
            'last_ping_at' => now()->subMinutes(6),
        ]);

        (new CloseStalePresenceSessionsJob)->handle();

        $this->assertNotNull($session->refresh()->ended_at);

        Carbon::setTestNow();
    }

    private function location(): Location
    {
        return Location::query()->create([
            'name' => 'Bar Nilo',
            'short' => 'Bar Nilo',
            'city' => 'Napoli',
            'type' => 'bar',
            'latitude' => 40.8495000,
            'longitude' => 14.2569000,
            'geo_radius_meters' => 250,
            'is_active' => true,
        ]);
    }
}
