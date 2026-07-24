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

    public function test_user_can_update_public_profile_fields_only(): void
    {
        $user = User::factory()->create([
            'email' => 'original@example.com',
            'karma' => 4,
        ]);

        $this
            ->actingAs($user, 'sanctum')
            ->patchJson('/api/me', [
                'display_name' => 'Nuovo Nome',
                'bio' => 'Una nuova bio.',
                'avatar_color' => '#12ABEF',
                'avatar_url' => 'https://cdn.example.test/avatar-new.jpg',
                'email' => 'changed@example.com',
                'karma' => 999,
                'is_admin' => true,
            ])
            ->assertOk()
            ->assertJsonPath('data.user.display_name', 'Nuovo Nome')
            ->assertJsonPath('data.user.bio', 'Una nuova bio.')
            ->assertJsonPath('data.user.avatar_color', '#12ABEF')
            ->assertJsonPath('data.user.email', 'original@example.com')
            ->assertJsonPath('data.user.karma', 4)
            ->assertJsonPath('data.user.is_admin', false);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => 'original@example.com',
            'display_name' => 'Nuovo Nome',
            'karma' => 4,
            'is_admin' => false,
        ]);
    }

    public function test_user_can_use_an_uploaded_local_photo_as_avatar(): void
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user, 'sanctum')
            ->patchJson('/api/me', [
                'avatar_url' => '/storage/profile-photos/avatar.jpg',
            ])
            ->assertOk()
            ->assertJsonPath(
                'data.user.avatar_url',
                '/storage/profile-photos/avatar.jpg',
            );
    }

    public function test_suspended_user_cannot_login_or_use_existing_authentication(): void
    {
        $user = User::factory()->create([
            'email' => 'suspended@example.com',
            'password' => 'password123',
            'is_suspended' => true,
        ]);

        $this
            ->postJson('/api/auth/login', [
                'email' => 'suspended@example.com',
                'password' => 'password123',
            ])
            ->assertForbidden();

        $this
            ->actingAs($user, 'sanctum')
            ->getJson('/api/me')
            ->assertForbidden();
    }
}
