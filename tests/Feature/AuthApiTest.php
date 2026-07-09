<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_receive_token(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'display_name' => 'Valentino',
            'email' => 'valentino@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('message', 'OK')
            ->assertJsonStructure([
                'data' => [
                    'user' => ['id', 'email', 'display_name', 'avatar_color'],
                    'token',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'valentino@example.com',
            'display_name' => 'Valentino',
        ]);
    }

    public function test_user_can_login_and_read_me(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'display_name' => 'Test User',
        ]);

        $login = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $token = $login->json('data.token');

        $login->assertOk();
        $this->assertNotEmpty($token);

        $this
            ->withToken($token)
            ->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('data.user.email', 'test@example.com')
            ->assertJsonPath('data.user.display_name', 'Test User');
    }
}
