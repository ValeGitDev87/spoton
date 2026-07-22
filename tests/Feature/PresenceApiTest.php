<?php

namespace Tests\Feature;

use App\Jobs\CloseStalePresenceSessionsJob;
use App\Jobs\PurgeLocationDataJob;
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

    public function test_location_retention_removes_only_expired_data(): void
    {
        Carbon::setTestNow('2026-07-21 12:00:00');
        config()->set('spoton.privacy.location_retention_hours', 24);
        config()->set('spoton.privacy.presence_retention_days', 30);

        $expiredUser = User::factory()->create([
            'last_known_latitude' => 40.8495,
            'last_known_longitude' => 14.2569,
            'last_location_update' => now()->subHours(25),
        ]);
        $freshUser = User::factory()->create([
            'last_known_latitude' => 41.9028,
            'last_known_longitude' => 12.4964,
            'last_location_update' => now()->subHours(23),
        ]);
        $location = $this->location();
        $expiredSession = PresenceSession::query()->create([
            'user_id' => $expiredUser->id,
            'location_id' => $location->id,
            'started_at' => now()->subDays(40),
            'last_ping_at' => now()->subDays(40),
            'ended_at' => now()->subDays(31),
        ]);
        $freshSession = PresenceSession::query()->create([
            'user_id' => $freshUser->id,
            'location_id' => $location->id,
            'started_at' => now()->subDays(20),
            'last_ping_at' => now()->subDays(20),
            'ended_at' => now()->subDays(20),
        ]);

        (new PurgeLocationDataJob)->handle();

        $expiredUser->refresh();
        $this->assertNull($expiredUser->last_known_latitude);
        $this->assertNull($expiredUser->last_known_longitude);
        $this->assertNull($expiredUser->last_location_update);
        $this->assertNotNull($freshUser->refresh()->last_location_update);
        $this->assertDatabaseMissing('presence_sessions', ['id' => $expiredSession->id]);
        $this->assertDatabaseHas('presence_sessions', ['id' => $freshSession->id]);

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
