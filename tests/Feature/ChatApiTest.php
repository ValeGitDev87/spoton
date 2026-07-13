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
        $user = User::factory()->create();
        $other = User::factory()->create(['display_name' => 'Altra Persona']);

        $chatId = $this
            ->actingAs($user, 'sanctum')
            ->postJson('/api/chats/open', ['user_id' => $other->id])
            ->assertCreated()
            ->assertJsonPath('data.participant.id', $other->id)
            ->json('data.id');

        $this
            ->actingAs($user, 'sanctum')
            ->postJson("/api/chats/{$chatId}/messages", ['text' => 'Ciao!'])
            ->assertCreated()
            ->assertJsonPath('data.text', 'Ciao!');

        $this
            ->actingAs($other, 'sanctum')
            ->getJson("/api/chats/{$chatId}/messages")
            ->assertOk()
            ->assertJsonPath('data.0.text', 'Ciao!');
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
}
