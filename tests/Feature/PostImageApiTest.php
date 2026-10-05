<?php

namespace Tests\Feature;

use App\Jobs\ExpirePostsJob;
use App\Models\Location;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PostImageApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_ghost_photo_is_reencoded_and_removed_on_expiration(): void
    {
        Storage::fake('public');
        $location = Location::query()->create([
            'name' => 'Napoli', 'short' => 'Napoli', 'city' => 'Napoli', 'type' => 'altro',
            'latitude' => 40.85, 'longitude' => 14.27, 'geo_radius_meters' => 300,
            'is_active' => true, 'moderation_status' => 'approved',
        ]);
        $author = User::factory()->create();
        $response = $this->actingAs($author, 'sanctum')->postJson('/api/posts', [
            'location_id' => $location->id,
            'text' => 'Foto della strada',
            'category' => 'weather_transport',
            'sighting_date' => today()->toDateString(),
            'is_anonymous' => true,
            'image' => UploadedFile::fake()->image('originale.png', 800, 600),
        ])->assertCreated()->assertJsonPath('data.image.mime', 'image/jpeg');

        $post = Post::query()->findOrFail($response->json('data.id'));
        Storage::disk('public')->assertExists($post->image_path);
        $this->assertStringStartsWith("\xff\xd8", Storage::disk('public')->get($post->image_path));
        $this->assertStringNotContainsString($author->id, $post->image_path);
        $this->actingAs(User::factory()->create(), 'sanctum')->getJson('/api/posts/'.$post->id)
            ->assertOk()->assertJsonPath('data.author.id', null)
            ->assertJsonPath('data.image.url', $post->image_url);

        $path = $post->image_path;
        $post->update(['expires_at' => now()->subMinute()]);
        app(ExpirePostsJob::class)->handle();
        Storage::disk('public')->assertMissing($path);
        $this->assertNull($post->refresh()->image_path);
    }

    public function test_owner_can_remove_photo_without_removing_post(): void
    {
        Storage::fake('public');
        $location = Location::query()->create([
            'name' => 'Napoli', 'short' => 'Napoli', 'city' => 'Napoli', 'type' => 'altro',
            'latitude' => 40.85, 'longitude' => 14.27, 'geo_radius_meters' => 300,
            'is_active' => true, 'moderation_status' => 'approved',
        ]);
        $author = User::factory()->create();
        $id = $this->actingAs($author, 'sanctum')->postJson('/api/posts', [
            'location_id' => $location->id, 'text' => 'Foto della strada',
            'sighting_date' => today()->toDateString(),
            'image' => UploadedFile::fake()->image('foto.jpg', 600, 400),
        ])->assertCreated()->json('data.id');
        $path = Post::query()->findOrFail($id)->image_path;
        $this->patchJson('/api/posts/'.$id, ['remove_image' => true])
            ->assertOk()->assertJsonPath('data.image', null);
        Storage::disk('public')->assertMissing($path);
        $this->assertSame('active', Post::query()->findOrFail($id)->status);
    }
}
