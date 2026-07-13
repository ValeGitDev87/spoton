<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class AdminBackupsWebTest extends TestCase
{
    use RefreshDatabase;

    private string $backupPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->backupPath = storage_path('framework/testing/backups');
        File::ensureDirectoryExists($this->backupPath);
        File::cleanDirectory($this->backupPath);
        config(['services.spoton_backup.path' => $this->backupPath]);
    }

    public function test_admin_can_list_and_download_allowed_backups(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        File::put($this->backupPath.'/spotonapp_db_20260713_100000.dump', 'dump-content');
        File::put($this->backupPath.'/segreto.txt', 'no');

        $this
            ->actingAs($admin)
            ->get('/admin/backups')
            ->assertOk()
            ->assertSee('spotonapp_db_20260713_100000.dump')
            ->assertDontSee('segreto.txt');

        $this
            ->actingAs($admin)
            ->get('/admin/backups/spotonapp_db_20260713_100000.dump')
            ->assertOk()
            ->assertHeader('content-disposition');
    }

    public function test_non_admin_cannot_access_backups_and_invalid_file_is_not_found(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($user)->get('/admin/backups')->assertForbidden();
        $this->actingAs($admin)->get('/admin/backups/not_allowed.txt')->assertNotFound();
        $this->actingAs($admin)->get('/admin/backups/..%2F.env')->assertNotFound();
    }
}
