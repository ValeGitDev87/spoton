<?php

namespace Tests\Feature;

use App\Models\Chat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_open_chat_and_send_message(): void
    {
        $user = User::factory()->create([
            'avatar_url' => 'profile-photos/utente.jpg',
        ]);
        $other = User::factory()->create([
            'display_name' => 'Altra Persona',
            'avatar_url' => 'profile-photos/altra-persona.jpg',
        ]);

        $chatId = $this
            ->actingAs($user, 'sanctum')
            ->postJson('/api/chats/open', ['user_id' => $other->id])
            ->assertCreated()
            ->assertJsonPath('data.participant.id', $other->id)
            ->assertJsonPath('data.participant.avatar_url', 'profile-photos/altra-persona.jpg')
            ->json('data.id');

        $this
            ->actingAs($user, 'sanctum')
            ->postJson("/api/chats/{$chatId}/messages", ['text' => 'Ciao!'])
            ->assertCreated()
            ->assertJsonPath('data.text', 'Ciao!')
            ->assertJsonPath('data.sender.avatar_url', 'profile-photos/utente.jpg');

        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $other->id,
            'type' => 'new_message',
        ]);

        $this
            ->actingAs($other, 'sanctum')
            ->getJson('/api/chats')
            ->assertOk()
            ->assertJsonPath('data.0.unread_count', 1);

        $this
            ->actingAs($other, 'sanctum')
            ->getJson("/api/chats/{$chatId}/messages")
            ->assertOk()
            ->assertJsonPath('data.0.text', 'Ciao!');

        $this
            ->actingAs($other, 'sanctum')
            ->getJson('/api/chats')
            ->assertOk()
            ->assertJsonPath('data.0.unread_count', 0);
    }

    public function test_non_participant_cannot_read_messages(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $stranger = User::factory()->create();
        [$one, $two] = Chat::sortedPair($user->id, $other->id);
        $chat = Chat::query()->create([
            'user_one_id' => $one,
            'user_two_id' => $two,
        ]);

        $this
            ->actingAs($stranger, 'sanctum')
            ->getJson("/api/chats/{$chat->id}/messages")
            ->assertForbidden();
    }

    public function test_chat_list_returns_latest_message(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        [$one, $two] = Chat::sortedPair($user->id, $other->id);
        $chat = Chat::query()->create([
            'user_one_id' => $one,
            'user_two_id' => $two,
        ]);

        $chat->messages()->create([
            'sender_id' => $user->id,
            'text' => 'Primo messaggio',
            'sent_at' => now()->subMinute(),
        ]);
        $chat->messages()->create([
            'sender_id' => $other->id,
            'text' => 'Ultimo messaggio',
            'sent_at' => now(),
        ]);

        $this
            ->actingAs($user, 'sanctum')
            ->getJson('/api/chats')
            ->assertOk()
            ->assertJsonPath('data.0.last_message.text', 'Ultimo messaggio');
    }

    public function test_first_messages_page_contains_the_latest_messages_in_display_order(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        [$one, $two] = Chat::sortedPair($user->id, $other->id);
        $chat = Chat::query()->create([
            'user_one_id' => $one,
            'user_two_id' => $two,
        ]);

        foreach (range(1, 55) as $index) {
            $chat->messages()->create([
                'sender_id' => $index % 2 === 0 ? $user->id : $other->id,
                'text' => "Messaggio {$index}",
                'sent_at' => now()->addSeconds($index),
            ]);
        }

        $this
            ->actingAs($user, 'sanctum')
            ->getJson("/api/chats/{$chat->id}/messages?per_page=50")
            ->assertOk()
            ->assertJsonPath('data.0.text', 'Messaggio 6')
            ->assertJsonPath('data.49.text', 'Messaggio 55')
            ->assertJsonPath('meta.last_page', 2);
    }

    public function test_conversation_is_hidden_per_user_and_deleted_after_both_clear_it(): void
    {
        $first = User::factory()->create();
        $second = User::factory()->create();
        [$one, $two] = Chat::sortedPair($first->id, $second->id);
        $chat = Chat::query()->create(['user_one_id' => $one, 'user_two_id' => $two]);
        $chat->messages()->create([
            'sender_id' => $first->id,
            'text' => 'Messaggio da eliminare',
            'sent_at' => now()->subMinute(),
        ]);

        $this->actingAs($first, 'sanctum')->deleteJson("/api/chats/{$chat->id}")
            ->assertOk()
            ->assertJsonPath('data.permanently_deleted', false);

        $this->actingAs($first, 'sanctum')->getJson('/api/chats')->assertJsonCount(0, 'data');
        $this->actingAs($second, 'sanctum')->getJson('/api/chats')->assertJsonCount(1, 'data');

        $this->actingAs($second, 'sanctum')->deleteJson("/api/chats/{$chat->id}")
            ->assertOk()
            ->assertJsonPath('data.permanently_deleted', true);

        $this->assertDatabaseMissing('chats', ['id' => $chat->id]);
        $this->assertDatabaseMissing('messages', ['chat_id' => $chat->id]);
    }

    public function test_new_message_restores_a_cleared_chat_without_old_history(): void
    {
        $first = User::factory()->create();
        $second = User::factory()->create();
        [$one, $two] = Chat::sortedPair($first->id, $second->id);
        $chat = Chat::query()->create(['user_one_id' => $one, 'user_two_id' => $two]);
        $chat->messages()->create([
            'sender_id' => $second->id,
            'text' => 'Vecchia cronologia',
            'sent_at' => now()->subMinutes(2),
        ]);

        $this->actingAs($first, 'sanctum')->deleteJson("/api/chats/{$chat->id}")->assertOk();
        $this->travel(2)->seconds();
        $this->actingAs($second, 'sanctum')->postJson("/api/chats/{$chat->id}/messages", [
            'text' => 'Nuovo messaggio',
        ])->assertCreated();

        $this->actingAs($first, 'sanctum')->getJson('/api/chats')
            ->assertOk()
            ->assertJsonPath('data.0.id', $chat->id)
            ->assertJsonPath('data.0.last_message.text', 'Nuovo messaggio');
        $this->actingAs($first, 'sanctum')->getJson("/api/chats/{$chat->id}/messages")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.text', 'Nuovo messaggio');
    }
}
