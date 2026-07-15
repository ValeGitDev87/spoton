<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_me_returns_profile_fields(): void
    {
        $user = User::factory()->create([
            'avatar_url' => 'https://cdn.example.test/avatar.jpg',
            'bio' => 'Bio test',
            'photos' => ['https://cdn.example.test/one.jpg'],
            'karma' => 7,
            'auth_provider' => 'email',
        ]);

        $this
            ->actingAs($user, 'sanctum')
            ->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('data.user.avatar_url', 'https://cdn.example.test/avatar.jpg')
            ->assertJsonPath('data.user.bio', 'Bio test')
            ->assertJsonPath('data.user.photos.0', 'https://cdn.example.test/one.jpg')
            ->assertJsonPath('data.user.karma', 7)
            ->assertJsonPath('data.user.auth_provider', 'email');
    }
}
