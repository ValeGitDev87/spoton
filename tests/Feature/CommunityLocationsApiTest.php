<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CommunityLocationsApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-08-28 12:00:00');
        config()->set('spoton.community_locations.daily_limit', 3);
        config()->set('spoton.community_locations.max_distance_meters', 1000);
        config()->set('spoton.community_locations.duplicate_radius_meters', 150);
        config()->set('spoton.community_locations.position_max_age_minutes', 10);
        config()->set('spoton.community_locations.max_accuracy_meters', 100);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_authenticated_verified_user_can_create_a_pending_community_location(): void
    {
        $user = $this->positionedUser();

        $this
            ->actingAs($user, 'sanctum')
            ->postJson('/api/locations', $this->payload())
            ->assertCreated()
            ->assertJsonPath('data.name', 'Panchina Belvedere')
            ->assertJsonPath('data.tier', Location::TIER_COMMUNITY)
            ->assertJsonPath('data.is_partner', false)
            ->assertJsonPath('data.moderation_status', Location::MODERATION_PENDING)
            ->assertJsonPath('data.icon', 'leaf-outline')
            ->assertJsonPath('data.is_locked', false);

        $this->assertDatabaseHas('locations', [
            'name' => 'Panchina Belvedere',
            'short' => 'Panchina Belvedere',
            'tier' => Location::TIER_COMMUNITY,
            'moderation_status' => Location::MODERATION_PENDING,
            'created_by_user_id' => $user->id,
            'geo_radius_meters' => 100,
            'icon' => 'leaf-outline',
            'is_active' => true,
            'is_locked' => false,
            'access_password_hash' => null,
        ]);
    }

    public function test_unverified_user_cannot_create_a_location(): void
    {
        $user = $this->positionedUser(['email_verified_at' => null]);

        $this
            ->actingAs($user, 'sanctum')
            ->postJson('/api/locations', $this->payload())
            ->assertForbidden()
            ->assertJsonPath('message', 'Verifica la tua email prima di aggiungere un luogo.');
    }

    public function test_recent_accurate_position_is_required(): void
    {
        $missingPosition = User::factory()->create();

        $this
            ->actingAs($missingPosition, 'sanctum')
            ->postJson('/api/locations', $this->payload())
            ->assertUnprocessable()
            ->assertJsonValidationErrors('position');

        $inaccuratePosition = $this->positionedUser([
            'last_location_accuracy_meters' => 150,
        ]);

        $this
            ->actingAs($inaccuratePosition, 'sanctum')
            ->postJson('/api/locations', $this->payload())
            ->assertUnprocessable()
            ->assertJsonValidationErrors('position');
    }

    public function test_user_cannot_create_a_location_too_far_from_current_position(): void
    {
        $user = $this->positionedUser();

        $this
            ->actingAs($user, 'sanctum')
            ->postJson('/api/locations', $this->payload([
                'latitude' => 40.9000000,
                'longitude' => 14.3000000,
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('latitude');
    }

    public function test_community_endpoint_rejects_partner_and_administrative_fields(): void
    {
        $user = $this->positionedUser();

        $this
            ->actingAs($user, 'sanctum')
            ->postJson('/api/locations', $this->payload([
                'tier' => Location::TIER_PARTNER,
                'is_locked' => true,
                'access_password' => 'segreta',
                'geo_radius_meters' => 5000,
                'icon' => 'airplane-outline',
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'tier',
                'is_locked',
                'access_password',
                'geo_radius_meters',
                'icon',
            ]);
    }

    public function test_likely_duplicate_is_not_created_and_candidates_are_returned(): void
    {
        $user = $this->positionedUser();
        $existing = Location::query()->create([
            'name' => 'Panchina Belvedere',
            'short' => 'Panchina Belvedere',
            'city' => 'Napoli',
            'type' => 'parco',
            'latitude' => 40.8447500,
            'longitude' => 14.2306000,
            'geo_radius_meters' => 100,
            'is_active' => true,
        ]);

        $this
            ->actingAs($user, 'sanctum')
            ->postJson('/api/locations', $this->payload([
                'name' => 'Panchina  Belvedere',
            ]))
            ->assertConflict()
            ->assertJsonPath('data.candidates.0.id', $existing->id)
            ->assertJsonPath('data.candidates.0.is_partner', true);

        $this->assertDatabaseCount('locations', 1);
    }

    public function test_user_cannot_exceed_successful_creation_limit_in_24_hours(): void
    {
        $user = $this->positionedUser();
        $payloads = [
            $this->payload(['name' => 'Panchina Belvedere', 'type' => 'parco']),
            $this->payload(['name' => 'Aula Studio Centro', 'type' => 'universita']),
            $this->payload(['name' => 'Bar Tramonto', 'type' => 'bar']),
        ];

        foreach ($payloads as $payload) {
            $this->actingAs($user, 'sanctum')->postJson('/api/locations', $payload)->assertCreated();
        }

        $this
            ->actingAs($user, 'sanctum')
            ->postJson('/api/locations', $this->payload([
                'name' => 'Piazza del Sole',
                'type' => 'piazza',
            ]))
            ->assertTooManyRequests();

        $this->assertDatabaseCount('locations', 3);
    }

    public function test_user_can_only_list_locations_they_created(): void
    {
        $user = $this->positionedUser();
        $otherUser = User::factory()->create();
        $mine = $this->communityLocation($user, [
            'moderation_status' => Location::MODERATION_REJECTED,
            'moderation_note' => 'Luogo duplicato.',
            'is_active' => false,
        ]);
        $this->communityLocation($otherUser, ['name' => 'Luogo di un altro utente']);

        $this
            ->actingAs($user, 'sanctum')
            ->getJson('/api/locations/mine')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $mine->id)
            ->assertJsonPath('data.0.moderation_status', Location::MODERATION_REJECTED)
            ->assertJsonPath('data.0.moderation_note', 'Luogo duplicato.');
    }

    public function test_admin_can_moderate_a_community_location(): void
    {
        $creator = $this->positionedUser();
        $admin = User::factory()->create(['is_admin' => true]);
        $location = $this->communityLocation($creator);

        $this
            ->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/locations/{$location->id}/moderation", [
                'status' => Location::MODERATION_REJECTED,
                'note' => 'Duplicato di un luogo esistente.',
            ])
            ->assertOk()
            ->assertJsonPath('data.moderation_status', Location::MODERATION_REJECTED)
            ->assertJsonPath('data.is_active', false);

        $this->assertDatabaseHas('locations', [
            'id' => $location->id,
            'moderated_by_user_id' => $admin->id,
            'moderation_status' => Location::MODERATION_REJECTED,
            'moderation_note' => 'Duplicato di un luogo esistente.',
            'is_active' => false,
        ]);

        $this
            ->actingAs($creator, 'sanctum')
            ->getJson('/api/locations')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_duplicate_preview_endpoint_returns_nearby_match(): void
    {
        $user = $this->positionedUser();
        $existing = Location::query()->create([
            'name' => 'Bar Tramonto',
            'short' => 'Bar Tramonto',
            'city' => 'Napoli',
            'type' => 'bar',
            'latitude' => 40.8447000,
            'longitude' => 14.2306000,
            'geo_radius_meters' => 100,
            'is_active' => true,
        ]);

        $this
            ->actingAs($user, 'sanctum')
            ->getJson('/api/locations/duplicates?name=Bar%20Tramonto&type=bar&latitude=40.8447316&longitude=14.2305912')
            ->assertOk()
            ->assertJsonPath('data.candidates.0.id', $existing->id);
    }

    private function positionedUser(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'last_known_latitude' => 40.8447316,
            'last_known_longitude' => 14.2305912,
            'last_location_accuracy_meters' => 20,
            'last_location_update' => now(),
        ], $overrides));
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Panchina Belvedere',
            'city' => 'Napoli',
            'type' => 'parco',
            'latitude' => 40.8447316,
            'longitude' => 14.2305912,
        ], $overrides);
    }

    private function communityLocation(User $creator, array $overrides = []): Location
    {
        return Location::query()->create(array_merge([
            'name' => 'Panchina Belvedere',
            'short' => 'Panchina Belvedere',
            'city' => 'Napoli',
            'type' => 'parco',
            'tier' => Location::TIER_COMMUNITY,
            'moderation_status' => Location::MODERATION_PENDING,
            'latitude' => 40.8447316,
            'longitude' => 14.2305912,
            'geo_radius_meters' => 100,
            'is_active' => true,
            'created_by_user_id' => $creator->id,
        ], $overrides));
    }
}
