<?php

namespace Tests\Feature;

use App\Models\Challenge;
use App\Models\Chat;
use App\Models\Comment;
use App\Models\Location;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ChallengeApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_classic_answer_unlocks_ghost_post_chat_and_karma(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create(['karma' => 0]);
        $post = $this->makePost($owner, [
            'is_anonymous' => true,
            'secret_question' => 'Che colore era il libro?',
            'secret_answer_hash' => Hash::make('blu'),
        ]);

        $this
            ->actingAs($viewer, 'sanctum')
            ->postJson("/api/posts/{$post->id}/verify-answer", ['answer' => ' Blu '])
            ->assertOk()
            ->assertJsonPath('data.verified', true)
            ->assertJsonPath('data.spot_on_count', 1)
            ->assertJsonPath('data.karma', 1);

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'is_anonymous' => false,
            'spot_on_count' => 1,
        ]);
        $this->assertDatabaseHas('chats', [
            'origin_post_id' => $post->id,
        ]);
    }

    public function test_classic_wrong_answer_does_not_unlock(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        $post = $this->makePost($owner, [
            'is_anonymous' => true,
            'secret_question' => 'Dove eri?',
            'secret_answer_hash' => Hash::make('scale'),
        ]);

        $this
            ->actingAs($viewer, 'sanctum')
            ->postJson("/api/posts/{$post->id}/verify-answer", ['answer' => 'bar'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['answer']);

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'is_anonymous' => true,
            'spot_on_count' => 0,
        ]);
    }

    public function test_inverted_challenge_to_comment_author_unlocks_chat_and_challenger_karma(): void
    {
        $postOwner = User::factory()->create();
        $commentAuthor = User::factory()->create();
        $challenger = User::factory()->create(['karma' => 0]);
        $post = $this->makePost($postOwner);
        $comment = Comment::query()->create([
            'post_id' => $post->id,
            'author_id' => $commentAuthor->id,
            'text' => 'Ero li anche io.',
        ]);

        $challengeId = $this
            ->actingAs($challenger, 'sanctum')
            ->postJson('/api/challenges', [
                'post_id' => $post->id,
                'target_type' => 'comment_author',
                'source_comment_id' => $comment->id,
                'mode' => 'question',
                'question' => 'Che cosa avevi ordinato?',
                'answer' => 'caffe',
            ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.target_user.id', $commentAuthor->id)
            ->assertJsonMissingPath('data.answer_hash')
            ->json('data.id');

        $this
            ->actingAs($commentAuthor, 'sanctum')
            ->postJson("/api/challenges/{$challengeId}/answer", ['answer' => ' Caffe '])
            ->assertOk()
            ->assertJsonPath('data.challenge.status', 'unlocked')
            ->assertJsonPath('data.karma', 1);

        $this->assertDatabaseHas('challenges', [
            'id' => $challengeId,
            'status' => 'unlocked',
        ]);
        $this->assertDatabaseHas('chats', [
            'origin_challenge_id' => $challengeId,
            'origin_post_id' => $post->id,
        ]);
        $this->assertSame(1, $challenger->fresh()->karma);
    }

    public function test_classic_counterproposal_can_be_accepted_by_post_owner(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create(['karma' => 0]);
        $post = $this->makePost($owner, [
            'is_anonymous' => true,
            'secret_question' => 'Che dettaglio ricordi?',
            'secret_answer_hash' => Hash::make('giacca rossa'),
        ]);

        $challengeId = $this
            ->actingAs($viewer, 'sanctum')
            ->postJson("/api/posts/{$post->id}/counter-propose", [
                'text' => 'Ricordo che avevi una giacca rossa e un libro.',
            ])
            ->assertCreated()
            ->assertJsonPath('data.origin', 'classic')
            ->assertJsonPath('data.status', 'counter_pending')
            ->json('data.id');

        $this
            ->actingAs($owner, 'sanctum')
            ->postJson("/api/challenges/{$challengeId}/counter-review", ['accepted' => true])
            ->assertOk()
            ->assertJsonPath('data.accepted', true)
            ->assertJsonPath('data.challenge.status', 'unlocked')
            ->assertJsonPath('data.karma', 1);

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'is_anonymous' => false,
            'spot_on_count' => 1,
        ]);
        $this->assertSame(1, $viewer->fresh()->karma);
    }

    public function test_inverted_challenge_cannot_bypass_classic_secret_question(): void
    {
        $owner = User::factory()->create();
        $challenger = User::factory()->create();
        $post = $this->makePost($owner, [
            'secret_question' => 'Che colore?',
            'secret_answer_hash' => Hash::make('blu'),
        ]);

        $this
            ->actingAs($challenger, 'sanctum')
            ->postJson('/api/challenges', [
                'post_id' => $post->id,
                'target_type' => 'post_author',
                'mode' => 'direct',
            ])
            ->assertUnprocessable();
    }

    public function test_pending_returns_challenges_to_target_and_counter_reviewer(): void
    {
        $owner = User::factory()->create();
        $target = User::factory()->create();
        $post = $this->makePost($owner);

        $pendingChallengeId = Challenge::query()->create([
            'post_id' => $post->id,
            'origin' => 'inverted',
            'challenger_id' => $owner->id,
            'target_type' => 'post_author',
            'target_user_id' => $target->id,
            'question' => 'Domanda',
            'answer_hash' => Hash::make('risposta'),
            'status' => 'pending',
        ])->id;

        $counterChallengeId = Challenge::query()->create([
            'post_id' => $post->id,
            'origin' => 'classic',
            'challenger_id' => $owner->id,
            'target_type' => 'post_author',
            'target_user_id' => $target->id,
            'question' => 'Domanda',
            'answer_hash' => Hash::make('risposta'),
            'status' => 'counter_pending',
            'counter_text' => 'Controproposta',
            'counter_proposed_by' => $target->id,
        ])->id;

        $this
            ->actingAs($target, 'sanctum')
            ->getJson('/api/challenges/pending')
            ->assertOk()
            ->assertJsonPath('data.0.id', $pendingChallengeId);

        $this
            ->actingAs($owner, 'sanctum')
            ->getJson('/api/challenges/pending')
            ->assertOk()
            ->assertJsonPath('data.0.id', $counterChallengeId);
    }

    private function makePost(User $owner, array $overrides = []): Post
    {
        return Post::query()->create($overrides + [
            'author_id' => $owner->id,
            'location_id' => $this->location()->id,
            'text' => 'Post challenge',
            'sighting_date' => now()->toDateString(),
            'expires_at' => now()->addDay(),
            'status' => 'active',
        ]);
    }

    private function location(): Location
    {
        return Location::query()->create([
            'name' => 'Locale Challenge',
            'short' => 'Locale Challenge',
            'city' => 'Napoli',
            'type' => 'bar',
            'latitude' => 40.8495000,
            'longitude' => 14.2569000,
            'geo_radius_meters' => 100,
            'is_active' => true,
        ]);
    }
}
