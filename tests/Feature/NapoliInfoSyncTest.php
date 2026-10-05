<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NapoliInfoSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_dry_run_does_not_create_account_or_posts(): void
    {
        $location = Location::query()->create([
            'name' => 'Napoli', 'short' => 'Napoli', 'city' => 'Napoli', 'type' => 'altro',
            'latitude' => 40.85, 'longitude' => 14.27, 'geo_radius_meters' => 300,
            'is_active' => true, 'moderation_status' => 'approved',
        ]);
        config()->set('spoton.napoli_info.location_id', $location->id);
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-10-05 09:00:00', 'Europe/Rome'));
        Http::fake(['www.tangenzialedinapoli.it/*' => Http::response(
            '<html><a class="list-title" href="https://www.tangenzialedinapoli.it/w/chiusure-dal-5.10-al-11.10.2026">Chiusure dal 05.10 al 11.10.2026</a></html>'
        )]);

        $this->artisan('spoton:sync-napoli-info --dry-run')->assertExitCode(0);
        $this->assertDatabaseCount('posts', 0);
        $this->assertDatabaseCount('imported_napoli_events', 0);
        $this->assertDatabaseMissing('users', ['email' => 'info-napoli@spotonapp.cloud']);
    }

    public function test_sync_creates_one_official_post_and_deduplicates(): void
    {
        $location = Location::query()->create([
            'name' => 'Napoli', 'short' => 'Napoli', 'city' => 'Napoli', 'type' => 'altro',
            'latitude' => 40.85, 'longitude' => 14.27, 'geo_radius_meters' => 300,
            'is_active' => true, 'moderation_status' => 'approved',
        ]);
        config()->set('spoton.napoli_info.enabled', true);
        config()->set('spoton.napoli_info.location_id', $location->id);
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-10-05 09:00:00', 'Europe/Rome'));
        $first = '<html><a class="list-title" href="https://www.tangenzialedinapoli.it/w/chiusure-dal-5.10-al-11.10.2026?redirect=x">Chiusure dal 05.10 al 11.10.2026</a></html>';
        $updated = '<html><a class="list-title" href="https://www.tangenzialedinapoli.it/w/chiusure-dal-5.10-al-11.10.2026?redirect=x">Aggiornamento chiusure dal 05.10 al 11.10.2026</a></html>';
        $next = '<html><a class="list-title" href="https://www.tangenzialedinapoli.it/w/chiusure-dal-7.10-al-14.10.2026">Chiusure dal 07.10 al 14.10.2026</a>'
            .'<a class="list-title" href="https://www.tangenzialedinapoli.it/w/chiusure-dal-5.10-al-11.10.2026">Chiusure dal 05.10 al 11.10.2026</a></html>';
        Http::fake(['www.tangenzialedinapoli.it/*' => Http::sequence()->push($first)->push($first)->push($updated)->push($next)->push('', 503)]);

        $this->artisan('spoton:sync-napoli-info')->assertExitCode(0);
        $this->artisan('spoton:sync-napoli-info')->assertExitCode(0);
        $this->artisan('spoton:sync-napoli-info')->assertExitCode(0);

        $this->assertSame(1, Post::query()->count());
        $post = Post::query()->firstOrFail();
        $this->assertStringContainsString('Aggiornamento chiusure', $post->text);
        $this->assertSame('https://www.tangenzialedinapoli.it/w/chiusure-dal-5.10-al-11.10.2026', $post->source_url);
        $this->assertSame('weather_transport', $post->category);
        $this->assertTrue($post->author->is_system);
        $this->assertSame('SpotOn Info Napoli', $post->author->display_name);
        $this->assertSame('/images/share/spoton-symbol.png', $post->author->avatar_url);
        $this->assertNull($post->expires_at);
        $viewer = User::factory()->create();
        $this->actingAs($viewer, 'sanctum')->getJson('/api/posts/feed')
            ->assertOk()->assertJsonPath('data.posts.0.author.is_official', true)
            ->assertJsonPath('data.posts.0.author.avatar_url', '/images/share/spoton-symbol.png')
            ->assertJsonPath('data.posts.0.source_name', 'Tangenziale di Napoli');

        $this->travel(49)->hours();
        $this->getJson('/api/posts/feed')->assertOk()->assertJsonCount(1, 'data.posts');
        $this->getJson('/api/locations/'.$location->id.'/stories')->assertOk()->assertJsonCount(1, 'data.stories');
        $this->getJson('/api/locations/story-feed')->assertOk()->assertJsonPath('data.locations.0.stories_count', 1);

        $this->artisan('spoton:sync-napoli-info')->assertExitCode(0);
        $this->assertSame(2, Post::query()->count());
        $this->assertSame(1, Post::query()->currentlyActive()->count());
        $this->assertSame('expired', $post->refresh()->status);
        $this->getJson('/api/posts/feed')->assertOk()->assertJsonCount(1, 'data.posts');
        $this->artisan('spoton:sync-napoli-info')->assertExitCode(1);
        $this->assertSame(1, Post::query()->currentlyActive()->count());
    }

    public function test_source_failure_keeps_existing_posts(): void
    {
        config()->set('spoton.napoli_info.enabled', true);
        config()->set('spoton.napoli_info.location_id', 'missing');
        $this->artisan('spoton:sync-napoli-info')->assertExitCode(1);
        $this->assertDatabaseCount('posts', 0);
    }
}
