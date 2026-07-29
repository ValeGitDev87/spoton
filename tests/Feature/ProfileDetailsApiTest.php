<?php

namespace Tests\Feature;

use App\Models\Chat;
use App\Models\Favorite;
use App\Models\Like;
use App\Models\Location;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileDetailsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_stats_and_detail_lists_are_complete(): void
    {
        $user = User::factory()->create(['karma' => 3]);
        $favoriteUser = User::factory()->create([
            'display_name' => 'Sara Preferita',
            'avatar_url' => '/storage/profile-photos/sara.jpg',
        ]);
        $liker = User::factory()->create([
            'display_name' => 'Luca Like',
            'avatar_url' => '/storage/profile-photos/luca.jpg',
        ]);
        $location = $this->location();
        $activePost = $this->makePost($user, $location, 'Annuncio attivo', now()->addDay());
        $expiredPost = $this->makePost($user, $location, 'Annuncio scaduto', now()->subDay());
        $removedPost = $this->makePost($user, $location, 'Annuncio rimosso', now()->addDay(), 'removed');

        Like::query()->create([
            'post_id' => $activePost->id,
            'user_id' => $liker->id,
        ]);
        $activePost->update(['like_count' => 1]);
        Favorite::query()->create([
            'owner_id' => $user->id,
            'target_user_id' => $favoriteUser->id,
            'target_name' => $favoriteUser->display_name,
        ]);
        [$one, $two] = Chat::sortedPair($user->id, $favoriteUser->id);
        Chat::query()->create([
            'user_one_id' => $one,
            'user_two_id' => $two,
        ]);

        $this
            ->actingAs($user, 'sanctum')
            ->getJson('/api/users/me/stats')
            ->assertOk()
            ->assertJsonPath('data.posts_count', 2)
            ->assertJsonPath('data.chats_count', 1)
            ->assertJsonPath('data.karma', 3)
            ->assertJsonPath('data.favorites_count', 1)
            ->assertJsonPath('data.likes_received_count', 1);

        $this
            ->actingAs($user, 'sanctum')
            ->getJson('/api/users/me/posts')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonMissing(['id' => $removedPost->id])
            ->assertJsonFragment(['id' => $expiredPost->id, 'is_active' => false]);

        $this
            ->actingAs($user, 'sanctum')
            ->getJson('/api/favorites')
            ->assertOk()
            ->assertJsonPath('data.0.target_user.id', $favoriteUser->id)
            ->assertJsonPath('data.0.target_user.avatar_url', '/storage/profile-photos/sara.jpg');

        $this
            ->actingAs($user, 'sanctum')
            ->getJson('/api/users/me/karma')
            ->assertOk()
            ->assertJsonPath('data.karma', 3)
            ->assertJsonPath('data.likes_received_count', 1)
            ->assertJsonPath('data.likes.0.user.id', $liker->id)
            ->assertJsonPath('data.likes.0.post.id', $activePost->id)
            ->assertJsonPath('meta.total', 1);
    }

    public function test_favorite_can_be_created_using_the_target_user_id(): void
    {
        $user = User::factory()->create();
        $target = User::factory()->create(['display_name' => 'Nome Corrente']);

        $this
            ->actingAs($user, 'sanctum')
            ->postJson('/api/favorites', ['target_user_id' => $target->id])
            ->assertCreated()
            ->assertJsonPath('data.target_name', 'Nome Corrente')
            ->assertJsonPath('data.target_user.id', $target->id);

        $this->assertDatabaseHas('favorites', [
            'owner_id' => $user->id,
            'target_user_id' => $target->id,
        ]);

        $target->update(['display_name' => 'Nome Aggiornato']);

        $this
            ->actingAs($user, 'sanctum')
            ->getJson('/api/favorites?query=aggiornato')
            ->assertOk()
            ->assertJsonPath('data.0.target_name', 'Nome Aggiornato');

        $this
            ->actingAs($user, 'sanctum')
            ->deleteJson('/api/favorites/Nome%20Aggiornato')
            ->assertOk()
            ->assertJsonPath('data.removed', true);
    }

    private function makePost(
        User $author,
        Location $location,
        string $text,
        mixed $expiresAt,
        string $status = 'active',
    ): Post {
        return Post::query()->create([
            'author_id' => $author->id,
            'location_id' => $location->id,
            'text' => $text,
            'sighting_date' => now()->toDateString(),
            'expires_at' => $expiresAt,
            'status' => $status,
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
