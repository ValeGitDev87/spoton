<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserNotificationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_list_and_mark_own_notifications_as_read(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $older = $this->notification($user, 'user_mentioned', 'Prima notifica');
        $newer = $this->notification($user, 'new_message', 'Seconda notifica');
        $foreign = $this->notification($other, 'new_comment', 'Notifica privata');
        $older->forceFill(['created_at' => now()->subMinute()])->saveQuietly();

        $this
            ->actingAs($user, 'sanctum')
            ->getJson('/api/notifications')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $newer->id)
            ->assertJsonPath('data.1.id', $older->id)
            ->assertJsonPath('meta.total', 2);

        $this
            ->actingAs($user, 'sanctum')
            ->getJson('/api/notifications/unread-count')
            ->assertOk()
            ->assertJsonPath('data.unread_count', 2);

        $this
            ->actingAs($user, 'sanctum')
            ->patchJson("/api/notifications/{$newer->id}/read")
            ->assertOk()
            ->assertJsonPath('data.id', $newer->id);

        $this
            ->actingAs($user, 'sanctum')
            ->getJson('/api/notifications/unread-count')
            ->assertJsonPath('data.unread_count', 1);

        $this
            ->actingAs($user, 'sanctum')
            ->patchJson('/api/notifications/read-all')
            ->assertOk()
            ->assertJsonPath('data.updated', 1)
            ->assertJsonPath('data.unread_count', 0);

        $this
            ->actingAs($user, 'sanctum')
            ->patchJson("/api/notifications/{$foreign->id}/read")
            ->assertNotFound();
    }

    private function notification(User $user, string $type, string $title): UserNotification
    {
        return UserNotification::query()->create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'body' => 'Corpo notifica',
            'data' => ['type' => $type],
        ]);
    }
}
