<?php

namespace Tests\Feature;

use App\Models\Chat;
use App\Models\Location;
use App\Models\Post;
use App\Models\PushToken;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AccountDeletionApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_deletion_requires_correct_password_and_confirmation(): void
    {
        $user = User::factory()->create(['password' => 'password123']);

        $this
            ->actingAs($user, 'sanctum')
            ->deleteJson('/api/me', [
                'current_password' => 'password-errata',
                'confirmation' => 'DELETE',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('current_password');

        $this
            ->actingAs($user, 'sanctum')
            ->deleteJson('/api/me', [
                'current_password' => 'password123',
                'confirmation' => 'ELIMINA',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('confirmation');

        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_admin_account_cannot_be_deleted_through_api(): void
    {
        $admin = User::factory()->create([
            'password' => 'password123',
            'is_admin' => true,
        ]);

        $this
            ->actingAs($admin, 'sanctum')
            ->deleteJson('/api/me', [
                'current_password' => 'password123',
                'confirmation' => 'DELETE',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_user_can_delete_account_and_all_related_local_data(): void
    {
        Storage::fake('public');

        $user = User::factory()->create([
            'email' => 'delete-me@example.test',
            'password' => 'password123',
            'avatar_url' => '/storage/profile-photos/avatar.jpg',
            'photos' => [
                '/storage/profile-photos/photo.jpg',
                'https://cdn.example.test/external.jpg',
            ],
            'last_known_latitude' => 40.8518,
            'last_known_longitude' => 14.2681,
            'last_location_update' => now(),
        ]);
        $other = User::factory()->create();
        $location = $this->location();
        $post = Post::query()->create([
            'author_id' => $user->id,
            'location_id' => $location->id,
            'text' => 'Post da eliminare',
            'audio_disk' => 'public',
            'audio_path' => 'post-audios/note.m4a',
            'audio_url' => '/storage/post-audios/note.m4a',
            'audio_mime' => 'audio/mp4',
            'audio_size_bytes' => 100,
            'audio_duration_seconds' => 5,
            'sighting_date' => now()->toDateString(),
            'expires_at' => now()->addDay(),
            'status' => 'active',
        ]);

        Storage::disk('public')->put('profile-photos/avatar.jpg', 'avatar');
        Storage::disk('public')->put('profile-photos/photo.jpg', 'photo');
        Storage::disk('public')->put('post-audios/note.m4a', 'audio');

        $apiTokenId = $user->createToken('delete-test')->accessToken->id;
        $pushToken = PushToken::query()->create([
            'user_id' => $user->id,
            'token' => 'ExponentPushToken[delete_me]',
            'token_hash' => PushToken::hashToken('ExponentPushToken[delete_me]'),
            'device_id' => 'delete-device',
            'platform' => 'ios',
            'is_active' => true,
            'last_seen_at' => now(),
        ]);
        [$one, $two] = Chat::sortedPair($user->id, $other->id);
        $chat = Chat::query()->create([
            'user_one_id' => $one,
            'user_two_id' => $two,
        ]);
        $chat->messages()->create([
            'sender_id' => $user->id,
            'text' => 'Messaggio da eliminare',
            'sent_at' => now(),
        ]);
        $userReport = Report::query()->create([
            'reporter_id' => $other->id,
            'reportable_type' => 'user',
            'reportable_id' => $user->id,
            'reason' => 'other',
            'status' => Report::STATUS_PENDING,
        ]);
        $postReport = Report::query()->create([
            'reporter_id' => $other->id,
            'reportable_type' => 'post',
            'reportable_id' => $post->id,
            'reason' => 'other',
            'status' => Report::STATUS_PENDING,
        ]);

        $this
            ->actingAs($user, 'sanctum')
            ->deleteJson('/api/me', [
                'current_password' => 'password123',
                'confirmation' => 'DELETE',
            ])
            ->assertOk()
            ->assertJsonPath('data.deleted', true);

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
        $this->assertDatabaseMissing('chats', ['id' => $chat->id]);
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $apiTokenId]);
        $this->assertDatabaseMissing('push_tokens', ['id' => $pushToken->id]);
        $this->assertDatabaseMissing('reports', ['id' => $userReport->id]);
        $this->assertDatabaseMissing('reports', ['id' => $postReport->id]);
        $this->assertDatabaseHas('users', ['id' => $other->id]);
        Storage::disk('public')->assertMissing('profile-photos/avatar.jpg');
        Storage::disk('public')->assertMissing('profile-photos/photo.jpg');
        Storage::disk('public')->assertMissing('post-audios/note.m4a');
    }

    private function location(): Location
    {
        return Location::query()->create([
            'name' => 'Locale Account Test',
            'short' => 'Account Test',
            'city' => 'Napoli',
            'type' => 'bar',
            'latitude' => 40.8518000,
            'longitude' => 14.2681000,
            'geo_radius_meters' => 100,
            'is_active' => true,
        ]);
    }
}
