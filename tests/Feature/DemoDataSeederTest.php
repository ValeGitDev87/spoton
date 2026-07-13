<?php

namespace Tests\Feature;

use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoDataSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_data_seeder_creates_complete_demo_dataset(): void
    {
        $this->seed(DemoDataSeeder::class);

        $this->assertDatabaseHas('users', ['email' => 'admin@spoton.local', 'is_admin' => true]);
        $this->assertDatabaseHas('users', ['email' => 'test@example.com', 'is_admin' => false]);
        $this->assertDatabaseCount('locations', 6);
        $this->assertDatabaseCount('posts', 18);
        $this->assertDatabaseCount('chats', 4);
        $this->assertDatabaseHas('likes', []);
        $this->assertDatabaseHas('post_i_was_there', []);
        $this->assertDatabaseHas('presence_sessions', []);
        $this->assertDatabaseHas('messages', []);
    }
}
