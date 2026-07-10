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
