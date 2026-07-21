<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_report_post_and_duplicate_pending_report_is_reused(): void
    {
        $reporter = User::factory()->create();
        $post = $this->postBy(User::factory()->create());
        $payload = [
            'target_type' => 'post',
            'target_id' => $post->id,
            'reason' => 'spam',
            'details' => 'Contenuto ripetuto.',
        ];

        $this
            ->actingAs($reporter, 'sanctum')
            ->postJson('/api/reports', $payload)
            ->assertCreated()
            ->assertJsonPath('data.target_type', 'post')
            ->assertJsonPath('data.status', Report::STATUS_PENDING);

        $this
            ->actingAs($reporter, 'sanctum')
            ->postJson('/api/reports', $payload)
            ->assertOk()
            ->assertJsonPath('message', 'Segnalazione gia in attesa.');

        $this->assertDatabaseCount('reports', 1);
    }

    public function test_user_can_report_another_user_but_not_self_or_own_post(): void
    {
        $reporter = User::factory()->create();
        $target = User::factory()->create();

        $this
            ->actingAs($reporter, 'sanctum')
            ->postJson('/api/reports', [
                'target_type' => 'user',
                'target_id' => $target->id,
                'reason' => 'harassment',
            ])
            ->assertCreated();

        $this
            ->actingAs($reporter, 'sanctum')
            ->postJson('/api/reports', [
                'target_type' => 'user',
                'target_id' => $reporter->id,
                'reason' => 'other',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('target_id');

        $this
            ->actingAs($reporter, 'sanctum')
            ->postJson('/api/reports', [
                'target_type' => 'post',
                'target_id' => $this->postBy($reporter)->id,
                'reason' => 'other',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('target_id');
    }

    public function test_reports_are_rate_limited(): void
    {
        $reporter = User::factory()->create();
        $target = User::factory()->create();

        foreach (range(1, 10) as $attempt) {
            $this
                ->actingAs($reporter, 'sanctum')
                ->postJson('/api/reports', [
                    'target_type' => 'user',
                    'target_id' => $target->id,
                    'reason' => 'spam',
                ])
                ->assertSuccessful();
        }

        $this
            ->actingAs($reporter, 'sanctum')
            ->postJson('/api/reports', [
                'target_type' => 'user',
                'target_id' => $target->id,
                'reason' => 'spam',
            ])
            ->assertTooManyRequests();
    }

    private function postBy(User $author): Post
    {
        $location = Location::query()->firstOrCreate(
            ['name' => 'Luogo report', 'city' => 'Napoli'],
            [
                'short' => 'Report',
                'type' => 'altro',
                'latitude' => 40.8518,
                'longitude' => 14.2681,
                'geo_radius_meters' => 100,
                'icon' => 'location-outline',
                'is_active' => true,
            ],
        );

        return Post::query()->create([
            'author_id' => $author->id,
            'location_id' => $location->id,
            'text' => 'Post da segnalare',
            'sighting_date' => now()->toDateString(),
            'expires_at' => now()->addDay(),
            'status' => 'active',
        ]);
    }
}
