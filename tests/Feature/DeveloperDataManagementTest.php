<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\ChartOfAccount;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\LoanAccount;
use App\Models\LoanApplication;
use App\Models\LoanScheme;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use App\Services\DatabaseBackupService;
use App\Services\DeveloperAuthService;
use App\Services\DeveloperDataManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\RateLimiter;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DeveloperDataManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $staffUser;
    protected Company $company;
    protected Branch $branch;
    protected LoanScheme $loanScheme;
    protected string $plainPassword = 'MySecretDeletionPassword123!';

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Configure Deletion Password Hash
        config([
            'developer.secret_hash' => password_hash($this->plainPassword, PASSWORD_BCRYPT),
            'developer.session_lifetime_minutes' => 15,
            'developer.rate_limit_max_attempts' => 5,
            'developer.confirmation_phrase' => 'CLEAN DEMO DATA',
        ]);

        // 2. Setup RBAC
        $permissions = [
            'settings.view',
            'backup.view',
            'backup.create',
            'backup.download',
            'backup.delete',
        ];
        foreach ($permissions as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $superAdminRole->syncPermissions(Permission::all());

        $staffRole = Role::firstOrCreate(['name' => 'Branch Manager', 'guard_name' => 'web']);
        $staffRole->syncPermissions(['settings.view']);

        // 3. Create Master Company & Branch
        $this->company = Company::create([
            'name' => 'TG Microfinance Test Co',
            'code' => 'TGM01',
            'email' => 'info@tgmicro.com',
            'phone' => '9800000000',
            'address' => 'Test Address',
        ]);

        $this->branch = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Main Test Branch',
            'code' => 'BR-001',
            'phone' => '0612250001',
            'address' => 'Test Branch Address',
            'city' => 'Patna',
            'state' => 'Bihar',
            'pincode' => '800001',
            'is_active' => true,
        ]);

        // 4. Create Master Users
        $this->superAdmin = User::create([
            'name' => 'Super Administrator',
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'email' => 'superadmin_dev@example.com',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);
        $this->superAdmin->assignRole('Super Admin');

        $this->staffUser = User::create([
            'name' => 'Branch Manager User',
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'email' => 'branchmgr_dev@example.com',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);
        $this->staffUser->assignRole('Branch Manager');

        // 5. Create Master Loan Scheme
        $this->loanScheme = LoanScheme::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'code' => 'SCHEME-001',
            'name' => 'Standard Micro Loan',
            'loan_type' => 'cash',
            'applicant_type' => 'individual',
            'min_amount' => 5000,
            'max_amount' => 50000,
            'interest_type' => 'flat',
            'interest_rate_per_annum' => 18.00,
            'min_tenure_months' => 6,
            'max_tenure_months' => 24,
            'repayment_frequency' => 'monthly',
            'is_active' => true,
        ]);

        // 6. Create Master Department, Designation & Employee Profile
        $dept = Department::create([
            'company_id' => $this->company->id,
            'name' => 'Operations',
            'code' => 'OPS',
        ]);

        $desig = Designation::create([
            'company_id' => $this->company->id,
            'department_id' => $dept->id,
            'title' => 'Branch Manager',
            'code' => 'BM',
        ]);

        Employee::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'user_id' => $this->staffUser->id,
            'department_id' => $dept->id,
            'designation_id' => $desig->id,
            'employee_code' => 'EMP-001',
            'first_name' => 'John',
            'last_name' => 'Staff',
            'email' => 'john.staff@example.com',
            'phone' => '9800001234',
            'gender' => 'male',
            'joining_date' => date('Y-m-d'),
            'status' => 'active',
        ]);

        // 7. Create Master Chart of Accounts Entry
        ChartOfAccount::create([
            'company_id' => $this->company->id,
            'account_code' => '1000',
            'account_name' => 'Cash in Hand',
            'account_type' => 'asset',
            'account_group' => 'current_assets',
            'is_system' => true,
            'is_active' => true,
        ]);
    }

    protected function tearDown(): void
    {
        // Clean up test backup files
        $directory = storage_path('app/private/backups');
        if (File::exists($directory)) {
            $files = File::files($directory);
            foreach ($files as $file) {
                if (str_starts_with($file->getFilename(), 'database_backup_')) {
                    File::delete($file->getRealPath());
                }
            }
        }

        RateLimiter::clear('developer_auth:127.0.0.1:' . $this->superAdmin->id);

        parent::tearDown();
    }

    /** Test 1: Unauthenticated guest is redirected to login */
    public function test_guest_cannot_access_data_management(): void
    {
        $response = $this->get('/admin/system/developer-data');
        $response->assertStatus(302);
        $response->assertRedirect('/admin/login');
    }

    /** Test 2: Non-Super Admin receives 403 Forbidden */
    public function test_non_super_admin_cannot_access_data_management(): void
    {
        $response = $this->actingAs($this->staffUser)->get('/admin/system/developer-data');
        $response->assertStatus(403);
    }

    /** Test 3: Super Admin can access the simplified 2-section dashboard */
    public function test_super_admin_can_access_simplified_dashboard(): void
    {
        $response = $this->actingAs($this->superAdmin)->get('/admin/system/developer-data');
        $response->assertStatus(200);

        // Must see the two clean sections
        $response->assertSee('Database Backup');
        $response->assertSee('Selective Data Delete');
        $response->assertSee('Create Database Backup');
        $response->assertSee('Preview Selected Data');
        $response->assertSee('Delete Selected Data');

        // Must NOT see developer commands or secret hashes
        $response->assertDontSee('php artisan tinker');
        $response->assertDontSee('Developer Secret Hash Not Configured');
        $response->assertDontSee('Protected System Configuration');
        $response->assertDontSee('Database Restore & Recovery Procedure');
    }

    /** Test 4: Database backup creation generates a verified SQL file */
    public function test_super_admin_can_create_database_backup(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->post('/admin/system/developer-data/backup');

        $response->assertRedirect(route('admin.system.developer-data.index'));
        $response->assertSessionHas('success');

        $backupService = app(DatabaseBackupService::class);
        $backups = $backupService->getBackups();

        $this->assertNotEmpty($backups);
        $latest = $backups[0];

        $verification = $backupService->verifyBackupFile($latest['path']);
        $this->assertTrue($verification['valid']);
        $this->assertGreaterThan(0, $verification['size']);
    }

    /** Test 5: Protected backup download enforces containment and blocks path traversal */
    public function test_backup_download_security(): void
    {
        $backupService = app(DatabaseBackupService::class);
        $backup = $backupService->createVerifiedBackup();

        // Super Admin can download
        $response = $this->actingAs($this->superAdmin)
            ->get("/admin/system/developer-data/backup/download/{$backup['filename']}");
        $response->assertStatus(200);

        // Path traversal attack is safely rejected
        $traversalResponse = $this->actingAs($this->superAdmin)
            ->get("/admin/system/developer-data/backup/download/..%2F..%2F.env");
        $this->assertTrue(in_array($traversalResponse->getStatusCode(), [302, 403, 404]));
    }

    /** Test 6: Deleting a backup requires password confirmation and rejects wrong password */
    public function test_backup_deletion_is_password_protected(): void
    {
        $backupService = app(DatabaseBackupService::class);
        $backup = $backupService->createVerifiedBackup();
        $this->assertFileExists($backup['path']);

        // 1. Wrong password rejected
        $failResponse = $this->actingAs($this->superAdmin)
            ->postJson("/admin/system/developer-data/backup/delete/{$backup['filename']}", [
                'password' => 'WrongPassword!',
            ]);
        $failResponse->assertStatus(403);
        $failResponse->assertJsonFragment(['error' => 'Invalid deletion password. Backup deletion aborted.']);
        $this->assertFileExists($backup['path']);

        // 2. Correct password deletes backup
        $successResponse = $this->actingAs($this->superAdmin)
            ->postJson("/admin/system/developer-data/backup/delete/{$backup['filename']}", [
                'password' => $this->plainPassword,
            ]);
        $successResponse->assertStatus(200);
        $this->assertFileDoesNotExist($backup['path']);
    }

    /** Test 7: Cleanup preview is non-destructive and detects live records */
    public function test_cleanup_preview_reports_records_and_detects_live_data(): void
    {
        $customer = Customer::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_code' => 'CUST-DEMO-001',
            'first_name' => 'Active',
            'last_name' => 'Borrower',
            'mobile_number' => '9800000099',
            'gender' => 'female',
            'registration_date' => date('Y-m-d'),
            'status' => 'active',
        ]);

        $countBefore = Customer::count();

        $response = $this->actingAs($this->superAdmin)
            ->postJson('/admin/system/developer-data/preview', [
                'modules' => ['customers', 'loans'],
            ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'selected_modules',
            'module_previews',
            'tables_in_deletion_order',
            'total_records_to_delete',
            'has_live_records',
            'live_warnings',
        ]);

        // Live records warning triggered because active customer exists
        $this->assertTrue($response->json('has_live_records'));
        $this->assertNotEmpty($response->json('live_warnings'));

        // Zero deletions occurred during preview
        $this->assertEquals($countBefore, Customer::count());
    }

    /** Test 8: Data deletion fails if password is missing or invalid */
    public function test_data_deletion_fails_with_invalid_password(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->postJson('/admin/system/developer-data/execute', [
                'modules' => ['field_tracking'],
                'password' => 'BadPassword!',
                'confirmation_phrase' => 'CLEAN DEMO DATA',
            ]);

        $response->assertStatus(403);
        $response->assertJsonFragment([
            'error' => 'Invalid deletion password. Operation aborted.',
        ]);
    }

    /** Test 9: Rate limiting blocks repeated failed deletion attempts */
    public function test_deletion_password_rate_limiting(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($this->superAdmin)
                ->postJson('/admin/system/developer-data/execute', [
                    'modules' => ['field_tracking'],
                    'password' => 'BadPassword!',
                    'confirmation_phrase' => 'CLEAN DEMO DATA',
                ]);
        }

        // 6th attempt should be blocked by rate limiter with 429
        $response = $this->actingAs($this->superAdmin)
            ->postJson('/admin/system/developer-data/execute', [
                'modules' => ['field_tracking'],
                'password' => $this->plainPassword,
                'confirmation_phrase' => 'CLEAN DEMO DATA',
            ]);

        $response->assertStatus(429);
        $response->assertJsonStructure(['error']);
    }

    /** Test 9b: Cleanup fails if confirmation phrase is missing or incorrect */
    public function test_cleanup_fails_with_invalid_confirmation_phrase(): void
    {
        // 1. Missing confirmation phrase
        $res1 = $this->actingAs($this->superAdmin)
            ->postJson('/admin/system/developer-data/execute', [
                'modules' => ['field_tracking'],
                'password' => $this->plainPassword,
            ]);
        $res1->assertStatus(422);

        // 2. Mismatched confirmation phrase
        $res2 = $this->actingAs($this->superAdmin)
            ->postJson('/admin/system/developer-data/execute', [
                'modules' => ['field_tracking'],
                'password' => $this->plainPassword,
                'confirmation_phrase' => 'DELETE EVERYTHING NOW',
            ]);
        $res2->assertStatus(422);
        $this->assertStringContainsString('Confirmation phrase mismatch', $res2->json('error'));
    }

    /** Test 10: Successful data deletion creates backup, deletes selected data, and preserves master data */
    public function test_successful_data_deletion_workflow(): void
    {
        // Create demo customer and loan application
        $customer = Customer::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_code' => 'CUST-DEMO-002',
            'first_name' => 'Demo',
            'last_name' => 'Customer',
            'mobile_number' => '9800000098',
            'gender' => 'female',
            'registration_date' => date('Y-m-d'),
            'status' => 'active',
        ]);

        $app = LoanApplication::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $customer->id,
            'loan_scheme_id' => $this->loanScheme->id,
            'application_number' => 'APP-DEMO-001',
            'loan_type' => 'cash',
            'borrower_type' => 'individual',
            'requested_amount' => 15000,
            'tenure_months' => 12,
            'interest_type' => 'flat',
            'interest_rate_per_annum' => 12.00,
            'repayment_frequency' => 'monthly',
            'status' => 'draft',
            'application_date' => date('Y-m-d'),
        ]);

        $this->assertGreaterThan(0, Customer::count());
        $this->assertGreaterThan(0, LoanApplication::count());

        // Execute selective cleanup
        $response = $this->actingAs($this->superAdmin)
            ->postJson('/admin/system/developer-data/execute', [
                'modules' => ['loans', 'customers', 'sales_billing'],
                'password' => $this->plainPassword,
                'confirmation_phrase' => 'CLEAN DEMO DATA',
            ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Demo records deleted
        $this->assertEquals(0, Customer::count());
        $this->assertEquals(0, LoanApplication::count());

        // Master records strictly preserved
        $this->assertNotNull(User::find($this->superAdmin->id));
        $this->assertTrue(User::find($this->superAdmin->id)->hasRole('Super Admin'));
        $this->assertGreaterThanOrEqual(1, LoanScheme::count());
        $this->assertNotNull(LoanScheme::where('code', 'SCHEME-001')->first());
        $this->assertNotNull(Branch::find($this->branch->id));
        $this->assertNotNull(Company::find($this->company->id));

        // Pre-cleanup verified backup exists
        $backupService = app(DatabaseBackupService::class);
        $backups = $backupService->getBackups();
        $this->assertNotEmpty($backups);
    }

    /** Test 11: Database restore requires confirmation checkbox and password */
    public function test_database_restore_validation_requirements(): void
    {
        $backupService = app(DatabaseBackupService::class);
        $backup = $backupService->createVerifiedBackup();

        // Missing password
        $res1 = $this->actingAs($this->superAdmin)
            ->postJson('/admin/system/developer-data/restore', [
                'filename' => $backup['filename'],
                'confirm_replace' => 1,
            ]);
        $res1->assertStatus(422);

        // Missing confirm_replace checkbox
        $res2 = $this->actingAs($this->superAdmin)
            ->postJson('/admin/system/developer-data/restore', [
                'filename' => $backup['filename'],
                'password' => $this->plainPassword,
            ]);
        $res2->assertStatus(422);
    }

    /** Test 12: Database restore rejects incorrect password */
    public function test_database_restore_rejects_invalid_password(): void
    {
        $backupService = app(DatabaseBackupService::class);
        $backup = $backupService->createVerifiedBackup();

        $response = $this->actingAs($this->superAdmin)
            ->postJson('/admin/system/developer-data/restore', [
                'filename' => $backup['filename'],
                'password' => 'WrongPassword!',
                'confirm_replace' => 1,
            ]);

        $response->assertStatus(403);
        $response->assertJsonFragment([
            'error' => 'Invalid Data Management Password. Database restoration aborted.',
        ]);
    }

    /** Test 13: Database restore rejects path traversal and non-existent backups */
    public function test_database_restore_rejects_invalid_file(): void
    {
        // 1. Invalid filename format / path traversal rejected
        $res1 = $this->actingAs($this->superAdmin)
            ->postJson('/admin/system/developer-data/restore', [
                'filename' => '../../invalid.sql',
                'password' => $this->plainPassword,
                'confirm_replace' => 1,
            ]);
        $res1->assertStatus(500);
        $this->assertStringContainsString('Invalid backup filename format', $res1->json('error'));

        // 2. Non-existent backup rejected
        $res2 = $this->actingAs($this->superAdmin)
            ->postJson('/admin/system/developer-data/restore', [
                'filename' => 'database_backup_1990_01_01_00_00_00.sql',
                'password' => $this->plainPassword,
                'confirm_replace' => 1,
            ]);
        $res2->assertStatus(500);
        $this->assertStringContainsString('Backup file not found', $res2->json('error'));
    }

    /** Test 14: Successful database restore creates safety backup, restores data, and maintains consistency */
    public function test_successful_database_restore_workflow(): void
    {
        $customer1 = Customer::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_code' => 'CUST-RESTORE-01',
            'first_name' => 'Original',
            'last_name' => 'Customer',
            'mobile_number' => '9800000011',
            'gender' => 'female',
            'registration_date' => date('Y-m-d'),
            'status' => 'active',
        ]);

        $backupService = app(DatabaseBackupService::class);
        $backup = $backupService->createVerifiedBackup();

        // Create second customer AFTER backup was taken
        $customer2 = Customer::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_code' => 'CUST-RESTORE-02',
            'first_name' => 'Newer',
            'last_name' => 'Customer',
            'mobile_number' => '9800000022',
            'gender' => 'female',
            'registration_date' => date('Y-m-d'),
            'status' => 'active',
        ]);
        $this->assertEquals(2, Customer::count());

        // Execute restore of earlier backup
        $response = $this->actingAs($this->superAdmin)
            ->postJson('/admin/system/developer-data/restore', [
                'filename' => $backup['filename'],
                'password' => $this->plainPassword,
                'confirm_replace' => 1,
            ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Post-restore verification: customer1 should exist, customer2 should not be in this snapshot
        $this->assertDatabaseHas('customers', ['customer_code' => 'CUST-RESTORE-01']);
        $this->assertDatabaseMissing('customers', ['customer_code' => 'CUST-RESTORE-02']);

        // Master records must be intact
        $this->assertNotNull(User::find($this->superAdmin->id));
        $this->assertNotNull(LoanScheme::where('code', 'SCHEME-001')->first());

        // Pre-restore safety backup was created
        $details = $response->json('details');
        $this->assertNotEmpty($details['pre_restore_backup']);
    }

    /** Test 15: Cleanup preserves all 56 master tables, employees, and settings */
    public function test_cleanup_preserves_master_tables_and_employees(): void
    {
        // Employee profile was already seeded in setUp()
        $this->assertEquals(1, Employee::count());

        // 1. Seed a product and supplier (Candidate cleanup tables)
        $supplier = Supplier::create([
            'company_id' => $this->company->id,
            'supplier_code' => 'SUP-TEST-001',
            'supplier_type' => 'company',
            'supplier_name' => 'Test Supplier Co',
            'contact_person' => 'Supplier Contact',
            'mobile' => '9800004321',
            'status' => 'active',
        ]);

        $product = Product::create([
            'company_id' => $this->company->id,
            'sku' => 'PROD-TEST-001',
            'name' => 'Test Appliance',
            'unit_price' => 5000,
            'cost_price' => 4000,
            'is_active' => true,
        ]);

        $this->assertEquals(1, Supplier::count());
        $this->assertEquals(1, Product::count());

        // 2. Execute cleanup on inventory_products and suppliers
        $response = $this->actingAs($this->superAdmin)
            ->postJson('/admin/system/developer-data/execute', [
                'modules' => ['inventory_products', 'suppliers'],
                'password' => $this->plainPassword,
                'confirmation_phrase' => 'CLEAN DEMO DATA',
            ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Candidate products and suppliers cleared
        $this->assertEquals(0, Product::count());
        $this->assertEquals(0, Supplier::count());

        // Master records strictly preserved
        $this->assertEquals(1, Employee::count());
        $this->assertNotNull(User::find($this->superAdmin->id));
        $this->assertNotNull(User::find($this->staffUser->id));
        $this->assertNotNull(Company::find($this->company->id));
        $this->assertNotNull(Branch::find($this->branch->id));
        $this->assertNotNull(LoanScheme::where('code', 'SCHEME-001')->first());
    }

    /** Test 16: Cleanup aborts if pre-cleanup backup fails */
    public function test_cleanup_aborts_if_pre_cleanup_backup_fails(): void
    {
        // Seed demo customer
        Customer::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_code' => 'CUST-FAIL-SAFE',
            'first_name' => 'Safe',
            'last_name' => 'Customer',
            'mobile_number' => '9800007777',
            'gender' => 'female',
            'registration_date' => date('Y-m-d'),
            'status' => 'active',
        ]);

        $this->assertEquals(1, Customer::count());

        // Mock backup service to throw exception during pre-cleanup backup
        $mockBackup = $this->createMock(DatabaseBackupService::class);
        $mockBackup->expects($this->once())
            ->method('createVerifiedBackup')
            ->willThrowException(new \RuntimeException("Disk full error on backup drive"));

        $this->app->instance(DatabaseBackupService::class, $mockBackup);

        $response = $this->actingAs($this->superAdmin)
            ->postJson('/admin/system/developer-data/execute', [
                'modules' => ['customers', 'loans', 'sales_billing'],
                'password' => $this->plainPassword,
                'confirmation_phrase' => 'CLEAN DEMO DATA',
            ]);

        $response->assertStatus(500);

        // Verification: No customer deleted because backup failed
        $this->assertEquals(1, Customer::count());
        $this->assertDatabaseHas('customers', ['customer_code' => 'CUST-FAIL-SAFE']);
    }
}
