<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\FieldLocationLog;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BranchManagerEmployeeMappingTest extends TestCase
{
    use DatabaseTransactions;

    protected Company $company;
    protected Branch $branchA;
    protected Branch $branchB;
    protected Department $department;
    protected Designation $designation;
    protected Role $adminRole;
    protected Role $bmRole;
    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminRole = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $this->bmRole = Role::firstOrCreate(['name' => 'Branch Manager', 'guard_name' => 'web']);

        // Required permissions
        $permissions = [
            'users.view', 'users.create', 'users.edit', 'users.delete',
            'employee.view', 'employee.create', 'employee.edit',
            'attendance.view', 'attendance.create',
            'field_tracking.view',
        ];
        foreach ($permissions as $permName) {
            $perm = Permission::firstOrCreate(['name' => $permName, 'guard_name' => 'web']);
            $this->adminRole->givePermissionTo($perm);
            if (in_array($permName, ['attendance.view', 'attendance.create', 'field_tracking.view'])) {
                $this->bmRole->givePermissionTo($perm);
            }
        }

        $this->company = Company::firstOrCreate(
            ['id' => 1],
            [
                'name' => 'Grihalaxmi Finance Private Limited',
                'code' => 'GLF-TEST',
                'email' => 'info@grihalaxmitest.com',
                'phone' => '9876543210',
                'address' => 'Patna Main Road',
                'is_active' => true,
            ]
        );

        $this->branchA = Branch::firstOrCreate(
            ['id' => 101],
            [
                'company_id' => $this->company->id,
                'name' => 'Patna Central Branch',
                'code' => 'BR-PAT-01',
                'phone' => '0612200001',
                'address' => 'Patna Central',
                'city' => 'Patna',
                'state' => 'Bihar',
                'pincode' => '800001',
                'is_active' => true,
            ]
        );

        $this->branchB = Branch::firstOrCreate(
            ['id' => 102],
            [
                'company_id' => $this->company->id,
                'name' => 'Gaya Regional Branch',
                'code' => 'BR-GAY-02',
                'phone' => '0631200002',
                'address' => 'Gaya Road',
                'city' => 'Gaya',
                'state' => 'Bihar',
                'pincode' => '823001',
                'is_active' => true,
            ]
        );

        $this->department = Department::firstOrCreate(
            ['company_id' => $this->company->id, 'code' => 'OPS'],
            [
                'name' => 'Branch Operations',
                'is_active' => true,
            ]
        );

        $this->designation = Designation::firstOrCreate(
            ['company_id' => $this->company->id, 'code' => 'BM'],
            [
                'department_id' => $this->department->id,
                'title' => 'Branch Manager',
                'is_active' => true,
            ]
        );

        $this->adminUser = User::create([
            'name' => 'System Administrator',
            'email' => 'admin_test_' . uniqid() . '@grihalaxmi.com',
            'password' => Hash::make('AdminSecret123!'),
            'company_id' => $this->company->id,
            'status' => 'active',
        ]);
        $this->adminUser->assignRole($this->adminRole);
    }

    /**
     * Requirement 8.1 & Requirement 4:
     * User without an Employee profile displays "No Employee Profile" / unlinked,
     * and duty check-in is rejected with validation error.
     */
    public function test_user_without_an_employee_profile_displays_unlinked_status_and_cannot_check_in(): void
    {
        $bmUser = User::create([
            'name' => 'Ramesh Kumar (Unlinked BM)',
            'email' => 'ramesh_unlinked_' . uniqid() . '@grihalaxmi.com',
            'password' => Hash::make('Pass1234!@#'),
            'company_id' => $this->company->id,
            'branch_id' => $this->branchA->id,
            'employee_id' => null,
            'status' => 'active',
        ]);
        $bmUser->assignRole($this->bmRole);

        // 1. Duty status API endpoint
        $statusResponse = $this->actingAs($bmUser)->getJson('/admin/duty/status');
        $statusResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'has_profile' => false,
                    'duty_status' => 'unlinked',
                    'status_label' => 'No Employee Profile',
                    'employee' => null,
                ],
            ]);

        // 2. Dashboard renders unlinked notice and disables check-in
        $dashboardResponse = $this->actingAs($bmUser)->get('/admin/dashboard');
        $dashboardResponse->assertStatus(200);
        $dashboardResponse->assertSee('No Employee Profile');

        // 3. Attempt check-in must fail with 422
        $checkInResponse = $this->actingAs($bmUser)->postJson('/admin/duty/check-in', [
            'latitude' => 25.5941,
            'longitude' => 85.1376,
            'accuracy' => 15.0,
        ]);
        $checkInResponse->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    }

    /**
     * Requirement 8.2 & Requirement 2 & 4:
     * Support linking an existing Branch Manager User account to an existing unlinked Employee record.
     * After linking, the dashboard displays the correct employee profile.
     */
    public function test_admin_can_link_existing_unlinked_employee_to_branch_manager_user(): void
    {
        $employee = Employee::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branchA->id,
            'user_id' => null,
            'department_id' => $this->department->id,
            'designation_id' => $this->designation->id,
            'employee_code' => 'EMP-TEST-' . rand(1000, 9999),
            'first_name' => 'Suresh',
            'last_name' => 'Patel',
            'email' => 'suresh_emp_' . uniqid() . '@grihalaxmi.com',
            'gender' => 'male',
            'joining_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $bmUser = User::create([
            'name' => 'Suresh Patel',
            'email' => 'suresh_user_' . uniqid() . '@grihalaxmi.com',
            'password' => Hash::make('Pass1234!@#'),
            'company_id' => $this->company->id,
            'branch_id' => $this->branchA->id,
            'employee_id' => null,
            'status' => 'active',
        ]);
        $bmUser->assignRole($this->bmRole);

        // Link via Admin update
        $response = $this->actingAs($this->adminUser)->put("/admin/system/users/{$bmUser->id}", [
            'name' => $bmUser->name,
            'email' => $bmUser->email,
            'role' => 'Branch Manager',
            'branch_id' => $this->branchA->id,
            'status' => 'active',
            'employee_record_id' => $employee->id,
        ]);
        $response->assertRedirect(route('admin.system.users.index'));

        // Verify database linkage
        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
            'user_id' => $bmUser->id,
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $bmUser->id,
            'employee_id' => $employee->employee_code,
        ]);

        // Branch Manager duty status check
        $statusResponse = $this->actingAs($bmUser)->getJson('/admin/duty/status');
        $statusResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'has_profile' => true,
                    'duty_status' => 'not_started',
                    'status_label' => 'Off Duty',
                ],
            ]);
        $this->assertEquals($employee->id, $statusResponse->json('data.employee.id'));
        $this->assertEquals($employee->employee_code, $statusResponse->json('data.employee.employee_code'));
    }

    /**
     * Requirement 8.3 & Requirement 2:
     * Admin can auto-create and link Employee profile for a Branch Manager when none exists.
     */
    public function test_admin_can_auto_create_employee_profile_for_branch_manager_user(): void
    {
        $userEmail = 'new_bm_' . uniqid() . '@grihalaxmi.com';

        $response = $this->actingAs($this->adminUser)->post('/admin/system/users', [
            'name' => 'Amitabh Sharma',
            'email' => $userEmail,
            'password' => 'SecurePass123!@#',
            'role' => 'Branch Manager',
            'branch_id' => $this->branchA->id,
            'status' => 'active',
            'auto_create_employee' => 1,
        ]);
        $response->assertRedirect(route('admin.system.users.index'));

        $createdUser = User::where('email', $userEmail)->firstOrFail();
        $this->assertNotNull($createdUser->employee_id);

        $linkedEmployee = Employee::where('user_id', $createdUser->id)->firstOrFail();
        $this->assertEquals('Amitabh', $linkedEmployee->first_name);
        $this->assertEquals('Sharma', $linkedEmployee->last_name);
        $this->assertEquals($this->branchA->id, $linkedEmployee->branch_id);
        $this->assertEquals($this->company->id, $linkedEmployee->company_id);
        $this->assertEquals($createdUser->employee_id, $linkedEmployee->employee_code);

        // Verify duty dashboard reflects profile
        $statusResponse = $this->actingAs($createdUser)->getJson('/admin/duty/status');
        $statusResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'has_profile' => true,
                    'duty_status' => 'not_started',
                    'status_label' => 'Off Duty',
                ],
            ]);
        $this->assertEquals($linkedEmployee->id, $statusResponse->json('data.employee.id'));
    }

    /**
     * Requirement 8.3 & Requirement 3:
     * Duplicate mapping prevention: cannot assign an employee already linked to another user.
     */
    public function test_duplicate_mapping_prevention_rejects_linking_already_linked_employee(): void
    {
        $user1 = User::create([
            'name' => 'User One',
            'email' => 'user1_' . uniqid() . '@grihalaxmi.com',
            'password' => Hash::make('Pass1234!@#'),
            'company_id' => $this->company->id,
            'branch_id' => $this->branchA->id,
            'status' => 'active',
        ]);

        $employee = Employee::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branchA->id,
            'user_id' => $user1->id,
            'department_id' => $this->department->id,
            'designation_id' => $this->designation->id,
            'employee_code' => 'EMP-TEST-' . rand(1000, 9999),
            'first_name' => 'Original',
            'last_name' => 'Assignee',
            'email' => 'orig_' . uniqid() . '@grihalaxmi.com',
            'gender' => 'male',
            'joining_date' => now()->toDateString(),
            'status' => 'active',
        ]);
        $user1->update(['employee_id' => $employee->employee_code]);

        $user2 = User::create([
            'name' => 'User Two',
            'email' => 'user2_' . uniqid() . '@grihalaxmi.com',
            'password' => Hash::make('Pass1234!@#'),
            'company_id' => $this->company->id,
            'branch_id' => $this->branchA->id,
            'status' => 'active',
        ]);
        $user2->assignRole($this->bmRole);

        // Attempting to link Employee to User 2 via HTTP must fail validation
        $response = $this->actingAs($this->adminUser)->put("/admin/system/users/{$user2->id}", [
            'name' => $user2->name,
            'email' => $user2->email,
            'role' => 'Branch Manager',
            'branch_id' => $this->branchA->id,
            'status' => 'active',
            'employee_record_id' => $employee->id,
        ]);
        $response->assertSessionHasErrors('employee_record_id');

        // Service level check: direct call must throw ValidationException
        $userService = app(UserService::class);
        $this->expectException(ValidationException::class);
        $userService->linkEmployeeToUser($user2, $employee);

        // Ensure database state was untouched
        $this->assertEquals($user1->id, $employee->fresh()->user_id);
        $this->assertNull($user2->fresh()->employee_id);
    }

    /**
     * Requirement 8.4 & Requirement 5:
     * Check-in and Check-out after mapping work successfully,
     * recording the correct Employee ID and authenticated User identity.
     */
    public function test_check_in_and_check_out_after_mapping_records_correct_employee_and_user_ids(): void
    {
        $employee = Employee::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branchA->id,
            'user_id' => null,
            'department_id' => $this->department->id,
            'designation_id' => $this->designation->id,
            'employee_code' => 'EMP-DUTY-' . rand(1000, 9999),
            'first_name' => 'Vikram',
            'last_name' => 'Aditya',
            'email' => 'vikram_' . uniqid() . '@grihalaxmi.com',
            'gender' => 'male',
            'joining_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $bmUser = User::create([
            'name' => 'Vikram Aditya',
            'email' => 'vikram_user_' . uniqid() . '@grihalaxmi.com',
            'password' => Hash::make('Pass1234!@#'),
            'company_id' => $this->company->id,
            'branch_id' => $this->branchA->id,
            'status' => 'active',
        ]);
        $bmUser->assignRole($this->bmRole);

        // Link employee
        app(UserService::class)->linkEmployeeToUser($bmUser, $employee);

        // 1. Perform Check-In
        $checkInResponse = $this->actingAs($bmUser)->postJson('/admin/duty/check-in', [
            'latitude' => 25.5941,
            'longitude' => 85.1376,
            'accuracy' => 12.5,
            'remarks' => 'Starting Morning Shift',
        ]);
        $checkInResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'duty_status' => 'active',
                    'employee_id' => $employee->id,
                    'branch_id' => $this->branchA->id,
                ],
            ]);

        // Verify Attendance record in DB
        $attendance = Attendance::where('employee_id', $employee->id)
            ->where('attendance_date', now()->toDateString())
            ->firstOrFail();
        $this->assertEquals($bmUser->id, $attendance->created_by);
        $this->assertEquals($this->branchA->id, $attendance->branch_id);
        $this->assertNotNull($attendance->clock_in);
        $this->assertNull($attendance->clock_out);

        // Verify FieldLocationLog ping_in event
        $this->assertDatabaseHas('field_location_logs', [
            'event_type' => 'ping_in',
            'user_id' => $bmUser->id,
            'employee_id' => $employee->id,
            'branch_id' => $this->branchA->id,
        ]);

        // 2. Perform Check-Out
        $checkOutResponse = $this->actingAs($bmUser)->postJson('/admin/duty/check-out', [
            'latitude' => 25.5950,
            'longitude' => 85.1380,
            'accuracy' => 10.0,
            'remarks' => 'Ending Duty Shift',
        ]);
        $checkOutResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'duty_status' => 'completed',
                    'employee_id' => $employee->id,
                ],
            ]);

        $attendance->refresh();
        $this->assertNotNull($attendance->clock_out);
        $this->assertNotNull($attendance->working_duration_minutes);
        $this->assertNotNull($attendance->working_duration_hours);
        $this->assertEquals($bmUser->id, $attendance->updated_by);

        // Verify FieldLocationLog ping_out event
        $this->assertDatabaseHas('field_location_logs', [
            'event_type' => 'ping_out',
            'user_id' => $bmUser->id,
            'employee_id' => $employee->id,
            'branch_id' => $this->branchA->id,
        ]);
    }

    /**
     * Requirement 8.5 & Requirement 6:
     * Unauthorized cross-branch access:
     * A Branch Manager cannot link or access employees or attendances from another branch.
     */
    public function test_unauthorized_cross_branch_access_is_strictly_prevented(): void
    {
        // Branch Manager A in Branch A
        $bmUserA = User::create([
            'name' => 'Manager Branch A',
            'email' => 'bma_' . uniqid() . '@grihalaxmi.com',
            'password' => Hash::make('Pass1234!@#'),
            'company_id' => $this->company->id,
            'branch_id' => $this->branchA->id,
            'status' => 'active',
        ]);
        $bmUserA->assignRole($this->bmRole);

        $employeeA = Employee::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branchA->id,
            'user_id' => $bmUserA->id,
            'department_id' => $this->department->id,
            'designation_id' => $this->designation->id,
            'employee_code' => 'EMP-A-' . rand(1000, 9999),
            'first_name' => 'Manager',
            'last_name' => 'BranchA',
            'email' => $bmUserA->email,
            'gender' => 'male',
            'joining_date' => now()->toDateString(),
            'status' => 'active',
        ]);
        $bmUserA->update(['employee_id' => $employeeA->employee_code]);

        // Employee B in Branch B
        $employeeB = Employee::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branchB->id,
            'user_id' => null,
            'department_id' => $this->department->id,
            'designation_id' => $this->designation->id,
            'employee_code' => 'EMP-B-' . rand(1000, 9999),
            'first_name' => 'Staff',
            'last_name' => 'BranchB',
            'email' => 'staffb_' . uniqid() . '@grihalaxmi.com',
            'gender' => 'male',
            'joining_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        Attendance::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branchB->id,
            'employee_id' => $employeeB->id,
            'attendance_date' => now()->toDateString(),
            'clock_in' => '09:00:00',
            'clock_out' => '17:00:00',
            'status' => 'present',
        ]);

        // 1. Branch Manager A cannot access Employee Directory
        $dirResponse = $this->actingAs($bmUserA)->get('/admin/employee');
        $dirResponse->assertStatus(403);

        // 2. Branch Manager A cannot view Employee B's profile
        $empShowResponse = $this->actingAs($bmUserA)->get("/admin/employee/{$employeeB->id}");
        $empShowResponse->assertStatus(403);

        // 3. Branch Manager A cannot link an employee from Branch B to their own account
        $this->expectException(ValidationException::class);
        app(UserService::class)->linkEmployeeToUser($bmUserA, $employeeB);
    }

    /**
     * Requirement 7:
     * Admin can safely unlink an employee profile without deleting the employee profile.
     */
    public function test_admin_can_safely_unlink_employee_profile_without_deleting_employee_record(): void
    {
        $employee = Employee::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branchA->id,
            'user_id' => null,
            'department_id' => $this->department->id,
            'designation_id' => $this->designation->id,
            'employee_code' => 'EMP-UNLINK-' . rand(1000, 9999),
            'first_name' => 'Sunil',
            'last_name' => 'Gavaskar',
            'email' => 'sunil_' . uniqid() . '@grihalaxmi.com',
            'gender' => 'male',
            'joining_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $bmUser = User::create([
            'name' => 'Sunil Gavaskar',
            'email' => 'sunil_user_' . uniqid() . '@grihalaxmi.com',
            'password' => Hash::make('Pass1234!@#'),
            'company_id' => $this->company->id,
            'branch_id' => $this->branchA->id,
            'status' => 'active',
        ]);
        $bmUser->assignRole($this->bmRole);

        app(UserService::class)->linkEmployeeToUser($bmUser, $employee);
        $this->assertEquals($bmUser->id, $employee->fresh()->user_id);

        // Unlink via Admin update
        $response = $this->actingAs($this->adminUser)->put("/admin/system/users/{$bmUser->id}", [
            'name' => $bmUser->name,
            'email' => $bmUser->email,
            'role' => 'Branch Manager',
            'branch_id' => $this->branchA->id,
            'status' => 'active',
            'unlink_employee' => 1,
        ]);
        $response->assertRedirect(route('admin.system.users.index'));

        // Employee record must STILL EXIST in database (not deleted!), but user_id is null
        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
            'user_id' => null,
            'deleted_at' => null,
        ]);

        // User employee_id is cleared
        $this->assertNull($bmUser->fresh()->employee_id);

        // Duty status for user is now unlinked
        $statusResponse = $this->actingAs($bmUser->fresh())->getJson('/admin/duty/status');
        $statusResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'has_profile' => false,
                    'duty_status' => 'unlinked',
                    'status_label' => 'No Employee Profile',
                ],
            ]);
    }
}
