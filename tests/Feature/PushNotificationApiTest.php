<?php

namespace Tests\Feature;

use App\Contracts\PushGateway;
use App\Jobs\Push\SendExpoPushNotification;
use App\Models\Location;
use App\Models\Post;
use App\Models\PushToken;
use App\Models\User;
use App\Services\Push\ExpoPushGateway;
use App\Services\Push\LogPushGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PushNotificationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_upsert_and_revoke_push_token_by_device(): void
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user, 'sanctum')
            ->putJson('/api/me/push-tokens/iphone-15', [
                'token' => 'ExponentPushToken[token_one]',
                'platform' => 'ios',
                'app_version' => '1.0.0',
                'locale' => 'it-IT',
                'timezone' => 'Europe/Rome',
            ])
            ->assertOk()
            ->assertJsonPath('data.device_id', 'iphone-15')
            ->assertJsonMissingPath('data.token');

        $this
            ->actingAs($user, 'sanctum')
            ->putJson('/api/me/push-tokens/iphone-15', [
                'token' => 'ExponentPushToken[token_two]',
                'platform' => 'ios',
            ])
            ->assertOk()
            ->assertJsonPath('data.is_active', true);

        $this->assertDatabaseHas('push_tokens', [
            'user_id' => $user->id,
            'device_id' => 'iphone-15',
            'token_hash' => PushToken::hashToken('ExponentPushToken[token_two]'),
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('push_tokens', [
            'token_hash' => PushToken::hashToken('ExponentPushToken[token_one]'),
            'is_active' => false,
        ]);

        $this
            ->actingAs($user, 'sanctum')
            ->deleteJson('/api/me/push-tokens/iphone-15')
            ->assertOk()
            ->assertJsonPath('data.revoked', true);

        $this->assertDatabaseHas('push_tokens', [
            'token_hash' => PushToken::hashToken('ExponentPushToken[token_two]'),
            'is_active' => false,
        ]);
    }

    public function test_same_expo_token_is_moved_to_latest_user_without_leaking_token(): void
    {
        $first = User::factory()->create();
        $second = User::factory()->create();

        $this
            ->actingAs($first, 'sanctum')
            ->putJson('/api/me/push-tokens/device-a', [
                'token' => 'ExponentPushToken[shared_token]',
                'platform' => 'android',
            ])
            ->assertOk();

        $this
            ->actingAs($second, 'sanctum')
            ->putJson('/api/me/push-tokens/device-b', [
                'token' => 'ExponentPushToken[shared_token]',
                'platform' => 'android',
            ])
            ->assertOk()
            ->assertJsonMissingPath('data.token');

        $this->assertDatabaseHas('push_tokens', [
            'token_hash' => PushToken::hashToken('ExponentPushToken[shared_token]'),
            'user_id' => $second->id,
            'device_id' => 'device-b',
            'is_active' => true,
        ]);
        $this->assertDatabaseCount('push_tokens', 1);
    }

    public function test_dev_push_endpoint_queues_notification_on_notifications_queue(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        PushToken::query()->create([
            'user_id' => $user->id,
            'token' => 'ExponentPushToken[test_token]',
            'token_hash' => PushToken::hashToken('ExponentPushToken[test_token]'),
            'device_id' => 'device-a',
            'platform' => 'ios',
            'is_active' => true,
            'last_seen_at' => now(),
        ]);

        $this
            ->actingAs($user, 'sanctum')
            ->postJson('/api/dev/push/test', [
                'title' => 'Test',
                'body' => 'Messaggio test',
            ])
            ->assertOk()
            ->assertJsonPath('data.queued', true)
            ->assertJsonPath('data.recipients', 1);

        Queue::assertPushedOn('notifications', SendExpoPushNotification::class);
    }

    public function test_tagged_comment_queues_push_to_tagged_user(): void
    {
        Queue::fake();

        $author = User::factory()->create(['display_name' => 'Valentino']);
        $target = User::factory()->create(['display_name' => 'Sara']);
        $post = $this->makePost(User::factory()->create());
        $this->activeTokenFor($target);

        $this
            ->actingAs($author, 'sanctum')
            ->postJson('/api/favorites', ['target_name' => 'Sara'])
            ->assertCreated();

        $this
            ->actingAs($author, 'sanctum')
            ->postJson("/api/posts/{$post->id}/comments", ['text' => 'Ciao @Sara'])
            ->assertCreated();

        Queue::assertPushedOn('notifications', SendExpoPushNotification::class);
    }

    public function test_inverted_challenge_queues_push_to_target_user(): void
    {
        Queue::fake();

        $postOwner = User::factory()->create();
        $target = User::factory()->create();
        $challenger = User::factory()->create(['display_name' => 'Valentino']);
        $post = $this->makePost($postOwner);
        $comment = $post->comments()->create([
            'author_id' => $target->id,
            'text' => 'Ero li anche io.',
        ]);
        $this->activeTokenFor($target);

        $this
            ->actingAs($challenger, 'sanctum')
            ->postJson('/api/challenges', [
                'post_id' => $post->id,
                'target_type' => 'comment_author',
                'source_comment_id' => $comment->id,
                'mode' => 'question',
                'question' => 'Che cosa avevi ordinato?',
                'answer' => 'caffe',
            ])
            ->assertCreated();

        Queue::assertPushedOn('notifications', SendExpoPushNotification::class);
    }

    public function test_log_gateway_logs_only_sanitized_recipient_hashes(): void
    {
        Log::spy();

        $token = 'ExponentPushToken[super_secret_token]';
        $gateway = new LogPushGateway;

        $gateway->send([
            ['token' => $token, 'token_hash' => PushToken::hashToken($token)],
        ], 'Titolo', 'Corpo', ['type' => 'test']);

        Log::shouldHaveReceived('info')
            ->once()
            ->with('spoton.push.log', \Mockery::on(function (array $context) use ($token): bool {
                return $context['recipient_count'] === 1
                    && $context['recipient_hashes'] !== []
                    && ! str_contains(json_encode($context, JSON_THROW_ON_ERROR), $token);
            }));
    }

    public function test_expo_gateway_sends_batches_and_job_deactivates_device_not_registered_tokens(): void
    {
        Http::fake([
            'https://exp.host/*' => Http::response([
                'data' => [
                    ['status' => 'error', 'details' => ['error' => 'DeviceNotRegistered']],
                ],
            ]),
        ]);

        $user = User::factory()->create();
        $pushToken = PushToken::query()->create([
            'user_id' => $user->id,
            'token' => 'ExponentPushToken[expired_token]',
            'token_hash' => PushToken::hashToken('ExponentPushToken[expired_token]'),
            'device_id' => 'device-a',
            'platform' => 'ios',
            'is_active' => true,
            'last_seen_at' => now(),
        ]);

        app()->instance(PushGateway::class, new ExpoPushGateway);

        (new SendExpoPushNotification([$pushToken->id], 'Titolo', 'Corpo'))->handle(app(PushGateway::class));

        $this->assertFalse($pushToken->fresh()->is_active);
        Http::assertSent(fn ($request): bool => $request->url() === 'https://exp.host/--/api/v2/push/send'
            && $request[0]['to'] === 'ExponentPushToken[expired_token]');
    }

    private function activeTokenFor(User $user): PushToken
    {
        return PushToken::query()->create([
            'user_id' => $user->id,
            'token' => 'ExponentPushToken['.$user->id.']',
            'token_hash' => PushToken::hashToken('ExponentPushToken['.$user->id.']'),
            'device_id' => 'device-'.$user->id,
            'platform' => 'ios',
            'is_active' => true,
            'last_seen_at' => now(),
        ]);
    }

    private function makePost(User $owner): Post
    {
        return Post::query()->create([
            'author_id' => $owner->id,
            'location_id' => $this->location()->id,
            'text' => 'Post push',
            'sighting_date' => now()->toDateString(),
            'expires_at' => now()->addDay(),
            'status' => 'active',
        ]);
    }

    private function location(): Location
    {
        return Location::query()->create([
            'name' => 'Locale Push',
            'short' => 'Locale Push',
            'city' => 'Napoli',
            'type' => 'bar',
            'latitude' => 40.8495000,
            'longitude' => 14.2569000,
            'geo_radius_meters' => 100,
            'is_active' => true,
        ]);
    }
}
