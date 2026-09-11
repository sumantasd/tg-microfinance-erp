<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\DatabaseBackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DatabaseBackupTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $unauthorizedUser;
    protected DatabaseBackupService $backupService;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Setup RBAC permissions & roles
        $permissions = [
            'backup.view',
            'backup.create',
            'backup.download',
            'backup.delete',
            'settings.view',
        ];

        foreach ($permissions as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        $adminRole = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $adminRole->syncPermissions(Permission::all());

        $staffRole = Role::firstOrCreate(['name' => 'Staff', 'guard_name' => 'web']);
        $staffRole->syncPermissions(['settings.view']);

        // 2. Create test users
        $this->superAdmin = User::factory()->create([
            'email' => 'admin_backup@example.com',
            'status' => 'active',
        ]);
        $this->superAdmin->assignRole($adminRole);

        $this->unauthorizedUser = User::factory()->create([
            'email' => 'staff_nobackup@example.com',
            'status' => 'active',
        ]);
        $this->unauthorizedUser->assignRole($staffRole);

        $this->backupService = new DatabaseBackupService();
    }

    protected function tearDown(): void
    {
        // Clean up created test backup files from storage
        $directory = storage_path('app/private/backups');
        if (File::exists($directory)) {
            $files = File::files($directory);
            foreach ($files as $file) {
                if (str_starts_with($file->getFilename(), 'database_backup_')) {
                    File::delete($file->getRealPath());
                }
            }
        }

        parent::tearDown();
    }

    /** Test 1: Guest is redirected to login */
    public function test_guest_cannot_access_backup_page(): void
    {
        $response = $this->get('/admin/system/backup');

        $response->assertStatus(302);
        $response->assertRedirect('/admin/login');
    }

    /** Test 2: Unauthorized user gets 403 Forbidden */
    public function test_unauthorized_user_cannot_access_backup_page(): void
    {
        $response = $this->actingAs($this->unauthorizedUser)->get('/admin/system/backup');

        $response->assertStatus(403);
    }

    /** Test 3: Authorized Admin can view backup page */
    public function test_authorized_admin_can_access_backup_page(): void
    {
        $response = $this->actingAs($this->superAdmin)->get('/admin/system/backup');

        $response->assertStatus(200);
        $response->assertSee('Database Backup');
    }

    /** Test 4: Authorized Admin can create a database backup */
    public function test_authorized_admin_can_create_backup(): void
    {
        $response = $this->actingAs($this->superAdmin)->post('/admin/system/backup');

        $response->assertStatus(302);
        $response->assertRedirect(route('admin.system.backup.index'));
        $response->assertSessionHas('success');

        $backups = $this->backupService->getBackups();
        $this->assertNotEmpty($backups);

        $latest = $backups[0];
        $this->assertFileExists($latest['path']);
        $this->assertGreaterThan(0, $latest['size']);

        // Inspect file contents
        $content = File::get($latest['path']);
        $this->assertStringContainsString('-- TG Microfinance ERP Database Backup Dump', $content);
    }

    /** Test 5: Unauthorized user cannot create a backup */
    public function test_unauthorized_user_cannot_create_backup(): void
    {
        $response = $this->actingAs($this->unauthorizedUser)->post('/admin/system/backup');

        $response->assertStatus(403);
    }

    /** Test 6: Created backup appears in backup list view */
    public function test_created_backup_appears_in_backup_list(): void
    {
        $backup = $this->backupService->createBackup();

        $response = $this->actingAs($this->superAdmin)->get('/admin/system/backup');

        $response->assertStatus(200);
        $response->assertSee($backup['filename']);
        $response->assertSee($backup['size_formatted']);
    }

    /** Test 7: Authorized Admin can download a backup file */
    public function test_authorized_admin_can_download_backup(): void
    {
        $backup = $this->backupService->createBackup();

        $response = $this->actingAs($this->superAdmin)
            ->get(route('admin.system.backup.download', $backup['filename']));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/sql');
    }

    /** Test 8: Unauthorized user cannot download backup */
    public function test_unauthorized_user_cannot_download_backup(): void
    {
        $backup = $this->backupService->createBackup();

        $response = $this->actingAs($this->unauthorizedUser)
            ->get(route('admin.system.backup.download', $backup['filename']));

        $response->assertStatus(403);
    }

    /** Test 9: Download nonexistent file fails gracefully */
    public function test_download_nonexistent_file_redirects_with_error(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get('/admin/system/backup/download/database_backup_2099_01_01_000000.sql');

        $response->assertStatus(302);
        $response->assertSessionHas('error');
    }

    /** Test 10: Path traversal attack on download is rejected */
    public function test_path_traversal_attack_on_download_is_rejected(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get('/admin/system/backup/download/..%2F..%2F.env');

        // Should return 302 with session error or 404/403
        $this->assertTrue(in_array($response->getStatusCode(), [302, 403, 404]));
    }

    /** Test 11: Authorized Admin can delete a backup file */
    public function test_authorized_admin_can_delete_backup(): void
    {
        $backup = $this->backupService->createBackup();
        $this->assertFileExists($backup['path']);

        $response = $this->actingAs($this->superAdmin)
            ->delete(route('admin.system.backup.destroy', $backup['filename']));

        $response->assertStatus(302);
        $response->assertRedirect(route('admin.system.backup.index'));
        $response->assertSessionHas('success');

        $this->assertFileDoesNotExist($backup['path']);
    }

    /** Test 12: Unauthorized user cannot delete a backup */
    public function test_unauthorized_user_cannot_delete_backup(): void
    {
        $backup = $this->backupService->createBackup();

        $response = $this->actingAs($this->unauthorizedUser)
            ->delete(route('admin.system.backup.destroy', $backup['filename']));

        $response->assertStatus(403);
        $this->assertFileExists($backup['path']);
    }

    /** Test 13: Path traversal attack on delete is rejected */
    public function test_path_traversal_attack_on_delete_is_rejected(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->delete('/admin/system/backup/..%2F..%2F.env');

        $this->assertTrue(in_array($response->getStatusCode(), [302, 403, 404]));
    }
}
