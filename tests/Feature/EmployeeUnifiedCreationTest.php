<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\User;
use App\Services\EmployeeService;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EmployeeUnifiedCreationTest extends TestCase
{
    use RefreshDatabase;

    protected Company $companyA;
    protected Company $companyB;
    protected Branch $branchA;
    protected Branch $branchB;
    protected Department $deptA;
    protected Designation $desigA;
    protected User $superAdmin;
    protected User $companyAdminA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RbacSeeder::class,
            AdminUserSeeder::class,
        ]);

        $this->companyA = Company::create([
            'name' => 'Company Alpha',
            'code' => 'ALPH',
            'email' => 'alpha@test.com',
            'phone' => '9876543210',
            'address' => '123 Alpha St',
            'is_active' => true,
        ]);

        $this->companyB = Company::create([
            'name' => 'Company Beta',
            'code' => 'BETA',
            'email' => 'beta@test.com',
            'phone' => '9876543211',
            'address' => '456 Beta St',
            'is_active' => true,
        ]);

        $this->branchA = Branch::create([
            'company_id' => $this->companyA->id,
            'name' => 'Alpha Main Branch',
            'code' => 'A-001',
            'email' => 'branchA@alpha.com',
            'phone' => '9876543220',
            'address' => '100 Branch Road',
            'city' => 'Kolkata',
            'state' => 'West Bengal',
            'pincode' => '700001',
            'is_active' => true,
        ]);

        $this->branchB = Branch::create([
            'company_id' => $this->companyB->id,
            'name' => 'Beta Main Branch',
            'code' => 'B-001',
            'email' => 'branchB@beta.com',
            'phone' => '9876543221',
            'address' => '200 Branch Road',
            'city' => 'Mumbai',
            'state' => 'Maharashtra',
            'pincode' => '400001',
            'is_active' => true,
        ]);

        $this->deptA = Department::create([
            'company_id' => $this->companyA->id,
            'name' => 'Credit & Operations',
            'code' => 'CR-OPS',
            'is_active' => true,
        ]);

        $this->desigA = Designation::create([
            'company_id' => $this->companyA->id,
            'department_id' => $this->deptA->id,
            'title' => 'Loan Officer',
            'code' => 'LO',
            'is_active' => true,
        ]);

        $this->superAdmin = User::where('email', 'admin@grihalaxmifinance.com')->first();
        if (!$this->superAdmin) {
            $this->superAdmin = User::create([
                'name' => 'Super Administrator',
                'email' => 'admin@grihalaxmifinance.com',
                'password' => Hash::make('Admin@123456'),
                'status' => 'active',
                'company_id' => $this->companyA->id,
                'branch_id' => $this->branchA->id,
            ]);
            $this->superAdmin->assignRole('Super Admin');
        }

        $this->companyAdminA = User::create([
            'name' => 'Company Admin Alpha',
            'email' => 'admin@alpha.com',
            'mobile_number' => '9876500001',
            'password' => Hash::make('Password@123'),
            'status' => 'active',
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA->id,
        ]);
        $this->companyAdminA->assignRole('Company Admin');
    }

    /**
     * Phase B Tests: Employee Code Generator
     */
    public function test_employee_code_format_and_sequence_starts_at_0001(): void
    {
        $employeeService = app(EmployeeService::class);
        $year = 2026;

        $code1 = $employeeService->generateEmployeeCode($this->companyA->id, $year);
        $this->assertEquals("GLF-EMP-{$year}-0001", $code1);

        // Create an employee with this code
        Employee::create([
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA->id,
            'department_id' => $this->deptA->id,
            'designation_id' => $this->desigA->id,
            'employee_code' => $code1,
            'first_name' => 'First',
            'last_name' => 'Employee',
            'gender' => 'male',
            'email' => 'emp1@test.com',
            'phone' => '9876543001',
            'joining_date' => '2026-01-01',
            'employment_type' => 'full_time',
            'status' => 'active',
        ]);

        $code2 = $employeeService->generateEmployeeCode($this->companyA->id, $year);
        $this->assertEquals("GLF-EMP-{$year}-0002", $code2);
    }

    public function test_employee_code_independent_per_company_and_per_year(): void
    {
        $employeeService = app(EmployeeService::class);

        // Company A in 2026
        $codeA2026 = $employeeService->generateEmployeeCode($this->companyA->id, 2026);
        $this->assertEquals('GLF-EMP-2026-0001', $codeA2026);

        // Company B in 2026 should also start at 0001 independently
        $codeB2026 = $employeeService->generateEmployeeCode($this->companyB->id, 2026);
        $this->assertEquals('GLF-EMP-2026-0001', $codeB2026);

        // Company A in 2027 should reset to 0001
        $codeA2027 = $employeeService->generateEmployeeCode($this->companyA->id, 2027);
        $this->assertEquals('GLF-EMP-2027-0001', $codeA2027);
    }

    public function test_employee_code_does_not_reuse_soft_deleted_codes(): void
    {
        $employeeService = app(EmployeeService::class);
        $year = 2026;

        $emp1 = Employee::create([
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA->id,
            'department_id' => $this->deptA->id,
            'designation_id' => $this->desigA->id,
            'employee_code' => "GLF-EMP-{$year}-0001",
            'first_name' => 'Soft',
            'last_name' => 'Deleted',
            'gender' => 'male',
            'email' => 'softdeleted@test.com',
            'phone' => '9876543002',
            'joining_date' => '2026-01-01',
            'employment_type' => 'full_time',
            'status' => 'active',
        ]);

        // Soft delete the employee
        $emp1->delete();
        $this->assertSoftDeleted('employees', ['id' => $emp1->id]);

        // Next code generated must be GLF-EMP-2026-0002 to avoid collision
        $nextCode = $employeeService->generateEmployeeCode($this->companyA->id, $year);
        $this->assertEquals("GLF-EMP-{$year}-0002", $nextCode);
    }

    public function test_employee_code_skips_over_manual_or_gap_codes(): void
    {
        $employeeService = app(EmployeeService::class);
        $year = 2026;

        // Manually created employee with higher code GLF-EMP-2026-0010
        Employee::create([
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA->id,
            'department_id' => $this->deptA->id,
            'designation_id' => $this->desigA->id,
            'employee_code' => "GLF-EMP-{$year}-0010",
            'first_name' => 'Manual',
            'last_name' => 'Gap',
            'gender' => 'male',
            'email' => 'manualgap@test.com',
            'phone' => '9876543003',
            'joining_date' => '2026-01-01',
            'employment_type' => 'full_time',
            'status' => 'active',
        ]);

        $nextCode = $employeeService->generateEmployeeCode($this->companyA->id, $year);
        $this->assertEquals("GLF-EMP-{$year}-0011", $nextCode);
    }

    /**
     * Phase C Tests: Login Identifier Fix
     */
    public function test_login_successful_with_email_and_password(): void
    {
        $response = $this->post('/admin/login', [
            'email' => 'admin@alpha.com',
            'password' => 'Password@123',
        ]);

        $response->assertStatus(302);
        $response->assertRedirect('/admin');
        $this->assertAuthenticatedAs($this->companyAdminA);
    }

    public function test_login_successful_with_registered_mobile_number(): void
    {
        $response = $this->post('/admin/login', [
            'email' => '9876500001',
            'password' => 'Password@123',
        ]);

        $response->assertStatus(302);
        $response->assertRedirect('/admin');
        $this->assertAuthenticatedAs($this->companyAdminA);
    }

    public function test_login_rejects_employee_code_as_identifier(): void
    {
        // Create an employee with a known code
        $emp = Employee::create([
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA->id,
            'department_id' => $this->deptA->id,
            'designation_id' => $this->desigA->id,
            'employee_code' => 'GLF-EMP-2026-0001',
            'first_name' => 'Login',
            'last_name' => 'Test',
            'gender' => 'male',
            'email' => 'empcode@test.com',
            'phone' => '9876543004',
            'joining_date' => '2026-01-01',
            'employment_type' => 'full_time',
            'status' => 'active',
            'user_id' => $this->companyAdminA->id,
        ]);

        $response = $this->from('/admin/login')->post('/admin/login', [
            'email' => 'GLF-EMP-2026-0001',
            'password' => 'Password@123',
        ]);

        $response->assertStatus(302);
        $response->assertRedirect('/admin/login');
        $response->assertSessionHasErrors(['email']);
        $this->assertGuest();
    }

    public function test_login_rejects_ambiguous_duplicate_mobile_numbers(): void
    {
        // Create another user with the exact same mobile number
        User::create([
            'name' => 'Duplicate Mobile User',
            'email' => 'duplicate@alpha.com',
            'mobile_number' => '9876500001', // duplicate
            'password' => Hash::make('Password@123'),
            'status' => 'active',
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA->id,
        ]);

        $response = $this->from('/admin/login')->post('/admin/login', [
            'email' => '9876500001',
            'password' => 'Password@123',
        ]);

        $response->assertStatus(302);
        $response->assertRedirect('/admin/login');
        $response->assertSessionHasErrors(['email']);
        $this->assertGuest();
    }

    public function test_login_rejects_inactive_user(): void
    {
        $inactiveUser = User::create([
            'name' => 'Inactive User',
            'email' => 'inactive@alpha.com',
            'mobile_number' => '9876500002',
            'password' => Hash::make('Password@123'),
            'status' => 'inactive',
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA->id,
        ]);

        $response = $this->from('/admin/login')->post('/admin/login', [
            'email' => 'inactive@alpha.com',
            'password' => 'Password@123',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['email']);
        $this->assertGuest();
    }

    /**
     * Phase D Tests: Unified Employee Form & User Account
     */
    public function test_employee_create_page_renders_successfully(): void
    {
        $response = $this->actingAs($this->companyAdminA)->get(route('admin.employee.create'));

        $response->assertStatus(200);
        $response->assertSee('Add Enterprise Employee');
        $response->assertSee('Employee Code (Auto-generated)');
        $response->assertSee('employee_code_preview');
        $response->assertSee('Automatically generated by the system. No manual entry required.');
        $response->assertSee('GLF-EMP-2026-0001');
        $response->assertSee('Residential Address');
        $response->assertSee('System Login');
        $response->assertSee('New User Account');
        $response->assertSee('Link Existing User');
    }

    public function test_employee_creation_with_new_user_account_and_role(): void
    {
        $postData = [
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA->id,
            'department_id' => $this->deptA->id,
            'designation_id' => $this->desigA->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'gender' => 'male',
            'email' => 'johndoe@test.com',
            'phone' => '9876543010',
            'joining_date' => '2026-02-01',
            'employment_type' => 'full_time',
            'basic_salary' => 25000,
            'status' => 'active',
            'address' => '42 Park Street',
            'city' => 'Kolkata',
            'state' => 'West Bengal',
            'pincode' => '700016',
            'login_enabled' => 1,
            'user_mode' => 'create',
            'user_email' => 'johndoe@test.com',
            'user_password' => 'SecurePass@123',
            'user_password_confirmation' => 'SecurePass@123',
            'role' => 'Loan Officer',
        ];

        $response = $this->actingAs($this->companyAdminA)->post(route('admin.employee.store'), $postData);

        $response->assertStatus(302);
        $response->assertSessionHas('success');

        // Verify Employee created
        $employee = Employee::where('email', 'johndoe@test.com')->first();
        $this->assertNotNull($employee);
        $this->assertStringStartsWith('GLF-EMP-', $employee->employee_code);
        $this->assertEquals('42 Park Street', $employee->address);
        $this->assertEquals('Kolkata', $employee->city);
        $this->assertEquals('West Bengal', $employee->state);
        $this->assertEquals('700016', $employee->pincode);
        $this->assertTrue((bool)$employee->login_enabled);

        // Verify User created and linked
        $user = User::where('email', 'johndoe@test.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals($this->companyA->id, $user->company_id);
        $this->assertEquals($this->branchA->id, $user->branch_id);
        $this->assertEquals($employee->employee_code, $user->employee_id);
        $this->assertEquals($user->id, $employee->user_id);
        $this->assertTrue($user->hasRole('Loan Officer'));
        $this->assertTrue(Hash::check('SecurePass@123', $user->password));
    }

    public function test_employee_creation_with_login_disabled(): void
    {
        $postData = [
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA->id,
            'department_id' => $this->deptA->id,
            'designation_id' => $this->desigA->id,
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'gender' => 'female',
            'email' => 'janesmith@test.com',
            'phone' => '9876543011',
            'joining_date' => '2026-02-01',
            'employment_type' => 'full_time',
            'basic_salary' => 20000,
            'status' => 'active',
            'login_enabled' => 0,
            'user_mode' => 'none',
        ];

        $response = $this->actingAs($this->companyAdminA)->post(route('admin.employee.store'), $postData);

        $response->assertStatus(302);
        $response->assertSessionHas('success');

        $employee = Employee::where('email', 'janesmith@test.com')->first();
        $this->assertNotNull($employee);
        $this->assertFalse((bool)$employee->login_enabled);
        $this->assertNull($employee->user_id);

        // No User should be created
        $user = User::where('email', 'janesmith@test.com')->first();
        $this->assertNull($user);
    }

    public function test_employee_creation_linking_eligible_existing_user(): void
    {
        // Existing user in same company without employee link
        $existingUser = User::create([
            'name' => 'Existing Unlinked User',
            'email' => 'unlinked@alpha.com',
            'mobile_number' => '9876543012',
            'password' => Hash::make('ExistingSecret@123'),
            'status' => 'active',
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA->id,
        ]);

        $postData = [
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA->id,
            'department_id' => $this->deptA->id,
            'designation_id' => $this->desigA->id,
            'first_name' => 'Linked',
            'last_name' => 'Staff',
            'gender' => 'male',
            'email' => 'linkedstaff@test.com',
            'phone' => '9876543012',
            'joining_date' => '2026-02-01',
            'employment_type' => 'full_time',
            'basic_salary' => 22000,
            'status' => 'active',
            'login_enabled' => 1,
            'user_mode' => 'link',
            'user_id' => $existingUser->id,
            'role' => 'Loan Officer',
        ];

        $response = $this->actingAs($this->companyAdminA)->post(route('admin.employee.store'), $postData);

        $response->assertStatus(302);
        $response->assertSessionHas('success');

        $employee = Employee::where('email', 'linkedstaff@test.com')->first();
        $this->assertNotNull($employee);
        $this->assertEquals($existingUser->id, $employee->user_id);

        $existingUser->refresh();
        $this->assertEquals($employee->employee_code, $existingUser->employee_id);
        $this->assertTrue($existingUser->hasRole('Loan Officer'));
        // Existing password must NOT be modified
        $this->assertTrue(Hash::check('ExistingSecret@123', $existingUser->password));
    }

    public function test_rejects_linking_cross_company_user(): void
    {
        // User in Company B
        $userCompanyB = User::create([
            'name' => 'User in Beta',
            'email' => 'user@beta.com',
            'mobile_number' => '9876543013',
            'password' => Hash::make('Password@123'),
            'status' => 'active',
            'company_id' => $this->companyB->id,
            'branch_id' => $this->branchB->id,
        ]);

        $postData = [
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA->id,
            'department_id' => $this->deptA->id,
            'designation_id' => $this->desigA->id,
            'first_name' => 'Cross',
            'last_name' => 'Company',
            'gender' => 'male',
            'email' => 'cross@test.com',
            'phone' => '9876543014',
            'joining_date' => '2026-02-01',
            'employment_type' => 'full_time',
            'basic_salary' => 22000,
            'status' => 'active',
            'login_enabled' => 1,
            'user_mode' => 'link',
            'user_id' => $userCompanyB->id,
        ];

        $response = $this->actingAs($this->companyAdminA)->post(route('admin.employee.store'), $postData);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['user_id']);
        $this->assertDatabaseMissing('employees', ['email' => 'cross@test.com']);
    }

    public function test_rejects_linking_already_linked_user(): void
    {
        // An existing user already linked to an employee
        $alreadyLinkedEmp = Employee::create([
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA->id,
            'department_id' => $this->deptA->id,
            'designation_id' => $this->desigA->id,
            'employee_code' => 'EMP-2026-9999',
            'first_name' => 'Existing',
            'last_name' => 'Emp',
            'gender' => 'male',
            'email' => 'empalready@alpha.com',
            'phone' => '9876543015',
            'joining_date' => '2026-01-01',
            'employment_type' => 'full_time',
            'status' => 'active',
        ]);

        $alreadyLinkedUser = User::create([
            'name' => 'Already Linked',
            'email' => 'alreadylinked@alpha.com',
            'password' => Hash::make('Password@123'),
            'status' => 'active',
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA->id,
            'employee_id' => $alreadyLinkedEmp->id,
        ]);
        $alreadyLinkedEmp->update(['user_id' => $alreadyLinkedUser->id]);

        $postData = [
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA->id,
            'department_id' => $this->deptA->id,
            'designation_id' => $this->desigA->id,
            'first_name' => 'Second',
            'last_name' => 'Emp',
            'gender' => 'male',
            'email' => 'secondemp@test.com',
            'phone' => '9876543016',
            'joining_date' => '2026-02-01',
            'employment_type' => 'full_time',
            'basic_salary' => 22000,
            'status' => 'active',
            'login_enabled' => 1,
            'user_mode' => 'link',
            'user_id' => $alreadyLinkedUser->id,
        ];

        $response = $this->actingAs($this->companyAdminA)->post(route('admin.employee.store'), $postData);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['user_id']);
        $this->assertDatabaseMissing('employees', ['email' => 'secondemp@test.com']);
    }

    /**
     * Phase F Tests: RBAC and Tenant Isolation
     */
    public function test_non_superadmin_cannot_assign_super_admin_role(): void
    {
        $postData = [
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA->id,
            'department_id' => $this->deptA->id,
            'designation_id' => $this->desigA->id,
            'first_name' => 'Privilege',
            'last_name' => 'Escalation',
            'gender' => 'male',
            'email' => 'escalate@test.com',
            'phone' => '9876543017',
            'joining_date' => '2026-02-01',
            'employment_type' => 'full_time',
            'basic_salary' => 30000,
            'status' => 'active',
            'login_enabled' => 1,
            'user_mode' => 'create',
            'user_email' => 'escalate@test.com',
            'user_password' => 'SecurePass@123',
            'user_password_confirmation' => 'SecurePass@123',
            'role' => 'Super Admin', // Forbidden for Company Admin
        ];

        $response = $this->actingAs($this->companyAdminA)->post(route('admin.employee.store'), $postData);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['role']);
        $this->assertDatabaseMissing('employees', ['email' => 'escalate@test.com']);
        $this->assertDatabaseMissing('users', ['email' => 'escalate@test.com']);
    }

    public function test_rejects_cross_tenant_branch_or_department(): void
    {
        $postData = [
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchB->id, // Belongs to Company B!
            'department_id' => $this->deptA->id,
            'designation_id' => $this->desigA->id,
            'first_name' => 'Cross',
            'last_name' => 'Branch',
            'gender' => 'male',
            'email' => 'crossbranch@test.com',
            'phone' => '9876543018',
            'joining_date' => '2026-02-01',
            'employment_type' => 'full_time',
            'basic_salary' => 20000,
            'status' => 'active',
            'login_enabled' => 0,
        ];

        $response = $this->actingAs($this->companyAdminA)->post(route('admin.employee.store'), $postData);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['branch_id']);
        $this->assertDatabaseMissing('employees', ['email' => 'crossbranch@test.com']);
    }

    /**
     * Phase E Tests: Transaction Safety & File Rollback
     */
    public function test_transaction_rollback_and_file_cleanup_on_failure(): void
    {
        Storage::fake('public');

        $photo = UploadedFile::fake()->image('profile.jpg');

        $employeeService = app(EmployeeService::class);

        // Attempt creation with invalid role that throws exception
        try {
            $employeeService->createEmployee([
                'company_id' => $this->companyA->id,
                'branch_id' => $this->branchA->id,
                'department_id' => $this->deptA->id,
                'designation_id' => $this->desigA->id,
                'first_name' => 'Rollback',
                'last_name' => 'Test',
                'gender' => 'male',
                'email' => 'rollback@test.com',
                'phone' => '9876543019',
                'joining_date' => '2026-02-01',
                'employment_type' => 'full_time',
                'status' => 'active',
                'login_enabled' => true,
                'user_mode' => 'create',
                'user_email' => 'rollback@test.com',
                'user_password' => 'Pass@12345',
                'role' => 'NonExistentRole12345', // will trigger exception in assignRole
            ], $photo);
            $this->fail('Expected exception was not thrown');
        } catch (\Throwable $e) {
            // Expected exception
        }

        // Verify DB rollback
        $this->assertDatabaseMissing('employees', ['email' => 'rollback@test.com']);
        $this->assertDatabaseMissing('users', ['email' => 'rollback@test.com']);

        // Verify file cleanup
        $files = Storage::disk('public')->allFiles('employees/photos');
        $this->assertEmpty($files, 'New file should have been cleaned up on transaction rollback');
    }

    /**
     * Phase G Tests: Employee Edit Bug Resolution
     */
    public function test_employee_edit_renders_successfully_with_managers(): void
    {
        $employee = Employee::create([
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA->id,
            'department_id' => $this->deptA->id,
            'designation_id' => $this->desigA->id,
            'employee_code' => 'EMP-2026-0099',
            'first_name' => 'Edit',
            'last_name' => 'Me',
            'gender' => 'female',
            'email' => 'editme@test.com',
            'phone' => '9876543020',
            'joining_date' => '2026-01-01',
            'employment_type' => 'full_time',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->companyAdminA)->get(route('admin.employee.edit', $employee));

        $response->assertStatus(200);
        $response->assertSee('Edit Enterprise Employee');
        $response->assertSee('Residential Address');
        $response->assertSee('User Account');
        $response->assertSee('Employee Code');
        $response->assertSee('EMP-2026-0099');
        $response->assertSee('LOCKED');
        $response->assertSee('Permanent unique system identifier. Cannot be modified.');
    }

    public function test_browser_submitted_custom_employee_code_is_ignored_and_overridden(): void
    {
        $postData = [
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA->id,
            'department_id' => $this->deptA->id,
            'designation_id' => $this->desigA->id,
            'employee_code' => 'CUSTOM-HACK-9999', // Browser attempts to submit custom code
            'first_name' => 'Secure',
            'last_name' => 'Code',
            'gender' => 'male',
            'email' => 'securecode@test.com',
            'phone' => '9876543099',
            'joining_date' => '2026-02-01',
            'employment_type' => 'full_time',
            'basic_salary' => 25000,
            'status' => 'active',
            'login_enabled' => 0,
            'user_mode' => 'none',
        ];

        $response = $this->actingAs($this->companyAdminA)->post(route('admin.employee.store'), $postData);

        $response->assertStatus(302);
        $response->assertSessionHas('success');

        $employee = Employee::where('email', 'securecode@test.com')->first();
        $this->assertNotNull($employee);
        // The submitted code CUSTOM-HACK-9999 must have been ignored
        $this->assertNotEquals('CUSTOM-HACK-9999', $employee->employee_code);
        // System must have assigned the real sequence GLF-EMP-2026-0001
        $this->assertEquals('GLF-EMP-2026-0001', $employee->employee_code);
    }

    public function test_actual_generated_code_appears_in_success_message_and_index_page(): void
    {
        $postData = [
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA->id,
            'department_id' => $this->deptA->id,
            'designation_id' => $this->desigA->id,
            'first_name' => 'Flash',
            'last_name' => 'Notice',
            'gender' => 'female',
            'email' => 'flashnotice@test.com',
            'phone' => '9876543098',
            'joining_date' => '2026-02-01',
            'employment_type' => 'full_time',
            'basic_salary' => 25000,
            'status' => 'active',
            'login_enabled' => 0,
            'user_mode' => 'none',
        ];

        $response = $this->actingAs($this->companyAdminA)->post(route('admin.employee.store'), $postData);

        $response->assertStatus(302);
        $response->assertRedirect(route('admin.employee.index'));
        $response->assertSessionHas('assigned_employee_code', 'GLF-EMP-2026-0001');

        $indexResponse = $this->actingAs($this->companyAdminA)->get(route('admin.employee.index'));
        $indexResponse->assertStatus(200);
        $indexResponse->assertSee('Assigned Employee Code:');
        $indexResponse->assertSee('GLF-EMP-2026-0001');
        $indexResponse->assertSee('Copy Code');
    }

    public function test_editing_employee_never_changes_existing_code(): void
    {
        $employee = Employee::create([
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA->id,
            'department_id' => $this->deptA->id,
            'designation_id' => $this->desigA->id,
            'employee_code' => 'EMP-2026-0055',
            'first_name' => 'Original',
            'last_name' => 'Staff',
            'gender' => 'male',
            'email' => 'originalstaff@test.com',
            'phone' => '9876543097',
            'joining_date' => '2026-01-01',
            'employment_type' => 'full_time',
            'basic_salary' => 28000,
            'status' => 'active',
        ]);

        $updateData = [
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA->id,
            'department_id' => $this->deptA->id,
            'designation_id' => $this->desigA->id,
            'first_name' => 'Renamed',
            'last_name' => 'Staff',
            'gender' => 'male',
            'email' => 'originalstaff@test.com',
            'phone' => '9876543097',
            'joining_date' => '2026-01-01',
            'employment_type' => 'full_time',
            'basic_salary' => 30000,
            'status' => 'active',
            'employee_code' => 'GLF-EMP-2026-9999', // Attempt to tamper with employee code
        ];

        $response = $this->actingAs($this->companyAdminA)->put(route('admin.employee.update', $employee), $updateData);

        $response->assertStatus(302);
        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success');
        $employee->refresh();

        $this->assertEquals('Renamed', $employee->first_name);
        // Code must remain strictly unchanged
        $this->assertEquals('EMP-2026-0055', $employee->employee_code);
    }

    public function test_employee_code_preview_endpoint_returns_json_code(): void
    {
        $response = $this->actingAs($this->companyAdminA)->get(route('admin.employee.preview-code', ['company_id' => $this->companyA->id]));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'code' => 'GLF-EMP-2026-0001',
        ]);
    }

    public function test_legacy_employee_code_continuity_and_example_sequence(): void
    {
        $employeeService = app(EmployeeService::class);
        $year = 2026;

        // Verify fresh company sequence starts at GLF-EMP-YYYY-0001
        $code1 = $employeeService->generateEmployeeCode($this->companyA->id, $year);
        $this->assertEquals("GLF-EMP-{$year}-0001", $code1);

        // When a company has a legacy employee code like EMP-2026-0001
        $legacyEmp = Employee::create([
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA->id,
            'department_id' => $this->deptA->id,
            'designation_id' => $this->desigA->id,
            'employee_code' => "EMP-{$year}-0001",
            'first_name' => 'Legacy',
            'last_name' => 'Employee',
            'gender' => 'male',
            'email' => 'legacy@test.com',
            'phone' => '9876543088',
            'joining_date' => '2026-01-01',
            'employment_type' => 'full_time',
            'status' => 'active',
        ]);

        // Next code generated must transition seamlessly to GLF-EMP-2026-0002
        $code2 = $employeeService->generateEmployeeCode($this->companyA->id, $year);
        $this->assertEquals("GLF-EMP-{$year}-0002", $code2);

        // Save employee 2
        Employee::create([
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA->id,
            'department_id' => $this->deptA->id,
            'designation_id' => $this->desigA->id,
            'employee_code' => $code2,
            'first_name' => 'Second',
            'last_name' => 'Employee',
            'gender' => 'female',
            'email' => 'second@test.com',
            'phone' => '9876543087',
            'joining_date' => '2026-01-01',
            'employment_type' => 'full_time',
            'status' => 'active',
        ]);

        // Next must be GLF-EMP-2026-0003
        $code3 = $employeeService->generateEmployeeCode($this->companyA->id, $year);
        $this->assertEquals("GLF-EMP-{$year}-0003", $code3);

        // Save employee 3
        Employee::create([
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA->id,
            'department_id' => $this->deptA->id,
            'designation_id' => $this->desigA->id,
            'employee_code' => $code3,
            'first_name' => 'Third',
            'last_name' => 'Employee',
            'gender' => 'female',
            'email' => 'third@test.com',
            'phone' => '9876543086',
            'joining_date' => '2026-01-01',
            'employment_type' => 'full_time',
            'status' => 'active',
        ]);

        // Next must be GLF-EMP-2026-0004
        $code4 = $employeeService->generateEmployeeCode($this->companyA->id, $year);
        $this->assertEquals("GLF-EMP-{$year}-0004", $code4);
    }
}
