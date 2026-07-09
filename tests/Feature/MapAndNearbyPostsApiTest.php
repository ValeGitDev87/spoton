<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class MapAndNearbyPostsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_nearby_posts_returns_active_posts_inside_radius(): void
    {
        Carbon::setTestNow('2026-07-09 12:00:00');

        $user = User::factory()->create();
        $nearLocation = $this->location('Bar Nilo', 'Napoli', 40.8495000, 14.2569000);
        $farLocation = $this->location('Milano Centrale', 'Milano', 45.4863000, 9.2025000);

        $nearPost = $this->makePost($user, $nearLocation, 'Post vicino', now()->addDay());
        $this->makePost($user, $farLocation, 'Post lontano', now()->addDay());
        $this->makePost($user, $nearLocation, 'Post scaduto', now()->subMinute());

        $this
            ->actingAs($user, 'sanctum')
            ->getJson('/api/posts/nearby?lat=40.8518&lng=14.2681&radius_km=200')
            ->assertOk()
            ->assertJsonCount(1, 'data.posts')
            ->assertJsonPath('data.posts.0.id', $nearPost->id)
            ->assertJsonPath('data.posts.0.distance_km', 0.98);

        Carbon::setTestNow();
    }

    public function test_map_endpoint_returns_locations_and_posts(): void
    {
        Carbon::setTestNow('2026-07-09 12:00:00');

        $user = User::factory()->create();
        $location = $this->location('Piazza Plebiscito', 'Napoli', 40.8359000, 14.2488000);
        $post = $this->makePost($user, $location, 'Post da mappa', now()->addDay());

        $this
            ->actingAs($user, 'sanctum')
            ->getJson('/api/map?lat=40.8518&lng=14.2681&radius_km=200')
            ->assertOk()
            ->assertJsonPath('data.locations.0.id', $location->id)
            ->assertJsonPath('data.posts.0.id', $post->id)
            ->assertJsonPath('data.radius_km', 200);

        Carbon::setTestNow();
    }

    private function location(string $name, string $city, float $lat, float $lng): Location
    {
        return Location::query()->create([
            'name' => $name,
            'short' => $name,
            'city' => $city,
            'type' => 'altro',
            'latitude' => $lat,
            'longitude' => $lng,
            'geo_radius_meters' => 100,
            'is_active' => true,
        ]);
    }

    private function makePost(User $user, Location $location, string $text, Carbon $expiresAt): Post
    {
        return Post::query()->create([
            'author_id' => $user->id,
            'location_id' => $location->id,
            'text' => $text,
            'musica' => null,
            'sighting_date' => '2026-07-09',
            'expires_at' => $expiresAt,
            'status' => 'active',
        ]);
    }
}
