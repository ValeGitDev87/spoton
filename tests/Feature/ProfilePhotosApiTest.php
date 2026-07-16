<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfilePhotosApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_read_karma_and_manage_photo_urls(): void
    {
        $user = User::factory()->create(['karma' => 4, 'photos' => []]);

        $this
            ->actingAs($user, 'sanctum')
            ->getJson('/api/users/me/karma')
            ->assertOk()
            ->assertJsonPath('data.karma', 4);

        $this
            ->actingAs($user, 'sanctum')
            ->postJson('/api/users/me/photos', ['photo_url' => 'https://cdn.example.test/photo-1.jpg'])
            ->assertCreated()
            ->assertJsonPath('data.photos.0.id', 0)
            ->assertJsonPath('data.photos.0.url', 'https://cdn.example.test/photo-1.jpg');

        $this
            ->actingAs($user->fresh(), 'sanctum')
            ->deleteJson('/api/users/me/photos/0')
            ->assertOk()
            ->assertJsonPath('data.removed', true)
            ->assertJsonCount(0, 'data.photos');
    }

    public function test_photos_are_limited_to_ten(): void
    {
        $user = User::factory()->create([
            'photos' => collect(range(1, 10))
                ->map(fn (int $index) => "https://cdn.example.test/photo-{$index}.jpg")
                ->all(),
        ]);

        $this
            ->actingAs($user, 'sanctum')
            ->postJson('/api/users/me/photos', ['photo_url' => 'https://cdn.example.test/photo-11.jpg'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['photo']);
    }
}
