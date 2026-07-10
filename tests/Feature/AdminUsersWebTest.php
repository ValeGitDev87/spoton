<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUsersWebTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_users_table(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        User::factory()->create([
            'display_name' => 'Utente Test',
            'email' => 'utente@example.com',
            'is_admin' => false,
        ]);

        $this
            ->actingAs($admin)
            ->get('/admin/users')
            ->assertOk()
            ->assertSee('Utente Test')
            ->assertSee('utente@example.com')
            ->assertSee('Utente');
    }
}
