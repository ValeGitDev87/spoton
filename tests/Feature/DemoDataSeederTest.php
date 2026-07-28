<?php

namespace Tests\Feature;

use App\Models\Challenge;
use Database\Seeders\DemoDataSeeder;
use Database\Seeders\DemoUsersSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoDataSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_data_seeder_creates_complete_demo_dataset(): void
    {
        $this->seed(DemoDataSeeder::class);

        $this->assertDatabaseHas('users', ['email' => 'admin@spoton.local', 'is_admin' => true]);
        foreach (['luca@test.it', 'sara@test.it', 'marco@test.it', 'giulia@test.it', 'elena@test.it'] as $email) {
            $this->assertDatabaseHas('users', ['email' => $email, 'is_admin' => false]);
        }
        $this->assertDatabaseCount('users', 6);
        $this->assertDatabaseCount('locations', 6);
        $this->assertDatabaseCount('posts', 18);
        $this->assertDatabaseCount('chats', 4);
        $this->assertDatabaseHas('likes', []);
        $this->assertDatabaseHas('post_i_was_there', []);
        $this->assertDatabaseHas('presence_sessions', []);
        $this->assertDatabaseHas('messages', []);
        $this->assertDatabaseHas('favorites', []);
        $this->assertDatabaseHas('comments', []);
        $this->assertDatabaseHas('challenges', []);
        $this->assertDatabaseHas('posts', ['is_anonymous' => true]);

        $counterProposal = Challenge::query()
            ->where('status', Challenge::STATUS_COUNTER_PENDING)
            ->with('post')
            ->sole();

        $this->assertSame($counterProposal->post->author_id, $counterProposal->target_user_id);
        $this->assertSame($counterProposal->counter_proposed_by, $counterProposal->challenger_id);
    }

    public function test_demo_users_seeder_is_repeatable_and_creates_five_test_users(): void
    {
        $this->seed(DemoUsersSeeder::class);
        $this->seed(DemoUsersSeeder::class);

        foreach (array_column(DemoUsersSeeder::USERS, 'email') as $email) {
            $this->assertDatabaseHas('users', ['email' => $email, 'is_admin' => false]);
        }

        $this->assertDatabaseCount('users', 6);
    }
}
