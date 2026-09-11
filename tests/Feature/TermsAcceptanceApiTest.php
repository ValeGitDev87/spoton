<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TermsAcceptanceApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_requires_and_persists_current_terms_acceptance(): void
    {
        $payload = [
            'display_name' => 'Nuovo Utente',
            'email' => 'nuovo@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $this->postJson('/api/auth/register', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('terms_accepted');

        $this->postJson('/api/auth/register', $payload + ['terms_accepted' => true])
            ->assertCreated()
            ->assertJsonPath('data.user.terms_accepted', true)
            ->assertJsonPath('data.user.terms_version', config('spoton.terms.version'));

        $user = User::query()->where('email', 'nuovo@example.com')->firstOrFail();
        $this->assertNotNull($user->terms_accepted_at);
        $this->assertSame(config('spoton.terms.version'), $user->terms_version);
    }

    public function test_legacy_user_can_access_account_and_accept_terms_before_using_app(): void
    {
        $legacy = User::factory()->create([
            'email' => 'legacy@example.com',
            'password' => Hash::make('password123'),
            'terms_accepted_at' => null,
            'terms_version' => null,
        ]);

        $login = $this->postJson('/api/auth/login', [
            'email' => 'legacy@example.com',
            'password' => 'password123',
        ])->assertOk()->assertJsonPath('data.user.terms_accepted', false);
        $token = $login->json('data.token');

        $this->withToken($token)->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('data.user.terms_accepted', false);

        $this->withToken($token)->getJson('/api/posts/feed')
            ->assertForbidden()
            ->assertJsonPath('data.code', 'terms_acceptance_required');

        $this->withToken($token)->postJson('/api/me/terms', ['accepted' => false])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('accepted');

        $this->withToken($token)->postJson('/api/me/terms', ['accepted' => true])
            ->assertOk()
            ->assertJsonPath('data.user.terms_accepted', true);

        $this->withToken($token)->getJson('/api/posts/feed')->assertOk();
        $this->assertNotNull($legacy->fresh()->terms_accepted_at);
    }

    public function test_terms_are_publicly_available(): void
    {
        $this->get('/terms')
            ->assertOk()
            ->assertSee('Termini di utilizzo ed EULA')
            ->assertSee('18+')
            ->assertSee('Tolleranza zero', false);
    }
}
