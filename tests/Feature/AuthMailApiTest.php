<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\Auth\PasswordChangedNotification;
use App\Notifications\Auth\ResetPasswordNotification;
use App\Notifications\Auth\VerifyEmailNotification;
use App\Notifications\Auth\WelcomeNotification;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AuthMailApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_dispatches_registered_event_and_verification_email(): void
    {
        Event::fake([Registered::class]);
        Notification::fake();

        $this
            ->postJson('/api/auth/register', [
                'display_name' => 'Valentino',
                'email' => 'vale@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->assertCreated()
            ->assertJsonPath('data.user.email_verified', false);

        $user = User::query()->where('email', 'vale@example.com')->firstOrFail();

        Event::assertDispatched(Registered::class);
        Notification::assertSentTo($user, VerifyEmailNotification::class);
    }

    public function test_signed_email_verification_marks_user_verified_and_sends_welcome_once(): void
    {
        Notification::fake();

        $user = User::factory()->unverified()->create();
        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)],
        );

        $this->get($url)->assertOk();

        $this->assertNotNull($user->fresh()->email_verified_at);
        Notification::assertSentTo($user->fresh(), WelcomeNotification::class);

        $this->get($url)->assertOk();

        $this->assertDatabaseCount('users', 1);
        Notification::assertSentToTimes($user->fresh(), WelcomeNotification::class, 1);
    }

    public function test_verification_resend_is_idempotent_for_verified_users(): void
    {
        Notification::fake();

        $verified = User::factory()->create();
        $unverified = User::factory()->unverified()->create();

        $this
            ->actingAs($verified, 'sanctum')
            ->postJson('/api/auth/email/verification-notification')
            ->assertOk()
            ->assertJsonPath('data.email_verified', true);

        Notification::assertNothingSent();

        $this
            ->actingAs($unverified, 'sanctum')
            ->postJson('/api/auth/email/verification-notification')
            ->assertOk()
            ->assertJsonPath('data.email_verified', false);

        Notification::assertSentTo($unverified, VerifyEmailNotification::class);
    }

    public function test_forgot_password_is_generic_and_sends_reset_only_to_existing_users(): void
    {
        Notification::fake();

        $user = User::factory()->create(['email' => 'reset@example.com']);

        $this
            ->postJson('/api/auth/forgot-password', ['email' => 'reset@example.com'])
            ->assertOk();

        $this
            ->postJson('/api/auth/forgot-password', ['email' => 'missing@example.com'])
            ->assertOk();

        Notification::assertSentTo($user, ResetPasswordNotification::class);
        Notification::assertCount(1);
    }

    public function test_password_reset_changes_password_revokes_tokens_and_token_cannot_be_reused(): void
    {
        $user = User::factory()->create([
            'email' => 'change@example.com',
            'password' => Hash::make('old-password'),
        ]);
        $user->createToken('mobile');
        $token = Password::createToken($user);

        $this
            ->postJson('/api/auth/reset-password', [
                'email' => 'change@example.com',
                'token' => $token,
                'password' => 'new-password123',
                'password_confirmation' => 'new-password123',
            ])
            ->assertOk();

        $this->assertTrue(Hash::check('new-password123', $user->fresh()->password));
        $this->assertNotNull($user->fresh()->password_changed_at);
        $this->assertDatabaseCount('personal_access_tokens', 0);

        $this
            ->postJson('/api/auth/reset-password', [
                'email' => 'change@example.com',
                'token' => $token,
                'password' => 'another-password123',
                'password_confirmation' => 'another-password123',
            ])
            ->assertUnprocessable();
    }

    public function test_authenticated_user_can_change_password_and_keep_current_token_only(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'password' => Hash::make('current-password'),
        ]);
        $currentToken = $user->createToken('current');
        $otherToken = $user->createToken('other');

        $this
            ->withToken($currentToken->plainTextToken)
            ->patchJson('/api/auth/password', [
                'current_password' => 'wrong-password',
                'password' => 'new-password123',
                'password_confirmation' => 'new-password123',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['current_password']);

        $this
            ->withToken($currentToken->plainTextToken)
            ->patchJson('/api/auth/password', [
                'current_password' => 'current-password',
                'password' => 'new-password123',
                'password_confirmation' => 'new-password123',
            ])
            ->assertOk();

        $this->assertTrue(Hash::check('new-password123', $user->fresh()->password));
        $this->assertDatabaseHas('personal_access_tokens', ['id' => $currentToken->accessToken->id]);
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $otherToken->accessToken->id]);
        Notification::assertSentTo($user, PasswordChangedNotification::class);
    }
}
