<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPostsWebTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_posts_table(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $post = $this->makePost('Post da controllare', 'active');

        $this
            ->actingAs($admin)
            ->get('/admin/posts')
            ->assertOk()
            ->assertSee('Post da controllare')
            ->assertSee($post->author->display_name)
            ->assertSee($post->location->name);
    }

    public function test_admin_can_deactivate_and_reactivate_post(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $post = $this->makePost('Post da disattivare', 'active');

        $this
            ->actingAs($admin)
            ->patch("/admin/posts/{$post->id}/status", ['status' => 'removed'])
            ->assertRedirect();

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'status' => 'removed',
        ]);

        $this
            ->actingAs($admin)
            ->patch("/admin/posts/{$post->id}/status", ['status' => 'active'])
            ->assertRedirect();

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'status' => 'active',
        ]);
    }

    public function test_admin_posts_use_compact_pagination_without_unstyled_svg_arrows(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        foreach (range(1, 21) as $index) {
            $this->makePost("Post pagina {$index}", 'active');
        }

        $this
            ->actingAs($admin)
            ->get('/admin/posts?page=2')
            ->assertOk()
            ->assertSee('class="admin-pager"', false)
            ->assertSee('aria-label="Pagina precedente"', false)
            ->assertDontSee('<svg', false);
    }

    public function test_reactivating_a_community_rejected_post_resets_its_verification(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $post = $this->makePost('Segnalazione risolta', 'removed');
        $post->update([
            'category' => 'weather_transport',
            'community_status' => 'rejected',
            'community_confirm_count' => 1,
            'community_false_count' => 2,
        ]);

        $this
            ->actingAs($admin)
            ->patch("/admin/posts/{$post->id}/status", ['status' => 'active'])
            ->assertRedirect();

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'status' => 'active',
            'community_status' => 'pending',
            'community_confirm_count' => 0,
            'community_false_count' => 0,
        ]);
    }

    private function makePost(string $text, string $status): Post
    {
        $user = User::factory()->create(['display_name' => 'Autore Post']);
        $location = Location::query()->create([
            'name' => 'Piazza Plebiscito',
            'short' => 'Piazza Plebiscito',
            'city' => 'Napoli',
            'type' => 'piazza',
            'latitude' => 40.8359000,
            'longitude' => 14.2488000,
            'geo_radius_meters' => 100,
            'is_active' => true,
        ]);

        return Post::query()->create([
            'author_id' => $user->id,
            'location_id' => $location->id,
            'text' => $text,
            'musica' => 'Musica test',
            'sighting_date' => '2026-07-10',
            'expires_at' => now()->addDay(),
            'status' => $status,
        ])->load(['author', 'location']);
    }
}
