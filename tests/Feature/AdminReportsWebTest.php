<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminReportsWebTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_report_queue_and_remove_reported_post(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $reporter = User::factory()->create();
        $post = $this->postBy(User::factory()->create());
        $report = $this->report($reporter, $post);

        $this
            ->actingAs($admin)
            ->get('/admin/reports')
            ->assertOk()
            ->assertSee('Segnalazioni')
            ->assertSee('Post da moderare')
            ->assertSee($reporter->display_name);

        $this
            ->actingAs($admin)
            ->patch("/admin/reports/{$report->id}", [
                'status' => Report::STATUS_ACTIONED,
                'resolution_note' => 'Contenuto rimosso.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('posts', ['id' => $post->id, 'status' => 'removed']);
        $this->assertDatabaseHas('reports', [
            'id' => $report->id,
            'status' => Report::STATUS_ACTIONED,
            'reviewed_by' => $admin->id,
        ]);
    }

    public function test_admin_action_suspends_reported_user_and_revokes_tokens(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $reporter = User::factory()->create();
        $target = User::factory()->create();
        $target->createToken('test-token');
        $report = $this->report($reporter, $target);

        $this
            ->actingAs($admin)
            ->patch("/admin/reports/{$report->id}", [
                'status' => Report::STATUS_ACTIONED,
                'resolution_note' => 'Molestie confermate.',
            ])
            ->assertRedirect();

        $target->refresh();
        $this->assertTrue($target->is_suspended);
        $this->assertNotNull($target->suspended_at);
        $this->assertSame('Molestie confermate.', $target->suspension_reason);
        $this->assertDatabaseMissing('personal_access_tokens', ['tokenable_id' => $target->id]);
    }

    public function test_non_admin_cannot_view_reports(): void
    {
        $this
            ->actingAs(User::factory()->create())
            ->get('/admin/reports')
            ->assertForbidden();
    }

    private function report(User $reporter, Post|User $target): Report
    {
        return Report::query()->create([
            'reporter_id' => $reporter->id,
            'reportable_type' => $target instanceof Post ? 'post' : 'user',
            'reportable_id' => $target->id,
            'reason' => 'inappropriate',
            'details' => 'Da controllare.',
            'status' => Report::STATUS_PENDING,
        ]);
    }

    private function postBy(User $author): Post
    {
        $location = Location::query()->create([
            'name' => 'Luogo moderazione',
            'short' => 'Moderazione',
            'city' => 'Napoli',
            'type' => 'altro',
            'latitude' => 40.8518,
            'longitude' => 14.2681,
            'geo_radius_meters' => 100,
            'icon' => 'location-outline',
            'is_active' => true,
        ]);

        return Post::query()->create([
            'author_id' => $author->id,
            'location_id' => $location->id,
            'text' => 'Post da moderare',
            'sighting_date' => now()->toDateString(),
            'expires_at' => now()->addDay(),
            'status' => 'active',
        ]);
    }
}
