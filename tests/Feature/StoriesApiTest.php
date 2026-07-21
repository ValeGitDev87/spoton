<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class StoriesApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_location_stories_return_only_active_non_expired_posts_from_last_24_hours(): void
    {
        Carbon::setTestNow('2026-07-09 12:00:00');

        $user = User::factory()->create();
        $location = $this->location();
        $otherLocation = $this->location('Altro luogo');

        $first = $this->makePost($user, $location, 'Prima storia', now()->subHours(2), now()->addHours(20));
        $second = $this->makePost($user, $location, 'Seconda storia', now()->subHour(), now()->addHours(23));
        $this->makePost($user, $location, 'Storia scaduta', now()->subHours(3), now()->subMinute());
        $this->makePost($user, $location, 'Troppo vecchia', now()->subHours(25), now()->addHour());
        $this->makePost($user, $otherLocation, 'Altra location', now()->subHour(), now()->addHour());

        $this
            ->actingAs($user, 'sanctum')
            ->getJson("/api/locations/{$location->id}/stories")
            ->assertOk()
            ->assertJsonPath('data.location.id', $location->id)
            ->assertJsonPath('data.location.icon', 'location-outline')
            ->assertJsonPath('data.location.icon_library', 'ionicons')
            ->assertJsonPath('data.location.stories_count', 2)
            ->assertJsonCount(2, 'data.stories')
            ->assertJsonPath('data.stories.0.id', $first->id)
            ->assertJsonPath('data.stories.1.id', $second->id);

        Carbon::setTestNow();
    }

    public function test_location_stories_are_protected(): void
    {
        $location = $this->location();

        $this
            ->getJson("/api/locations/{$location->id}/stories")
            ->assertUnauthorized();
    }

    private function location(string $name = 'Metro Mergellina'): Location
    {
        return Location::query()->create([
            'name' => $name,
            'short' => $name,
            'city' => 'Napoli',
            'type' => 'metro',
            'latitude' => 40.8319000,
            'longitude' => 14.2193000,
            'geo_radius_meters' => 100,
            'is_active' => true,
        ]);
    }

    private function makePost(User $user, Location $location, string $text, Carbon $createdAt, Carbon $expiresAt): Post
    {
        $post = Post::query()->create([
            'author_id' => $user->id,
            'location_id' => $location->id,
            'text' => $text,
            'musica' => null,
            'sighting_date' => '2026-07-09',
            'expires_at' => $expiresAt,
            'status' => 'active',
        ]);

        $post->forceFill([
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ])->save();

        return $post;
    }
}
