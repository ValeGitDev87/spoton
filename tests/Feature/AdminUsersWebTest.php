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

    public function test_admin_can_suspend_and_reactivate_user(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create(['is_admin' => false]);

        $this
            ->actingAs($admin)
            ->patch("/admin/users/{$user->id}/status", [
                'status' => 'suspended',
                'reason' => 'Controllo moderazione.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'is_suspended' => true,
            'suspension_reason' => 'Controllo moderazione.',
        ]);

        $this
            ->actingAs($admin)
            ->patch("/admin/users/{$user->id}/status", ['status' => 'active'])
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'is_suspended' => false,
            'suspension_reason' => null,
        ]);
    }

    public function test_admin_can_grant_and_revoke_the_everyone_mention_permission(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create(['can_mention_everyone' => false]);

        $this->actingAs($admin)
            ->patch("/admin/users/{$user->id}/mention-permission", [
                'can_mention_everyone' => true,
            ])
            ->assertRedirect();

        $this->assertTrue($user->refresh()->can_mention_everyone);

        $this->actingAs($admin)
            ->patch("/admin/users/{$user->id}/mention-permission", [
                'can_mention_everyone' => false,
            ])
            ->assertRedirect();

        $this->assertFalse($user->refresh()->can_mention_everyone);
    }
}
