<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\LeaveType;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HrmAttendanceAndLeaveNullEmployeeTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Branch $branch;
    protected User $admin;
    protected Department $department;
    protected Designation $designation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RbacSeeder::class,
            AdminUserSeeder::class,
        ]);

        $this->company = Company::create([
            'name' => 'Grihalaxmi Test Co',
            'code' => 'COMP-001',
            'email' => 'test@grihalaxmitest.com',
            'phone' => '9876543210',
            'address' => 'Test Address',
            'is_active' => true,
        ]);
        $this->branch = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Main Branch',
            'code' => 'BR-001',
            'email' => 'branch@grihalaxmitest.com',
            'phone' => '9876543211',
            'address' => 'Main Branch Address',
            'city' => 'Kolkata',
            'state' => 'West Bengal',
            'pincode' => '700001',
            'is_active' => true,
        ]);

        $this->department = Department::create([
            'company_id' => $this->company->id,
            'name' => 'HR',
            'code' => 'HR-DEP',
            'is_active' => true,
        ]);

        $this->designation = Designation::create([
            'company_id' => $this->company->id,
            'department_id' => $this->department->id,
            'title' => 'Officer',
            'code' => 'OFF-01',
            'is_active' => true,
        ]);

        $this->admin = User::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
        ]);
        $this->admin->assignRole('Super Admin');
    }

    public function test_attendance_index_handles_soft_deleted_and_missing_employee_without_500_error(): void
    {
        // Create an employee, attendance log, and then soft-delete the employee
        $employeeSoftDeleted = Employee::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'department_id' => $this->department->id,
            'designation_id' => $this->designation->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe.deleted@example.com',
            'phone' => '9876543291',
            'joining_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        Attendance::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'employee_id' => $employeeSoftDeleted->id,
            'attendance_date' => now()->toDateString(),
            'status' => 'present',
        ]);

        $employeeSoftDeleted->delete();

        // Create an attendance record referencing an orphaned / missing employee ID
        \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Attendance::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'employee_id' => 999999,
            'attendance_date' => now()->subDay()->toDateString(),
            'status' => 'absent',
        ]);
        \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $response = $this->actingAs($this->admin)->get('/admin/hrm/attendance?company_id=' . $this->company->id);

        $response->assertStatus(200);
        $response->assertSee($employeeSoftDeleted->full_name);
        $response->assertSee('Unknown / Former Employee');
    }

    public function test_leave_index_handles_soft_deleted_and_missing_employee_without_500_error(): void
    {
        $leaveType = LeaveType::create([
            'company_id' => $this->company->id,
            'name' => 'Sick Leave',
            'code' => 'SL',
            'days_allowed' => 10,
            'is_active' => true,
        ]);

        // Create an employee, leave request, and then soft-delete the employee
        $employeeSoftDeleted = Employee::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'department_id' => $this->department->id,
            'designation_id' => $this->designation->id,
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'jane.smith.deleted@example.com',
            'phone' => '9876543292',
            'joining_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        Leave::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'employee_id' => $employeeSoftDeleted->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(2)->toDateString(),
            'total_days' => 3,
            'reason' => 'Medical leave',
            'status' => 'pending',
        ]);

        $employeeSoftDeleted->delete();

        // Create a leave record referencing an orphaned / missing employee ID
        \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Leave::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'employee_id' => 888888,
            'leave_type_id' => $leaveType->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(1)->toDateString(),
            'total_days' => 2,
            'reason' => 'Urgent personal work',
            'status' => 'approved',
        ]);
        \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $response = $this->actingAs($this->admin)->get('/admin/hrm/leave?company_id=' . $this->company->id);

        $response->assertStatus(200);
        $response->assertSee($employeeSoftDeleted->full_name);
        $response->assertSee('Unknown / Former Employee');
    }
}
