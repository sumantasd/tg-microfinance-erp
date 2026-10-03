<?php

namespace Tests\Feature\Api;

use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BranchManagerAttendanceApiTest extends TestCase
{
    use DatabaseTransactions;

    protected Company $company;
    protected Branch $branch;
    protected Department $department;
    protected Designation $designation;
    protected Role $bmRole;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bmRole = Role::firstOrCreate(['name' => 'Branch Manager', 'guard_name' => 'web']);

        $this->company = Company::create([
            'name' => 'Grihalaxmi Finance Pvt Ltd',
            'code' => 'GFPL',
            'email' => 'info@grihalaxmi.com',
            'phone' => '9830098300',
            'address' => 'Patna Main Road',
        ]);

        $this->branch = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Patna Main Branch',
            'code' => 'PAT001',
            'phone' => '0612250001',
            'address' => 'Patna Main Road',
            'city' => 'Patna',
            'state' => 'Bihar',
            'pincode' => '800001',
        ]);

        $this->department = Department::create([
            'company_id' => $this->company->id,
            'name' => 'Branch Management',
            'code' => 'BM-DEPT',
        ]);

        $this->designation = Designation::create([
            'company_id' => $this->company->id,
            'department_id' => $this->department->id,
            'title' => 'Branch Manager',
            'code' => 'BM-DESIG',
        ]);
    }

    /**
     * Test Branch Manager check-in and check-out when linked via user_id on employees table.
     */
    public function test_branch_manager_checkin_and_checkout_linked_via_user_id(): void
    {
        $user = User::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'Ramesh Branch Manager',
            'email' => 'ramesh.bm@grihalaxmi.com',
            'password' => Hash::make('Password123'),
            'status' => 'active',
        ]);
        $user->assignRole($this->bmRole);

        $employee = Employee::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'user_id' => $user->id,
            'department_id' => $this->department->id,
            'designation_id' => $this->designation->id,
            'employee_code' => 'EMP-BM-101',
            'first_name' => 'Ramesh',
            'last_name' => 'Manager',
            'email' => 'ramesh.bm@grihalaxmi.com',
            'joining_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $token = $user->createToken('MobileDevice')->plainTextToken;

        // 1. Check-In
        $checkInResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/attendance/check-in', [
                'latitude' => 25.5941,
                'longitude' => 85.1376,
                'accuracy' => 4.2,
                'remarks' => 'Morning Branch Manager Check-in',
            ]);

        $checkInResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.employee_id', $employee->id)
            ->assertJsonPath('data.employee_code', 'EMP-BM-101')
            ->assertJsonPath('data.branch_id', $this->branch->id)
            ->assertJsonPath('data.status', 'present');

        $this->assertDatabaseHas('attendances', [
            'employee_id' => $employee->id,
            'branch_id' => $this->branch->id,
            'company_id' => $this->company->id,
            'attendance_date' => now()->toDateString(),
            'status' => 'present',
        ]);

        // 2. Check-Out
        $checkOutResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/attendance/check-out', [
                'latitude' => 25.5941,
                'longitude' => 85.1376,
                'accuracy' => 5.0,
                'remarks' => 'Evening Branch Manager Check-out',
            ]);

        $checkOutResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.employee_id', $employee->id)
            ->assertJsonPath('data.status', 'present');

        $attendanceRecord = Attendance::where('employee_id', $employee->id)
            ->where('attendance_date', now()->toDateString())
            ->first();

        $this->assertNotNull($attendanceRecord);
        $this->assertNotNull($attendanceRecord->clock_in);
        $this->assertNotNull($attendanceRecord->clock_out);
    }

    /**
     * Test Branch Manager check-in when linked via employee_id on users table.
     */
    public function test_branch_manager_checkin_linked_via_employee_id_on_users_table(): void
    {
        $employee = Employee::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'department_id' => $this->department->id,
            'designation_id' => $this->designation->id,
            'employee_code' => 'EMP-BM-102',
            'first_name' => 'Suresh',
            'last_name' => 'Manager',
            'email' => 'suresh.bm@grihalaxmi.com',
            'joining_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $user = User::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'employee_id' => 'EMP-BM-102',
            'name' => 'Suresh Branch Manager',
            'email' => 'suresh.bm@grihalaxmi.com',
            'password' => Hash::make('Password123'),
            'status' => 'active',
        ]);
        $user->assignRole($this->bmRole);

        $token = $user->createToken('MobileDevice')->plainTextToken;

        $checkInResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/attendance/check-in', [
                'latitude' => 25.5941,
                'longitude' => 85.1376,
                'accuracy' => 3.5,
                'remarks' => 'BM Checkin via employee_id link',
            ]);

        $checkInResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.employee_id', $employee->id)
            ->assertJsonPath('data.branch_id', $this->branch->id);

        $this->assertDatabaseHas('attendances', [
            'employee_id' => $employee->id,
            'branch_id' => $this->branch->id,
            'attendance_date' => now()->toDateString(),
        ]);
    }

    /**
     * Test user without employee profile receives HTTP 422 without bypassing validation.
     */
    public function test_unlinked_user_receives_422_error(): void
    {
        $unlinkedUser = User::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'Unlinked User',
            'email' => 'unlinked.user@grihalaxmi.com',
            'password' => Hash::make('Password123'),
            'status' => 'active',
        ]);

        $token = $unlinkedUser->createToken('MobileDevice')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/attendance/check-in', [
                'latitude' => 25.5941,
                'longitude' => 85.1376,
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Authenticated user is not linked to an employee profile');
    }
}
