<?php

namespace Tests\Feature;

use App\Contracts\PlaceProvider;
use App\Models\Location;
use App\Models\User;
use App\Services\Places\PlaceCandidateToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use RuntimeException;
use Tests\TestCase;

class SmartLocationCreationApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::clear();
        config()->set('spoton.smart_location.nearby_radius_meters', 150);
        config()->set('spoton.smart_location.provider_radius_meters', 150);
        config()->set('spoton.smart_location.candidate_limit', 5);
        config()->set('spoton.smart_location.cache_ttl_seconds', 86400);
    }

    public function test_lookup_stops_at_nearby_spoton_location(): void
    {
        $user = $this->positionedUser();
        $location = $this->location(['name' => 'Piazza Vanvitelli']);
        $provider = new FakePlaceProvider([$this->providerCandidate()]);
        $this->app->instance(PlaceProvider::class, $provider);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/locations/nearby-candidates')
            ->assertOk()
            ->assertJsonPath('data.existing.0.id', $location->id)
            ->assertJsonCount(0, 'data.provider');

        $this->assertSame(0, $provider->calls);
    }

    public function test_cache_hit_does_not_call_a_cacheable_provider_twice(): void
    {
        $user = $this->positionedUser();
        $provider = new FakePlaceProvider([$this->providerCandidate()]);
        $this->app->instance(PlaceProvider::class, $provider);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/locations/nearby-candidates')
            ->assertOk()
            ->assertJsonPath('data.provider.0.name', 'Bar Sole');

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/locations/nearby-candidates')
            ->assertOk()
            ->assertJsonPath('data.provider.0.name', 'Bar Sole');

        $this->assertSame(1, $provider->calls);
    }

    public function test_signed_provider_candidate_is_created_and_then_reused(): void
    {
        $user = $this->positionedUser();
        $candidate = $this->providerCandidate();
        $token = app(PlaceCandidateToken::class)->encode($candidate, $user->id);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/locations', ['candidate_token' => $token])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Bar Sole')
            ->assertJsonPath('data.moderation_status', Location::MODERATION_PENDING);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/locations', ['candidate_token' => $token])
            ->assertOk()
            ->assertJsonPath('data.resolved_existing', true);

        $this->assertDatabaseCount('locations', 1);
        $this->assertDatabaseHas('locations', [
            'provider' => 'fake',
            'provider_place_id' => 'place-123',
            'normalized_name' => 'bar sole',
        ]);
    }

    public function test_same_provider_id_is_reused_despite_gps_oscillation(): void
    {
        $user = $this->positionedUser();
        $existing = $this->location([
            'name' => 'Bar Sole',
            'type' => 'bar',
            'provider' => 'fake',
            'provider_place_id' => 'place-123',
            'latitude' => 40.8453000,
        ]);
        $candidate = $this->providerCandidate(['latitude' => 40.8447316]);
        $token = app(PlaceCandidateToken::class)->encode($candidate, $user->id);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/locations', ['candidate_token' => $token])
            ->assertOk()
            ->assertJsonPath('data.id', $existing->id)
            ->assertJsonPath('data.resolved_existing', true);

        $this->assertDatabaseCount('locations', 1);
    }

    public function test_area_name_drops_street_number_before_duplicate_check(): void
    {
        $user = $this->positionedUser();
        $existing = $this->location([
            'name' => 'Via Toledo',
            'normalized_name' => 'via toledo',
            'type' => 'altro',
            'location_kind' => 'area',
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/locations', [
                'name' => 'Via Toledo 118',
                'city' => 'Napoli',
                'type' => 'altro',
                'location_kind' => 'area',
                'latitude' => 40.8447316,
                'longitude' => 14.2305912,
            ])
            ->assertConflict()
            ->assertJsonPath('data.candidates.0.id', $existing->id);

        $this->assertDatabaseCount('locations', 1);
    }

    public function test_provider_failure_returns_safe_empty_fallback(): void
    {
        $user = $this->positionedUser();
        $provider = new FakePlaceProvider([], true);
        $this->app->instance(PlaceProvider::class, $provider);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/locations/nearby-candidates')
            ->assertOk()
            ->assertJsonCount(0, 'data.existing')
            ->assertJsonCount(0, 'data.provider')
            ->assertJsonPath('data.provider_available', true)
            ->assertJsonPath('data.provider_failed', true);
    }

    private function positionedUser(): User
    {
        return User::factory()->create([
            'last_known_latitude' => 40.8447316,
            'last_known_longitude' => 14.2305912,
            'last_location_accuracy_meters' => 20,
            'last_location_update' => now(),
        ]);
    }

    private function location(array $overrides = []): Location
    {
        return Location::query()->create(array_merge([
            'name' => 'Piazza Vanvitelli',
            'short' => 'Piazza Vanvitelli',
            'city' => 'Napoli',
            'type' => 'piazza',
            'latitude' => 40.8447500,
            'longitude' => 14.2306000,
            'geo_radius_meters' => 100,
            'is_active' => true,
        ], $overrides));
    }

    private function providerCandidate(array $overrides = []): array
    {
        return array_merge([
            'provider' => 'fake',
            'provider_place_id' => 'place-123',
            'name' => 'Bar Sole',
            'city' => 'Napoli',
            'type' => 'bar',
            'location_kind' => 'poi',
            'latitude' => 40.8447316,
            'longitude' => 14.2305912,
            'formatted_address' => 'Via Roma 1, Napoli',
        ], $overrides);
    }
}

class FakePlaceProvider implements PlaceProvider
{
    public int $calls = 0;

    public function __construct(
        private readonly array $candidates,
        private readonly bool $fails = false,
    ) {}

    public function nearby(float $latitude, float $longitude, int $radiusMeters, int $limit): array
    {
        $this->calls++;

        if ($this->fails) {
            throw new RuntimeException('Provider timeout');
        }

        return $this->candidates;
    }

    public function cacheable(): bool
    {
        return true;
    }

    public function name(): string
    {
        return 'fake';
    }
}
