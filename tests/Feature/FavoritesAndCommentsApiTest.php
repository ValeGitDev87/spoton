<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FavoritesAndCommentsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_favorites_are_private_idempotent_and_searchable(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $this
            ->actingAs($user, 'sanctum')
            ->postJson('/api/favorites', ['target_name' => 'Sara'])
            ->assertCreated()
            ->assertJsonPath('data.target_name', 'Sara');

        $this
            ->actingAs($user, 'sanctum')
            ->postJson('/api/favorites', ['target_name' => 'sara'])
            ->assertCreated();

        $this->assertDatabaseCount('favorites', 1);

        $this
            ->actingAs($user, 'sanctum')
            ->getJson('/api/favorites?query=ar')
            ->assertOk()
            ->assertJsonPath('data.0.target_name', 'Sara');

        $this
            ->actingAs($other, 'sanctum')
            ->getJson('/api/favorites')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_comment_can_tag_only_a_favorite(): void
    {
        $author = User::factory()->create();
        $target = User::factory()->create(['display_name' => 'Sara']);
        $post = $this->makePost();

        $this
            ->actingAs($author, 'sanctum')
            ->postJson("/api/posts/{$post->id}/comments", ['text' => 'Ciao @Sara'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['text']);

        $this
            ->actingAs($author, 'sanctum')
            ->postJson('/api/favorites', ['target_name' => 'Sara'])
            ->assertCreated();

        $this
            ->actingAs($author, 'sanctum')
            ->postJson("/api/posts/{$post->id}/comments", ['text' => 'Ciao @Sara'])
            ->assertCreated()
            ->assertJsonPath('data.tagged_user.id', $target->id)
            ->assertJsonPath('data.text', 'Ciao @Sara');

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'comment_count' => 1,
        ]);
    }

    public function test_comment_tag_accepts_trailing_punctuation_and_prefers_exact_name(): void
    {
        $author = User::factory()->create();
        $sara = User::factory()->create(['display_name' => 'Sara']);
        User::factory()->create(['display_name' => 'Sara Blu']);
        $post = $this->makePost();

        $this
            ->actingAs($author, 'sanctum')
            ->postJson('/api/favorites', ['target_name' => 'Sara'])
            ->assertCreated();

        $this
            ->actingAs($author, 'sanctum')
            ->postJson('/api/favorites', ['target_name' => 'Sara Blu'])
            ->assertCreated();

        $this
            ->actingAs($author, 'sanctum')
            ->postJson(
                "/api/posts/{$post->id}/comments",
                ['text' => 'Ciao @Sara, secondo me eri tu.']
            )
            ->assertCreated()
            ->assertJsonPath('data.tagged_user.id', $sara->id)
            ->assertJsonPath('data.tagged_user.display_name', 'Sara');
    }

    public function test_comment_tag_supports_favorite_names_with_spaces(): void
    {
        $author = User::factory()->create();
        User::factory()->create(['display_name' => 'Sara']);
        $saraBlu = User::factory()->create(['display_name' => 'Sara Blu']);
        $post = $this->makePost();

        $this
            ->actingAs($author, 'sanctum')
            ->postJson('/api/favorites', ['target_name' => 'Sara'])
            ->assertCreated();

        $this
            ->actingAs($author, 'sanctum')
            ->postJson('/api/favorites', ['target_name' => 'Sara Blu'])
            ->assertCreated();

        $this
            ->actingAs($author, 'sanctum')
            ->postJson(
                "/api/posts/{$post->id}/comments",
                ['text' => 'Ciao @Sara Blu!']
            )
            ->assertCreated()
            ->assertJsonPath('data.tagged_user.id', $saraBlu->id)
            ->assertJsonPath('data.tagged_user.display_name', 'Sara Blu');
    }

    public function test_comments_can_be_listed(): void
    {
        $author = User::factory()->create();
        $post = $this->makePost();

        $this
            ->actingAs($author, 'sanctum')
            ->postJson("/api/posts/{$post->id}/comments", ['text' => 'Primo commento'])
            ->assertCreated();

        $this
            ->actingAs($author, 'sanctum')
            ->getJson("/api/posts/{$post->id}/comments")
            ->assertOk()
            ->assertJsonPath('data.0.text', 'Primo commento');
    }

    private function makePost(): Post
    {
        return Post::query()->create([
            'author_id' => User::factory()->create()->id,
            'location_id' => $this->location()->id,
            'text' => 'Post con commenti',
            'sighting_date' => now()->toDateString(),
            'expires_at' => now()->addDay(),
            'status' => 'active',
        ]);
    }

    private function location(): Location
    {
        return Location::query()->create([
            'name' => 'Bar Test',
            'short' => 'Bar Test',
            'city' => 'Napoli',
            'type' => 'bar',
            'latitude' => 40.8495000,
            'longitude' => 14.2569000,
            'geo_radius_meters' => 100,
            'is_active' => true,
        ]);
    }
}
