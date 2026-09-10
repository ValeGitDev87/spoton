<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\Post;
use App\Models\User;
use App\Services\Moderation\ContentModerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentModerationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_filter_has_allow_warn_and_block_outcomes_with_normalization(): void
    {
        $filter = app(ContentModerationService::class);

        $this->assertSame(ContentModerationService::ALLOW, $filter->evaluate('Treno in arrivo al binario 2')['decision']);
        $this->assertSame(ContentModerationService::WARN, $filter->evaluate('Sei un c0gli0ne')['decision']);
        $this->assertSame(ContentModerationService::BLOCK, $filter->evaluate('T1 4mm4zz0')['decision']);
    }

    public function test_post_and_ghost_post_reject_unacceptable_text_but_allow_normal_text(): void
    {
        $user = User::factory()->create();
        $location = $this->location();
        $base = [
            'location_id' => $location->id,
            'category' => 'gossip_events',
            'sighting_date' => now()->toDateString(),
        ];

        $this->actingAs($user, 'sanctum')->postJson('/api/posts', $base + [
            'text' => 'T1 4mm4zz0 davanti a tutti',
            'is_anonymous' => false,
        ])->assertUnprocessable()->assertJsonValidationErrors('text');

        $this->actingAs($user, 'sanctum')->postJson('/api/posts', $base + [
            'text' => 'Ucciditi davanti a tutti',
            'is_anonymous' => true,
        ])->assertUnprocessable()->assertJsonValidationErrors('text');

        $this->actingAs($user, 'sanctum')->postJson('/api/posts', $base + [
            'text' => 'Concerto iniziato da pochi minuti',
            'is_anonymous' => false,
        ])->assertCreated();
    }

    public function test_comment_chat_and_profile_are_filtered_server_side(): void
    {
        $author = User::factory()->create();
        $other = User::factory()->create();
        $post = $this->makePost($author);

        $this->actingAs($other, 'sanctum')
            ->postJson("/api/posts/{$post->id}/comments", ['text' => 'Sei un pezzo di m3rd4'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('text');

        $chat = $this->actingAs($other, 'sanctum')
            ->postJson('/api/chats/open', ['user_id' => $author->id])
            ->assertCreated()
            ->json('data');

        $this->actingAs($other, 'sanctum')
            ->postJson("/api/chats/{$chat['id']}/messages", ['text' => 'Ti uccido'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('text');

        $this->actingAs($other, 'sanctum')
            ->patchJson('/api/me', ['bio' => 'Vaffanculo a tutti'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('bio');
    }

    private function location(): Location
    {
        return Location::query()->create([
            'name' => 'Piazza Test',
            'short' => 'Piazza',
            'city' => 'Napoli',
            'type' => 'altro',
            'latitude' => 40.8518,
            'longitude' => 14.2681,
            'geo_radius_meters' => 100,
            'is_active' => true,
        ]);
    }

    private function makePost(User $author): Post
    {
        return Post::query()->create([
            'author_id' => $author->id,
            'location_id' => $this->location()->id,
            'text' => 'Test moderazione commenti',
            'sighting_date' => now()->toDateString(),
            'expires_at' => now()->addHours(48),
            'status' => 'active',
        ]);
    }
}
