<?php

namespace Tests\Feature;

use App\Jobs\Push\SendExpoPushNotification;
use App\Models\Location;
use App\Models\Post;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\Push\PushNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class UserBlockingApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_block_list_and_unblock_idempotently_but_cannot_block_self(): void
    {
        $blocker = User::factory()->create(['display_name' => 'Luca']);
        $blocked = User::factory()->create(['display_name' => 'Sara']);

        $this->actingAs($blocker, 'sanctum')
            ->postJson("/api/users/{$blocker->id}/block")
            ->assertUnprocessable();

        foreach (range(1, 2) as $attempt) {
            $this->actingAs($blocker, 'sanctum')
                ->postJson("/api/users/{$blocked->id}/block")
                ->assertSuccessful()
                ->assertJsonPath('data.user.id', $blocked->id)
                ->assertJsonPath('data.user.display_name', 'Sara');
        }

        $this->assertDatabaseCount('user_blocks', 1);

        $blockId = $this->actingAs($blocker, 'sanctum')
            ->getJson('/api/users/me/blocked')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->json('data.0.id');

        $this->actingAs($blocker, 'sanctum')
            ->deleteJson("/api/users/me/blocked/{$blockId}")
            ->assertOk()
            ->assertJsonPath('data.unblocked', true);

        $this->assertDatabaseCount('user_blocks', 0);
    }

    public function test_block_is_enforced_in_both_directions_for_chat_comment_challenge_and_feed(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $post = $this->postBy($owner);

        $this->actingAs($owner, 'sanctum')
            ->postJson("/api/users/{$other->id}/block")
            ->assertSuccessful();

        $this->actingAs($other, 'sanctum')
            ->postJson('/api/chats/open', ['user_id' => $owner->id])
            ->assertForbidden();

        $this->actingAs($owner, 'sanctum')
            ->postJson('/api/chats/open', ['user_id' => $other->id])
            ->assertForbidden();

        $this->actingAs($other, 'sanctum')
            ->postJson("/api/posts/{$post->id}/comments", ['text' => 'Commento bloccato'])
            ->assertForbidden();

        $this->actingAs($other, 'sanctum')
            ->postJson('/api/challenges', [
                'post_id' => $post->id,
                'target_type' => 'post_author',
                'mode' => 'direct',
            ])
            ->assertForbidden();

        $this->actingAs($other, 'sanctum')
            ->getJson('/api/posts/feed')
            ->assertOk()
            ->assertJsonCount(0, 'data.posts');
    }

    public function test_block_prevents_existing_chat_messages_and_all_actor_notifications(): void
    {
        Queue::fake();
        $first = User::factory()->create();
        $second = User::factory()->create();
        $chatId = $this->actingAs($first, 'sanctum')
            ->postJson('/api/chats/open', ['user_id' => $second->id])
            ->assertCreated()
            ->json('data.id');

        $this->actingAs($first, 'sanctum')
            ->postJson("/api/users/{$second->id}/block")
            ->assertSuccessful();

        $this->actingAs($first, 'sanctum')
            ->postJson("/api/chats/{$chatId}/messages", ['text' => 'Non parte'])
            ->assertForbidden();

        $this->actingAs($second, 'sanctum')
            ->postJson("/api/chats/{$chatId}/messages", ['text' => 'Non parte nemmeno'])
            ->assertForbidden();

        $sent = app(PushNotificationService::class)->sendToUser(
            $first,
            'Titolo',
            'Corpo',
            ['type' => 'new_message'],
            $second,
        );

        $this->assertSame(0, $sent);
        $this->assertSame(0, UserNotification::query()->where('user_id', $first->id)->count());
        Queue::assertNotPushed(SendExpoPushNotification::class);
    }

    public function test_ghost_chat_can_be_blocked_without_revealing_identity(): void
    {
        $owner = User::factory()->create(['display_name' => 'Identita Segreta']);
        $viewer = User::factory()->create();
        $post = $this->postBy($owner, true);
        $chatId = $this->actingAs($viewer, 'sanctum')
            ->postJson('/api/chats/open', [
                'user_id' => $owner->id,
                'post_id' => $post->id,
            ])
            ->assertCreated()
            ->json('data.id');

        $this->actingAs($viewer, 'sanctum')
            ->postJson("/api/chats/{$chatId}/block-participant")
            ->assertSuccessful()
            ->assertJsonPath('data.identity_hidden', true)
            ->assertJsonPath('data.user.id', null)
            ->assertJsonPath('data.user.display_name', 'Utente Ghost')
            ->assertJsonMissing(['Identita Segreta', $owner->id]);

        $this->actingAs($viewer, 'sanctum')
            ->getJson('/api/users/me/blocked')
            ->assertOk()
            ->assertJsonPath('data.0.identity_hidden', true)
            ->assertJsonPath('data.0.user.id', null)
            ->assertJsonPath('data.0.user.display_name', 'Utente Ghost')
            ->assertJsonMissing(['Identita Segreta', $owner->id]);

        $this->actingAs($owner, 'sanctum')
            ->postJson("/api/chats/{$chatId}/reveal-identity")
            ->assertForbidden();

        $this->actingAs($owner, 'sanctum')
            ->postJson("/api/chats/{$chatId}/messages", ['text' => 'Tentativo Ghost'])
            ->assertForbidden();
    }

    private function postBy(User $author, bool $anonymous = false): Post
    {
        $location = Location::query()->firstOrCreate(
            ['name' => 'Luogo blocchi', 'city' => 'Napoli'],
            [
                'short' => 'Blocchi',
                'type' => 'altro',
                'latitude' => 40.8518,
                'longitude' => 14.2681,
                'geo_radius_meters' => 100,
                'icon' => 'location-outline',
                'is_active' => true,
            ],
        );

        return Post::query()->create([
            'author_id' => $author->id,
            'location_id' => $location->id,
            'text' => 'Post per test blocco utenti',
            'sighting_date' => now()->toDateString(),
            'is_anonymous' => $anonymous,
            'expires_at' => now()->addDay(),
            'status' => 'active',
        ]);
    }
}
