<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class GlobalFeedApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_feed_prioritizes_ten_nearest_posts_without_hiding_the_others(): void
    {
        Carbon::setTestNow('2026-07-29 12:00:00');

        $viewer = User::factory()->create();
        $author = User::factory()->create();
        $nearestIds = [];

        foreach (range(1, 10) as $index) {
            $location = $this->location(
                "Napoli {$index}",
                40.8518 + ($index * 0.001),
                14.2681,
            );
            $nearestIds[] = $this->makePost(
                $author,
                $location,
                "Vicino {$index}",
                now()->subHours(20 + $index),
            )->id;
        }

        $milan = $this->location('Milano', 45.4642, 9.1900);
        $farRecent = $this->makePost($author, $milan, 'Lontano ma recente', now()->subMinute());
        $rome = $this->location('Roma', 41.9028, 12.4964);
        $farOlder = $this->makePost($author, $rome, 'Lontano meno recente', now()->subMinutes(10));
        $this->makePost($author, $this->location('Scaduto', 40.8520, 14.2681), 'Scaduto', now(), now()->subMinute());

        $firstPage = $this
            ->actingAs($viewer, 'sanctum')
            ->getJson('/api/posts/feed?lat=40.8518&lng=14.2681&per_page=10&page=1')
            ->assertOk()
            ->assertJsonCount(10, 'data.posts')
            ->assertJsonPath('data.nearest_priority_count', 10)
            ->assertJsonPath('meta.total', 12)
            ->json('data.posts');

        $this->assertSame($nearestIds, collect($firstPage)->pluck('id')->all());

        $this
            ->actingAs($viewer, 'sanctum')
            ->getJson('/api/posts/feed?lat=40.8518&lng=14.2681&per_page=10&page=2')
            ->assertOk()
            ->assertJsonPath('data.posts.0.id', $farRecent->id)
            ->assertJsonPath('data.posts.1.id', $farOlder->id);

        Carbon::setTestNow();
    }

    public function test_feed_without_coordinates_still_returns_every_active_post_by_date(): void
    {
        Carbon::setTestNow('2026-07-29 12:00:00');

        $viewer = User::factory()->create();
        $author = User::factory()->create();
        $naples = $this->location('Napoli', 40.8518, 14.2681);
        $milan = $this->location('Milano', 45.4642, 9.1900);
        $older = $this->makePost($author, $naples, 'Vecchio', now()->subHour());
        $newer = $this->makePost($author, $milan, 'Nuovo', now()->subMinute());

        $this
            ->actingAs($viewer, 'sanctum')
            ->getJson('/api/posts/feed')
            ->assertOk()
            ->assertJsonPath('data.origin', null)
            ->assertJsonPath('data.nearest_priority_count', 0)
            ->assertJsonPath('data.posts.0.id', $newer->id)
            ->assertJsonPath('data.posts.1.id', $older->id);

        Carbon::setTestNow();
    }

    public function test_story_feed_is_global_and_paginated(): void
    {
        Carbon::setTestNow('2026-07-29 12:00:00');

        $viewer = User::factory()->create();
        $author = User::factory()->create();

        foreach (range(1, 12) as $index) {
            $location = $this->location(
                "Luogo {$index}",
                $index <= 10 ? 40.8518 + ($index * 0.001) : 45.4642 + ($index * 0.001),
                $index <= 10 ? 14.2681 : 9.1900,
            );
            $this->makePost($author, $location, "Story {$index}", now()->subMinutes($index));
        }

        $this
            ->actingAs($viewer, 'sanctum')
            ->getJson('/api/locations/story-feed?lat=40.8518&lng=14.2681&per_page=10&page=1')
            ->assertOk()
            ->assertJsonCount(10, 'data.locations')
            ->assertJsonPath('data.nearest_priority_count', 10)
            ->assertJsonPath('meta.total', 12)
            ->assertJsonPath('meta.last_page', 2);

        $this
            ->actingAs($viewer, 'sanctum')
            ->getJson('/api/locations/story-feed?lat=40.8518&lng=14.2681&per_page=10&page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data.locations')
            ->assertJsonPath('data.locations.0.city', 'Test');

        Carbon::setTestNow();
    }

    private function location(string $name, float $lat, float $lng): Location
    {
        return Location::query()->create([
            'name' => $name,
            'short' => $name,
            'city' => 'Test',
            'type' => 'altro',
            'latitude' => $lat,
            'longitude' => $lng,
            'geo_radius_meters' => 100,
            'is_active' => true,
        ]);
    }

    private function makePost(
        User $author,
        Location $location,
        string $text,
        Carbon $createdAt,
        ?Carbon $expiresAt = null,
    ): Post {
        $post = Post::query()->create([
            'author_id' => $author->id,
            'location_id' => $location->id,
            'text' => $text,
            'sighting_date' => now()->toDateString(),
            'expires_at' => $expiresAt ?? now()->addDay(),
            'status' => 'active',
        ]);
        $post->forceFill([
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ])->saveQuietly();

        return $post;
    }
}
