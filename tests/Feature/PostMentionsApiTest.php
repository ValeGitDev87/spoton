<?php

namespace Tests\Feature;

use App\Jobs\Push\SendPostMentionNotifications;
use App\Models\Location;
use App\Models\Post;
use App\Models\User;
use App\Services\Push\PushNotificationService;
use App\Support\Push\PushNotificationType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PostMentionsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_can_mention_multiple_users_and_exposes_them_in_the_payload(): void
    {
        Queue::fake();

        $author = User::factory()->create();
        $sara = User::factory()->create(['display_name' => 'Sara Blu']);
        $luca = User::factory()->create(['display_name' => 'Luca Verde']);

        $response = $this->actingAs($author, 'sanctum')->postJson('/api/posts', [
            'location_id' => $this->location()->id,
            'text' => 'Ciao @Sara Blu e @Luca Verde, questo annuncio è per voi.',
            'sighting_date' => now()->toDateString(),
            'mention_user_ids' => [$sara->id, $luca->id],
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.mentions_everyone', false)
            ->assertJsonCount(2, 'data.mentions')
            ->assertJsonFragment(['id' => $sara->id, 'display_name' => 'Sara Blu'])
            ->assertJsonFragment(['id' => $luca->id, 'display_name' => 'Luca Verde']);

        $postId = $response->json('data.id');

        $this->assertDatabaseHas('post_mentions', ['post_id' => $postId, 'user_id' => $sara->id]);
        $this->assertDatabaseHas('post_mentions', ['post_id' => $postId, 'user_id' => $luca->id]);
        Queue::assertPushedOn('notifications', SendPostMentionNotifications::class);
    }

    public function test_selected_mention_must_be_present_in_the_post_text(): void
    {
        $author = User::factory()->create();
        $target = User::factory()->create(['display_name' => 'Sara Blu']);

        $this->actingAs($author, 'sanctum')->postJson('/api/posts', [
            'location_id' => $this->location()->id,
            'text' => 'Questo testo non contiene la persona selezionata.',
            'sighting_date' => now()->toDateString(),
            'mention_user_ids' => [$target->id],
        ])->assertUnprocessable()->assertJsonValidationErrors('mention_user_ids');
    }

    public function test_everyone_mention_requires_permission_and_is_limited_to_twice_per_day(): void
    {
        Queue::fake();

        $unauthorized = User::factory()->create();

        $this->actingAs($unauthorized, 'sanctum')->postJson('/api/posts', [
            'location_id' => $this->location()->id,
            'text' => 'Ciao @tutti.',
            'sighting_date' => now()->toDateString(),
            'mention_everyone' => true,
        ])->assertUnprocessable()->assertJsonValidationErrors(['text', 'mention_everyone']);

        $authorized = User::factory()->create(['can_mention_everyone' => true]);

        foreach ([1, 2] as $attempt) {
            $this->actingAs($authorized, 'sanctum')->postJson('/api/posts', [
                'location_id' => $this->location()->id,
                'text' => "Avviso {$attempt} per @tutti.",
                'sighting_date' => now()->toDateString(),
                'mention_everyone' => true,
            ])->assertCreated()->assertJsonPath('data.mentions_everyone', true);
        }

        $this->actingAs($authorized, 'sanctum')->postJson('/api/posts', [
            'location_id' => $this->location()->id,
            'text' => 'Terzo avviso per @tutti.',
            'sighting_date' => now()->toDateString(),
            'mention_everyone' => true,
        ])->assertStatus(429);
    }

    public function test_post_mention_job_creates_persistent_notifications(): void
    {
        Queue::fake();

        $author = User::factory()->create(['display_name' => 'Valentino']);
        $sara = User::factory()->create();
        $luca = User::factory()->create();
        $post = $this->makePost($author);
        $job = new SendPostMentionNotifications(
            $post->id,
            $author->id,
            [$sara->id, $luca->id],
        );

        $job->handle(app(PushNotificationService::class));

        foreach ([$sara, $luca] as $recipient) {
            $this->assertDatabaseHas('user_notifications', [
                'user_id' => $recipient->id,
                'type' => PushNotificationType::USER_MENTIONED,
                'body' => 'Valentino ti ha menzionato in un annuncio.',
            ]);
        }
    }

    public function test_everyone_job_expands_only_active_non_admin_recipients(): void
    {
        Queue::fake();

        $author = User::factory()->create();
        $firstRecipient = User::factory()->create();
        $secondRecipient = User::factory()->create();
        User::factory()->create(['is_admin' => true]);
        User::factory()->create(['is_suspended' => true]);
        $post = $this->makePost($author);
        $job = new SendPostMentionNotifications(
            $post->id,
            $author->id,
            [],
            true,
            true,
        );

        $job->handle(app(PushNotificationService::class));

        Queue::assertPushed(SendPostMentionNotifications::class, function ($queuedJob) use ($firstRecipient, $secondRecipient): bool {
            $recipientIds = (new \ReflectionProperty($queuedJob, 'recipientIds'))->getValue($queuedJob);
            $everyone = (new \ReflectionProperty($queuedJob, 'everyone'))->getValue($queuedJob);
            $expandEveryone = (new \ReflectionProperty($queuedJob, 'expandEveryone'))->getValue($queuedJob);

            sort($recipientIds);
            $expected = [$firstRecipient->id, $secondRecipient->id];
            sort($expected);

            return $recipientIds === $expected && $everyone && ! $expandEveryone;
        });
    }

    public function test_current_user_payload_exposes_everyone_permission(): void
    {
        $user = User::factory()->create(['can_mention_everyone' => true]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('data.user.can_mention_everyone', true);
    }

    private function makePost(User $author): Post
    {
        return Post::query()->create([
            'author_id' => $author->id,
            'location_id' => $this->location()->id,
            'text' => 'Post con menzioni.',
            'sighting_date' => now()->toDateString(),
            'expires_at' => now()->addHours(48),
            'status' => 'active',
        ]);
    }

    private function location(): Location
    {
        return Location::query()->create([
            'name' => 'Piazza Test',
            'short' => 'Piazza Test',
            'city' => 'Napoli',
            'type' => 'piazza',
            'latitude' => 40.8420000,
            'longitude' => 14.2490000,
            'geo_radius_meters' => 250,
            'is_active' => true,
        ]);
    }
}
