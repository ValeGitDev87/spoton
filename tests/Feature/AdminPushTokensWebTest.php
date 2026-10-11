<?php

namespace Tests\Feature;

use App\Models\PushToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPushTokensWebTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_filter_tokens_and_open_all_details(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create([
            'display_name' => 'Lucia Bianchi',
            'email' => 'lucia@example.com',
        ]);
        $rawToken = 'ExpoPushToken[private_ios_token]';
        $iosToken = PushToken::query()->create([
            'user_id' => $user->id,
            'token' => $rawToken,
            'token_hash' => PushToken::hashToken($rawToken),
            'device_id' => 'iphone-lucia',
            'platform' => 'ios',
            'app_version' => '1.5.0',
            'locale' => 'it-IT',
            'timezone' => 'Europe/Rome',
            'is_active' => true,
            'last_seen_at' => now(),
        ]);
        $androidToken = 'ExpoPushToken[old_android_token]';
        PushToken::query()->create([
            'user_id' => $user->id,
            'token' => $androidToken,
            'token_hash' => PushToken::hashToken($androidToken),
            'device_id' => 'android-lucia',
            'platform' => 'android',
            'is_active' => false,
            'revoked_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get('/admin/push-tokens?search=lucia%40example.com&platform=ios&status=active')
            ->assertOk()
            ->assertSee('Lucia Bianchi')
            ->assertSee('iphone-lucia')
            ->assertSee('1.5.0')
            ->assertSee(route('admin.push-tokens.show', $iosToken))
            ->assertDontSee('android-lucia')
            ->assertDontSee($rawToken);

        $this->actingAs($admin)
            ->get(route('admin.push-tokens.show', $iosToken))
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertSee($iosToken->id)
            ->assertSee($user->id)
            ->assertSee($rawToken)
            ->assertSee($iosToken->token_hash)
            ->assertSee('it-IT')
            ->assertSee('Europe/Rome');
    }

    public function test_admin_token_list_is_paginated(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        for ($index = 0; $index < 21; $index++) {
            $rawToken = "ExpoPushToken[dashboard_token_{$index}]";
            PushToken::query()->create([
                'user_id' => $admin->id,
                'token' => $rawToken,
                'token_hash' => PushToken::hashToken($rawToken),
                'device_id' => "dashboard-device-{$index}",
                'platform' => 'android',
                'is_active' => $index % 2 === 0,
            ]);
        }

        $this->actingAs($admin)
            ->get('/admin/push-tokens')
            ->assertOk()
            ->assertSee('Token salvati')
            ->assertSee('page=2');

        $this->actingAs($admin)
            ->get('/admin/push-tokens?page=2')
            ->assertOk();
    }

    public function test_only_admin_can_view_token_pages(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $rawToken = 'ExpoPushToken[restricted_token]';
        $token = PushToken::query()->create([
            'user_id' => $user->id,
            'token' => $rawToken,
            'token_hash' => PushToken::hashToken($rawToken),
            'device_id' => 'restricted-device',
            'platform' => 'ios',
        ]);

        $this->actingAs($user)->get('/admin/push-tokens')->assertForbidden();
        $this->actingAs($user)->get(route('admin.push-tokens.show', $token))->assertForbidden();
    }
}
