<?php

namespace Tests\Feature;

use App\Models\Chat;
use App\Models\Location;
use App\Models\Post;
use App\Models\User;
use App\Models\UserNotification;
use App\Support\Push\PushNotificationType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GhostChatApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_opening_chat_from_ghost_post_masks_owner_and_keeps_post_anonymous(): void
    {
        $owner = User::factory()->create([
            'display_name' => 'Autore Reale',
            'avatar_color' => '#ec4899',
            'avatar_url' => 'profile-photos/owner.jpg',
        ]);
        $viewer = User::factory()->create(['display_name' => 'Partecipante']);
        $post = $this->ghostPost($owner);

        $chatId = $this
            ->actingAs($viewer, 'sanctum')
            ->postJson('/api/chats/open', [
                'user_id' => $owner->id,
                'post_id' => $post->id,
            ])
            ->assertCreated()
            ->assertJsonPath('data.context_type', 'ghost_post')
            ->assertJsonPath('data.is_ghost', true)
            ->assertJsonPath('data.identity_revealed', false)
            ->assertJsonPath('data.can_reveal_identity', false)
            ->assertJsonPath('data.participant.id', null)
            ->assertJsonPath('data.participant.display_name', 'Ghost')
            ->assertJsonPath('data.participant.avatar_color', null)
            ->assertJsonPath('data.participant.avatar_url', null)
            ->assertJsonPath('data.participant.is_ghost', true)
            ->json('data.id');

        $this->assertTrue($post->fresh()->is_anonymous);
        $this->assertDatabaseHas('chats', [
            'id' => $chatId,
            'context_type' => Chat::CONTEXT_GHOST_POST,
            'origin_post_id' => $post->id,
            'ghost_owner_id' => $owner->id,
            'ghost_identity_revealed_at' => null,
        ]);

        $this
            ->actingAs($owner, 'sanctum')
            ->getJson('/api/chats')
            ->assertOk()
            ->assertJsonPath('data.0.can_reveal_identity', true)
            ->assertJsonPath('data.0.participant.id', $viewer->id)
            ->assertJsonPath('data.0.participant.display_name', 'Partecipante')
            ->assertJsonPath('data.0.participant.is_ghost', false);
    }

    public function test_ghost_chat_is_separate_from_direct_chat_for_same_pair(): void
    {
        $owner = User::factory()->create(['display_name' => 'Autore Reale']);
        $viewer = User::factory()->create();
        $post = $this->ghostPost($owner);

        $directChatId = $this
            ->actingAs($viewer, 'sanctum')
            ->postJson('/api/chats/open', ['user_id' => $owner->id])
            ->assertCreated()
            ->assertJsonPath('data.context_type', 'direct')
            ->assertJsonPath('data.participant.id', $owner->id)
            ->json('data.id');

        $this
            ->actingAs($viewer, 'sanctum')
            ->postJson("/api/chats/{$directChatId}/messages", ['text' => 'Messaggio direct'])
            ->assertCreated();

        $ghostChatId = $this
            ->actingAs($viewer, 'sanctum')
            ->postJson('/api/chats/open', [
                'user_id' => $owner->id,
                'post_id' => $post->id,
            ])
            ->assertCreated()
            ->assertJsonPath('data.context_type', 'ghost_post')
            ->json('data.id');

        $this->assertNotSame($directChatId, $ghostChatId);
        $this->assertDatabaseCount('chats', 2);
        $this->assertDatabaseHas('chats', [
            'id' => $directChatId,
            'conversation_key' => Chat::directConversationKey($owner->id, $viewer->id),
        ]);
        $this->assertDatabaseHas('chats', [
            'id' => $ghostChatId,
            'conversation_key' => Chat::ghostPostConversationKey($post->id, $owner->id, $viewer->id),
        ]);

        $this
            ->actingAs($viewer, 'sanctum')
            ->getJson("/api/chats/{$ghostChatId}/messages")
            ->assertOk()
            ->assertJsonPath('meta.total', 0);

        $this
            ->actingAs($viewer, 'sanctum')
            ->getJson("/api/chats/{$directChatId}/messages")
            ->assertOk()
            ->assertJsonPath('data.0.text', 'Messaggio direct');
    }

    public function test_ghost_sender_is_masked_in_messages_and_notification_before_reveal(): void
    {
        $owner = User::factory()->create(['display_name' => 'Nome Segreto']);
        $viewer = User::factory()->create(['display_name' => 'Sara']);
        $post = $this->ghostPost($owner);
        $chatId = $this->openGhostChat($viewer, $owner, $post);

        $this
            ->actingAs($owner, 'sanctum')
            ->postJson("/api/chats/{$chatId}/messages", ['text' => 'Ciao da Ghost'])
            ->assertCreated()
            ->assertJsonPath('data.sender.id', $owner->id)
            ->assertJsonPath('data.sender.display_name', 'Nome Segreto');

        $this
            ->actingAs($viewer, 'sanctum')
            ->getJson("/api/chats/{$chatId}/messages")
            ->assertOk()
            ->assertJsonPath('data.0.sender.id', null)
            ->assertJsonPath('data.0.sender.display_name', 'Ghost')
            ->assertJsonPath('data.0.sender.avatar_url', null)
            ->assertJsonPath('data.0.sender.is_ghost', true);

        $this
            ->actingAs($viewer, 'sanctum')
            ->getJson('/api/chats')
            ->assertOk()
            ->assertJsonPath('data.0.last_message.sender.id', null)
            ->assertJsonPath('data.0.last_message.sender.display_name', 'Ghost');

        $notification = UserNotification::query()
            ->where('user_id', $viewer->id)
            ->where('type', PushNotificationType::NEW_MESSAGE)
            ->firstOrFail();

        $this->assertSame('Ghost ti ha scritto su SpotOn.', $notification->body);
        $this->assertArrayNotHasKey('sender_id', $notification->data);
        $this->assertStringNotContainsString($owner->id, json_encode($notification->data, JSON_THROW_ON_ERROR));
        $this->assertStringNotContainsString('Nome Segreto', json_encode($notification->toArray(), JSON_THROW_ON_ERROR));

        $this
            ->actingAs($viewer, 'sanctum')
            ->postJson("/api/chats/{$chatId}/messages", ['text' => 'Ciao Ghost'])
            ->assertCreated();

        $messages = $this
            ->actingAs($owner, 'sanctum')
            ->getJson("/api/chats/{$chatId}/messages")
            ->assertOk()
            ->json('data');

        $reply = collect($messages)->firstWhere('text', 'Ciao Ghost');

        $this->assertSame($viewer->id, data_get($reply, 'sender.id'));
        $this->assertSame('Sara', data_get($reply, 'sender.display_name'));
    }

    public function test_only_ghost_owner_can_reveal_identity_and_operation_is_idempotent(): void
    {
        $owner = User::factory()->create([
            'display_name' => 'Autore Rivelato',
            'avatar_url' => 'profile-photos/revealed.jpg',
        ]);
        $viewer = User::factory()->create();
        $stranger = User::factory()->create();
        $post = $this->ghostPost($owner);
        $chatId = $this->openGhostChat($viewer, $owner, $post);

        $this
            ->actingAs($owner, 'sanctum')
            ->postJson("/api/chats/{$chatId}/messages", ['text' => 'Messaggio prima del reveal'])
            ->assertCreated();

        $this
            ->actingAs($viewer, 'sanctum')
            ->postJson("/api/chats/{$chatId}/reveal-identity")
            ->assertForbidden();

        $this
            ->actingAs($stranger, 'sanctum')
            ->postJson("/api/chats/{$chatId}/reveal-identity")
            ->assertForbidden();

        $this
            ->actingAs($owner, 'sanctum')
            ->postJson("/api/chats/{$chatId}/reveal-identity")
            ->assertOk()
            ->assertJsonPath('data.identity_revealed', true)
            ->assertJsonPath('data.can_reveal_identity', false);

        $revealedAt = Chat::query()->findOrFail($chatId)->ghost_identity_revealed_at;
        $this->assertNotNull($revealedAt);

        $this
            ->actingAs($owner, 'sanctum')
            ->postJson("/api/chats/{$chatId}/reveal-identity")
            ->assertOk()
            ->assertJsonPath('data.identity_revealed', true);

        $this->assertTrue($revealedAt->equalTo(Chat::query()->findOrFail($chatId)->ghost_identity_revealed_at));
        $this->assertSame(
            1,
            UserNotification::query()
                ->where('user_id', $viewer->id)
                ->where('type', PushNotificationType::GHOST_IDENTITY_REVEALED)
                ->count(),
        );
        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $viewer->id,
            'type' => PushNotificationType::GHOST_IDENTITY_REVEALED,
        ]);

        $this
            ->actingAs($viewer, 'sanctum')
            ->getJson('/api/chats')
            ->assertOk()
            ->assertJsonPath('data.0.participant.id', $owner->id)
            ->assertJsonPath('data.0.participant.display_name', 'Autore Rivelato')
            ->assertJsonPath('data.0.participant.avatar_url', 'profile-photos/revealed.jpg')
            ->assertJsonPath('data.0.participant.is_ghost', false)
            ->assertJsonPath('data.0.last_message.sender.id', $owner->id)
            ->assertJsonPath('data.0.last_message.sender.display_name', 'Autore Rivelato');

        $this
            ->actingAs($viewer, 'sanctum')
            ->getJson("/api/chats/{$chatId}/messages")
            ->assertOk()
            ->assertJsonPath('data.0.sender.id', $owner->id)
            ->assertJsonPath('data.0.sender.display_name', 'Autore Rivelato')
            ->assertJsonPath('data.0.sender.is_ghost', false);

        $this->assertTrue($post->fresh()->is_anonymous);
    }

    public function test_direct_chat_cannot_reveal_identity(): void
    {
        $first = User::factory()->create();
        $second = User::factory()->create();

        $chatId = $this
            ->actingAs($first, 'sanctum')
            ->postJson('/api/chats/open', ['user_id' => $second->id])
            ->assertCreated()
            ->json('data.id');

        $this
            ->actingAs($first, 'sanctum')
            ->postJson("/api/chats/{$chatId}/reveal-identity")
            ->assertUnprocessable();
    }

    private function openGhostChat(User $viewer, User $owner, Post $post): string
    {
        return $this
            ->actingAs($viewer, 'sanctum')
            ->postJson('/api/chats/open', [
                'user_id' => $owner->id,
                'post_id' => $post->id,
            ])
            ->assertCreated()
            ->json('data.id');
    }

    private function ghostPost(User $owner): Post
    {
        return Post::query()->create([
            'author_id' => $owner->id,
            'location_id' => $this->location()->id,
            'text' => 'Post Ghost per chat',
            'sighting_date' => now()->toDateString(),
            'is_anonymous' => true,
            'expires_at' => now()->addDay(),
            'status' => 'active',
        ]);
    }

    private function location(): Location
    {
        return Location::query()->create([
            'name' => 'Locale Chat Ghost',
            'short' => 'Locale Chat Ghost',
            'city' => 'Napoli',
            'type' => 'bar',
            'latitude' => 40.8495000,
            'longitude' => 14.2569000,
            'geo_radius_meters' => 100,
            'is_active' => true,
        ]);
    }
}
