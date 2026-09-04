<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\Post;
use App\Models\User;
use App\Services\Media\PublicPostMediaContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPostMediaContentTest extends TestCase
{
    use RefreshDatabase;

    public function test_exported_content_hides_ghost_identity_and_mentioned_user_names(): void
    {
        $author = User::factory()->create(['display_name' => 'Autore Segreto']);
        $mentioned = User::factory()->create(['display_name' => 'Sara Segreta']);
        $location = Location::query()->create([
            'name' => 'Luogo Media',
            'short' => 'Luogo Media',
            'city' => 'Napoli',
            'type' => 'altro',
            'latitude' => 40.83,
            'longitude' => 14.24,
            'geo_radius_meters' => 100,
            'is_active' => true,
        ]);
        $post = Post::query()->create([
            'author_id' => $author->id,
            'location_id' => $location->id,
            'text' => 'Ciao @Sara Segreta e @tutti.',
            'sighting_date' => now()->toDateString(),
            'is_anonymous' => true,
            'mentions_everyone' => true,
            'expires_at' => now()->addHour(),
            'status' => 'active',
        ]);
        $post->mentions()->attach($mentioned);
        $content = new PublicPostMediaContent;

        $this->assertSame('Ghost', $content->author($post->load('author')));
        $this->assertSame('Ciao @utente e @community.', $content->text($post));
        $this->assertStringNotContainsString('Autore Segreto', $content->text($post));
        $this->assertStringNotContainsString('Sara Segreta', $content->text($post));
    }
}
