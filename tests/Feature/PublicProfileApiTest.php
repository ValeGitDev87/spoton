<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicProfileApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_choose_exactly_which_profile_details_are_public(): void
    {
        $viewer = User::factory()->create();
        $user = User::factory()->create([
            'bio' => 'Bio privata',
            'motto' => 'Vivi adesso',
            'favorite_song' => 'Una canzone privata',
            'photos' => ['/storage/one.jpg', '/storage/two.jpg'],
        ]);

        $this->actingAs($user, 'sanctum')->patchJson('/api/me/public-profile', [
            'show_bio' => false,
            'show_motto' => true,
            'show_favorite_song' => false,
            'public_photo_urls' => ['/storage/two.jpg'],
        ])->assertOk()
            ->assertJsonPath('data.user.show_motto', true)
            ->assertJsonPath('data.user.public_photo_urls.0', '/storage/two.jpg');

        $this->actingAs($viewer, 'sanctum')
            ->getJson("/api/users/{$user->id}/public-profile")
            ->assertOk()
            ->assertJsonPath('data.bio', null)
            ->assertJsonPath('data.motto', 'Vivi adesso')
            ->assertJsonPath('data.favorite_song', null)
            ->assertJsonPath('data.photos.0', '/storage/two.jpg')
            ->assertJsonMissing(['bio' => 'Bio privata'])
            ->assertJsonMissing(['favorite_song' => 'Una canzone privata']);
    }

    public function test_public_photos_must_belong_to_the_user_gallery(): void
    {
        $user = User::factory()->create(['photos' => ['/storage/owned.jpg']]);

        $this->actingAs($user, 'sanctum')->patchJson('/api/me/public-profile', [
            'public_photo_urls' => ['https://attacker.example/photo.jpg'],
        ])->assertUnprocessable()->assertJsonValidationErrors(['public_photo_urls.0']);
    }

    public function test_people_search_uses_display_name_and_never_exposes_email(): void
    {
        $viewer = User::factory()->create();
        $match = User::factory()->create([
            'display_name' => 'Sara Blu',
            'email' => 'sara-secret@example.com',
        ]);
        User::factory()->create(['display_name' => 'Marco Verde']);

        $this->actingAs($viewer, 'sanctum')
            ->getJson('/api/users/search?query=Sara')
            ->assertOk()
            ->assertJsonPath('data.0.id', $match->id)
            ->assertJsonMissing(['email' => 'sara-secret@example.com']);
    }
}
