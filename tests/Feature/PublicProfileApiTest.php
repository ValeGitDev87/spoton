<?php

namespace Tests\Feature;

use App\Models\Favorite;
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

    public function test_people_search_without_query_is_ranked_by_interactions_and_paginated(): void
    {
        $viewer = User::factory()->create();
        $alphabeticalFirst = User::factory()->create(['display_name' => 'Anna Nessuna Interazione']);
        $favorite = User::factory()->create(['display_name' => 'Zara Preferita']);
        User::factory()->create(['display_name' => 'Mario Generico']);

        Favorite::query()->create([
            'owner_id' => $viewer->id,
            'target_user_id' => $favorite->id,
            'target_name' => $favorite->display_name,
        ]);

        $response = $this->actingAs($viewer, 'sanctum')
            ->getJson('/api/users/search?per_page=2');

        $response
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $favorite->id)
            ->assertJsonPath('data.1.id', $alphabeticalFirst->id)
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.last_page', 2)
            ->assertJsonPath('meta.total', 3);
    }

    public function test_public_profile_reports_favorite_state(): void
    {
        $viewer = User::factory()->create();
        $target = User::factory()->create();

        $this->actingAs($viewer, 'sanctum')
            ->getJson("/api/users/{$target->id}/public-profile")
            ->assertOk()
            ->assertJsonPath('data.is_favorite', false);

        Favorite::query()->create([
            'owner_id' => $viewer->id,
            'target_user_id' => $target->id,
            'target_name' => $target->display_name,
        ]);

        $this->actingAs($viewer, 'sanctum')
            ->getJson("/api/users/{$target->id}/public-profile")
            ->assertOk()
            ->assertJsonPath('data.is_favorite', true);
    }
}
