<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\Post;
use App\Models\PostShareMedia;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
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
            ->assertDontSee('Elimina tutti gli scaduti')
            ->assertSee($post->author->display_name)
            ->assertSee($post->location->name);
    }

    public function test_expired_filter_includes_posts_due_before_the_minute_job_runs(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $due = $this->makePost('Post appena scaduto', 'active');
        $due->update(['expires_at' => now()->subMinute()]);
        $this->makePost('Post ancora attivo', 'active');

        $this->actingAs($admin)
            ->get('/admin/posts?status=expired')
            ->assertOk()
            ->assertSee('Post appena scaduto')
            ->assertSee('Scaduto')
            ->assertDontSee('Post ancora attivo');

        $this->get('/admin/posts?status=active')
            ->assertOk()
            ->assertSee('Post ancora attivo')
            ->assertDontSee('Post appena scaduto');
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

    public function test_only_admin_can_permanently_delete_post_and_all_attached_files(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();
        $post = $this->makePost('Post scaduto', 'expired');
        $post->update(['expires_at' => now()->subMinute()]);
        $imagePath = 'post-images/test.jpg';
        $audioPath = 'post-audios/test.m4a';
        $videoPath = 'post-videos/test.mp4';
        $sharePath = 'share-videos/test.mp4';
        $cardPath = "share-cards/{$post->id}-v1.png";

        foreach ([$imagePath, $audioPath, $videoPath, $sharePath] as $path) {
            Storage::disk('public')->put($path, 'test-file');
        }

        $post->update([
            'image_disk' => 'public', 'image_path' => $imagePath,
            'audio_disk' => 'public', 'audio_path' => $audioPath,
            'video_disk' => 'public', 'video_path' => $videoPath,
        ]);
        Storage::disk('public')->put($cardPath, 'test-file');
        $post->comments()->create(['author_id' => $user->id, 'text' => 'Commento da eliminare']);
        $post->reports()->create([
            'reporter_id' => $user->id,
            'reason' => 'spam',
            'status' => Report::STATUS_PENDING,
        ]);
        PostShareMedia::query()->create([
            'post_id' => $post->id,
            'template_version' => 'v1',
            'status' => PostShareMedia::STATUS_READY,
            'disk' => 'public',
            'path' => $sharePath,
        ]);

        $this->actingAs($user)->delete("/admin/posts/{$post->id}")->assertForbidden();
        $this->assertDatabaseHas('posts', ['id' => $post->id]);

        $this->actingAs($admin)
            ->get('/admin/posts?status=expired')
            ->assertOk()
            ->assertSee('Post scaduto')
            ->assertSee('Elimina definitivamente');

        $this->delete("/admin/posts/{$post->id}")
            ->assertRedirect()
            ->assertSessionHas('status', 'Post e file collegati eliminati definitivamente.');

        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
        $this->assertDatabaseMissing('comments', ['post_id' => $post->id]);
        $this->assertDatabaseMissing('reports', ['reportable_type' => 'post', 'reportable_id' => $post->id]);
        $this->assertDatabaseMissing('post_share_media', ['post_id' => $post->id]);
        foreach ([$imagePath, $audioPath, $videoPath, $sharePath, $cardPath] as $path) {
            Storage::disk('public')->assertMissing($path);
        }
    }

    public function test_admin_can_delete_all_expired_posts_across_pages_without_touching_other_posts(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();

        foreach (range(1, 21) as $index) {
            $this->makePost("Scaduto {$index}", 'expired');
        }

        $due = $this->makePost('Scaduto prima del job', 'active');
        $due->update(['expires_at' => now()->subMinute()]);
        $due->update([
            'image_disk' => 'public', 'image_path' => 'post-images/bulk.jpg',
            'audio_disk' => 'public', 'audio_path' => 'post-audios/bulk.m4a',
            'video_disk' => 'public', 'video_path' => 'post-videos/bulk.mp4',
        ]);
        foreach (['post-images/bulk.jpg', 'post-audios/bulk.m4a', 'post-videos/bulk.mp4'] as $path) {
            Storage::disk('public')->put($path, 'file');
        }
        $due->comments()->create(['author_id' => $user->id, 'text' => 'Commento']);
        $due->reports()->create([
            'reporter_id' => $user->id,
            'reason' => 'spam',
            'status' => Report::STATUS_PENDING,
        ]);

        $active = $this->makePost('Ancora attivo', 'active');
        $removed = $this->makePost('Disattivato', 'removed');
        $removed->update(['expires_at' => now()->subMinute()]);

        $this->actingAs($admin)
            ->get('/admin/posts')
            ->assertOk()
            ->assertSee('Post scaduti: 22')
            ->assertSee('Elimina tutti gli scaduti');

        $this->delete('/admin/posts/expired', ['confirmation' => 'delete_expired'])
            ->assertRedirect('/admin/posts?status=expired')
            ->assertSessionHas('status', 'Eliminati definitivamente 22 post scaduti e i file collegati.');

        $this->assertDatabaseCount('posts', 2);
        $this->assertDatabaseHas('posts', ['id' => $active->id]);
        $this->assertDatabaseHas('posts', ['id' => $removed->id]);
        $this->assertDatabaseMissing('posts', ['id' => $due->id]);
        $this->assertDatabaseMissing('comments', ['post_id' => $due->id]);
        $this->assertDatabaseMissing('reports', ['reportable_type' => 'post', 'reportable_id' => $due->id]);
        foreach (['post-images/bulk.jpg', 'post-audios/bulk.m4a', 'post-videos/bulk.mp4'] as $path) {
            Storage::disk('public')->assertMissing($path);
        }
    }

    public function test_bulk_expired_deletion_requires_admin_and_explicit_confirmation(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();
        $post = $this->makePost('Scaduto', 'expired');

        $this->actingAs($user)
            ->delete('/admin/posts/expired', ['confirmation' => 'delete_expired'])
            ->assertForbidden();

        $this->actingAs($admin)
            ->delete('/admin/posts/expired')
            ->assertSessionHasErrors('confirmation');

        $this->assertDatabaseHas('posts', ['id' => $post->id]);
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
