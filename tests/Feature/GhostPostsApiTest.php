<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class GhostPostsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_ghost_post_with_hashed_secret_answer(): void
    {
        $user = User::factory()->create(['display_name' => 'Autore Ghost']);
        $location = $this->location();

        $postId = $this
            ->actingAs($user, 'sanctum')
            ->postJson('/api/posts', [
                'location_id' => $location->id,
                'text' => 'Ci siamo visti davanti alla metro.',
                'song_quote' => 'Una frase della canzone',
                'sighting_date' => now()->toDateString(),
                'is_anonymous' => true,
                'secret_question' => 'Che colore era il libro?',
                'secret_answer' => ' Blu ',
            ])
            ->assertCreated()
            ->assertJsonPath('data.is_anonymous', true)
            ->assertJsonPath('data.author.display_name', 'Autore Ghost')
            ->assertJsonPath('data.song_quote', 'Una frase della canzone')
            ->assertJsonPath('data.has_secret_answer', true)
            ->assertJsonMissingPath('data.secret_answer_hash')
            ->json('data.id');

        $post = Post::query()->findOrFail($postId);

        $this->assertSame('Una frase della canzone', $post->song_quote);
        $this->assertNotSame('Blu', $post->secret_answer_hash);
        $this->assertTrue(Hash::check('blu', $post->secret_answer_hash));
    }

    public function test_ghost_post_masks_author_for_other_users(): void
    {
        $owner = User::factory()->create(['display_name' => 'Autore Reale']);
        $viewer = User::factory()->create();
        $post = Post::query()->create([
            'author_id' => $owner->id,
            'location_id' => $this->location()->id,
            'text' => 'Post anonimo',
            'sighting_date' => now()->toDateString(),
            'is_anonymous' => true,
            'secret_question' => 'Dove ero seduto?',
            'secret_answer_hash' => Hash::make('scala'),
            'expires_at' => now()->addDay(),
            'status' => 'active',
        ]);

        $this
            ->actingAs($viewer, 'sanctum')
            ->getJson("/api/posts/{$post->id}")
            ->assertOk()
            ->assertJsonPath('data.author.id', null)
            ->assertJsonPath('data.author.display_name', 'Ghost')
            ->assertJsonPath('data.secret_question', 'Dove ero seduto?')
            ->assertJsonMissingPath('data.secret_answer_hash');
    }

    private function location(): Location
    {
        return Location::query()->create([
            'name' => 'Metro Test',
            'short' => 'Metro Test',
            'city' => 'Napoli',
            'type' => 'metro',
            'latitude' => 40.8319000,
            'longitude' => 14.2193000,
            'geo_radius_meters' => 100,
            'is_active' => true,
        ]);
    }
}
