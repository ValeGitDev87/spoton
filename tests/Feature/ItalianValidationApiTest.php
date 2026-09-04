<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ItalianValidationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_validation_is_returned_in_italian(): void
    {
        $this
            ->postJson('/api/auth/forgot-password', [])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Il campo email è obbligatorio.')
            ->assertJsonPath('errors.email.0', 'Il campo email è obbligatorio.');

        $this
            ->postJson('/api/auth/forgot-password', ['email' => 'non-valida'])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Il campo email deve contenere un indirizzo email valido.')
            ->assertJsonPath('errors.email.0', 'Il campo email deve contenere un indirizzo email valido.');
    }

    public function test_registration_validation_uses_readable_italian_attribute_names(): void
    {
        User::factory()->create(['email' => 'esistente@example.com']);

        $this
            ->postJson('/api/auth/register', [
                'display_name' => 'A',
                'email' => 'esistente@example.com',
                'password' => 'breve',
                'password_confirmation' => 'diversa',
            ])
            ->assertUnprocessable()
            ->assertJsonPath(
                'errors.display_name.0',
                'Il campo nome visualizzato deve contenere almeno 2 caratteri.',
            )
            ->assertJsonPath(
                'errors.email.0',
                'Il valore inserito per email è già in uso.',
            )
            ->assertJsonPath(
                'errors.password.0',
                'Il campo password deve contenere almeno 8 caratteri.',
            );
    }

    public function test_location_validation_does_not_expose_technical_field_names_in_english(): void
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user, 'sanctum')
            ->postJson('/api/locations', [
                'name' => '',
                'city' => '',
                'type' => 'inesistente',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.name.0', 'Il campo nome è obbligatorio.')
            ->assertJsonPath('errors.city.0', 'Il campo città è obbligatorio.')
            ->assertJsonPath('errors.latitude.0', 'Il campo latitudine è obbligatorio.')
            ->assertJsonPath('errors.longitude.0', 'Il campo longitudine è obbligatorio.');
    }
}
