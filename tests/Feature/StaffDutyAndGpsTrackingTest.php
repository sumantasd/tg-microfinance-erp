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
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StaffDutyAndGpsTrackingTest extends TestCase
{
    use DatabaseTransactions;

    protected Company $company;
    protected Branch $branch1;
    protected Branch $branch2;
    protected Department $department;
    protected Designation $designation;
    protected Role $bmRole;
    protected Role $staffRole;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bmRole = Role::firstOrCreate(['name' => 'Branch Manager', 'guard_name' => 'web']);
        $this->staffRole = Role::firstOrCreate(['name' => 'Loan Officer', 'guard_name' => 'web']);

        $viewPerm = Permission::firstOrCreate(['name' => 'field_tracking.view', 'guard_name' => 'web']);
        $this->bmRole->givePermissionTo($viewPerm);

        $this->company = Company::create([
            'name' => 'Grihalaxmi Test Finance Pvt Ltd',
            'code' => 'GTFPL',
            'email' => 'info@gtfpl.test',
            'phone' => '9800098000',
            'address' => 'Kolkata HQ',
            'is_active' => true,
        ]);

        $this->branch1 = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Branch North',
            'code' => 'BR-NORTH',
            'phone' => '0331000001',
            'address' => 'North Station Road',
            'city' => 'Kolkata',
            'state' => 'West Bengal',
            'pincode' => '700001',
            'is_active' => true,
        ]);

        $this->branch2 = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Branch South',
            'code' => 'BR-SOUTH',
            'phone' => '0332000002',
            'address' => 'South Avenue',
            'city' => 'Kolkata',
            'state' => 'West Bengal',
            'pincode' => '700029',
            'is_active' => true,
        ]);

        $this->department = Department::create([
            'company_id' => $this->company->id,
            'name' => 'Operations',
            'code' => 'OPS-DEPT',
        ]);

        $this->designation = Designation::create([
            'company_id' => $this->company->id,
            'department_id' => $this->department->id,
            'title' => 'Field Executive',
            'code' => 'FIELD-EXEC',
        ]);
    }

    protected function createStaffMember(Branch $branch, string $name, string $email, string $code): array
    {
        $user = User::create([
            'company_id' => $this->company->id,
            'branch_id' => $branch->id,
            'name' => $name,
            'email' => $email,
            'password' => Hash::make('Password123'),
            'status' => 'active',
        ]);

        $employee = Employee::create([
            'company_id' => $this->company->id,
            'branch_id' => $branch->id,
            'user_id' => $user->id,
            'department_id' => $this->department->id,
            'designation_id' => $this->designation->id,
            'employee_code' => $code,
            'first_name' => explode(' ', $name)[0],
            'last_name' => explode(' ', $name)[1] ?? 'Staff',
            'email' => $email,
            'joining_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        return [$user, $employee];
    }

    /**
     * Test employee can check in with GPS and ping_in log is created.
     */
    public function test_employee_can_check_in_with_gps_location(): void
    {
        [$user, $employee] = $this->createStaffMember($this->branch1, 'Anil Sharma', 'anil@gtfpl.test', 'EMP-1001');

        $response = $this->actingAs($user)->postJson('/admin/duty/check-in', [
            'latitude' => 22.5726,
            'longitude' => 88.3639,
            'accuracy' => 4.5,
            'remarks' => 'Morning duty start from branch gate',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.duty_status', 'active')
            ->assertJsonPath('data.employee_id', $employee->id);

        $this->assertDatabaseHas('attendances', [
            'employee_id' => $employee->id,
            'branch_id' => $this->branch1->id,
            'attendance_date' => now()->toDateString(),
            'status' => 'present',
        ]);

        $this->assertDatabaseHas('field_location_logs', [
            'user_id' => $user->id,
            'employee_id' => $employee->id,
            'event_type' => 'ping_in',
            'latitude' => 22.5726,
            'longitude' => 88.3639,
        ]);
    }

    /**
     * Test duplicate active check-in is rejected.
     */
    public function test_duplicate_check_in_is_rejected_when_already_on_active_duty(): void
    {
        [$user, $employee] = $this->createStaffMember($this->branch1, 'Sunil Verma', 'sunil@gtfpl.test', 'EMP-1002');

        // Initial check-in
        $this->actingAs($user)->postJson('/admin/duty/check-in', [
            'latitude' => 22.5726,
            'longitude' => 88.3639,
            'accuracy' => 5.0,
        ])->assertStatus(200);

        $attendance = Attendance::where('employee_id', $employee->id)->where('attendance_date', now()->toDateString())->first();
        $initialClockIn = $attendance->clock_in;

        // Duplicate check-in attempt
        $secondResponse = $this->actingAs($user)->postJson('/admin/duty/check-in', [
            'latitude' => 22.5800,
            'longitude' => 88.3700,
        ]);

        $secondResponse->assertStatus(422)
            ->assertJsonPath('success', false);

        // Verify initial clock_in was not overwritten
        $attendance->refresh();
        $this->assertEquals($initialClockIn, $attendance->clock_in);
    }

    /**
     * Test duty status endpoint returns correct active duty state for restoring after refresh.
     */
    public function test_duty_status_endpoint_returns_correct_active_duty_state_after_refresh(): void
    {
        [$user, $employee] = $this->createStaffMember($this->branch1, 'Rohit Roy', 'rohit@gtfpl.test', 'EMP-1003');

        // Check in
        $this->actingAs($user)->postJson('/admin/duty/check-in', [
            'latitude' => 22.5726,
            'longitude' => 88.3639,
        ])->assertStatus(200);

        // Status check
        $statusResponse = $this->actingAs($user)->getJson('/admin/duty/status');

        $statusResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.duty_status', 'active')
            ->assertJsonPath('data.status_label', 'Active On Duty')
            ->assertJsonPath('data.has_profile', true);

        $this->assertNotNull($statusResponse->json('data.clock_in'));
        $this->assertNull($statusResponse->json('data.clock_out'));
    }

    /**
     * Test employee can record periodic GPS pings while active on duty.
     */
    public function test_employee_can_record_periodic_gps_pings_while_on_duty(): void
    {
        [$user, $employee] = $this->createStaffMember($this->branch1, 'Manoj Dey', 'manoj@gtfpl.test', 'EMP-1004');

        $this->actingAs($user)->postJson('/admin/duty/check-in', [
            'latitude' => 22.5726,
            'longitude' => 88.3639,
        ]);

        $pingResponse = $this->actingAs($user)->postJson('/admin/duty/ping', [
            'latitude' => 22.5740,
            'longitude' => 88.3650,
            'accuracy' => 3.2,
            'speed' => 12.5,
            'battery_level' => 88,
        ]);

        $pingResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('is_active', true);

        $this->assertDatabaseHas('field_location_logs', [
            'user_id' => $user->id,
            'event_type' => 'location_ping',
            'latitude' => 22.5740,
            'longitude' => 88.3650,
            'battery_level' => 88,
        ]);
    }

    /**
     * Test employee can check out with GPS and working duration is calculated.
     */
    public function test_employee_can_check_out_and_working_duration_is_calculated(): void
    {
        [$user, $employee] = $this->createStaffMember($this->branch1, 'Bikash Das', 'bikash@gtfpl.test', 'EMP-1005');

        // Check in
        $this->actingAs($user)->postJson('/admin/duty/check-in', [
            'latitude' => 22.5726,
            'longitude' => 88.3639,
        ]);

        // Manually set clock_in 4 hours ago for exact duration verification
        $fourHoursAgo = now()->subHours(4)->format('H:i:s');
        Attendance::where('employee_id', $employee->id)->update([
            'clock_in' => $fourHoursAgo,
        ]);

        // Check out
        $checkOutResponse = $this->actingAs($user)->postJson('/admin/duty/check-out', [
            'latitude' => 22.5790,
            'longitude' => 88.3690,
            'accuracy' => 5.0,
            'remarks' => 'Evening duty conclusion',
        ]);

        $checkOutResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.duty_status', 'completed');

        $attendance = Attendance::where('employee_id', $employee->id)->first();
        $this->assertNotNull($attendance->clock_out);
        $this->assertNotNull($attendance->working_duration_minutes);
        $this->assertGreaterThanOrEqual(235, $attendance->working_duration_minutes);
        $this->assertGreaterThanOrEqual(3.9, (float) $attendance->working_duration_hours);

        // Ping out logged in field_location_logs
        $this->assertDatabaseHas('field_location_logs', [
            'user_id' => $user->id,
            'event_type' => 'ping_out',
            'latitude' => 22.5790,
            'longitude' => 88.3690,
        ]);
    }

    /**
     * Test repeated check-out request is rejected.
     */
    public function test_repeated_check_out_request_is_rejected(): void
    {
        [$user, $employee] = $this->createStaffMember($this->branch1, 'Tanmoy Roy', 'tanmoy@gtfpl.test', 'EMP-1006');

        $this->actingAs($user)->postJson('/admin/duty/check-in', [
            'latitude' => 22.5726,
            'longitude' => 88.3639,
        ]);

        $this->actingAs($user)->postJson('/admin/duty/check-out', [
            'latitude' => 22.5730,
            'longitude' => 88.3640,
        ])->assertStatus(200);

        // Second check-out attempt
        $secondOut = $this->actingAs($user)->postJson('/admin/duty/check-out', [
            'latitude' => 22.5730,
            'longitude' => 88.3640,
        ]);

        $secondOut->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    /**
     * Test Branch Manager cannot view route history of staff in another branch.
     */
    public function test_branch_manager_cannot_view_route_history_of_staff_from_another_branch(): void
    {
        [$bmUser, $bmEmp] = $this->createStaffMember($this->branch1, 'Manager North', 'manager.north@gtfpl.test', 'EMP-BM-01');
        $bmUser->assignRole($this->bmRole);

        [$southStaff, $southEmp] = $this->createStaffMember($this->branch2, 'Staff South', 'staff.south@gtfpl.test', 'EMP-SOUTH-01');

        // Create a location ping for South staff
        FieldLocationLog::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch2->id,
            'user_id' => $southStaff->id,
            'employee_id' => $southEmp->id,
            'event_type' => 'location_ping',
            'latitude' => 22.5100,
            'longitude' => 88.3400,
            'recorded_at' => now(),
        ]);

        // Branch 1 Manager requests Branch 2 Staff route history -> 403 Forbidden
        $response = $this->actingAs($bmUser)->getJson("/admin/field-tracking/route-history/{$southStaff->id}");
        $response->assertStatus(403);
    }

    /**
     * Test Branch Manager can view route history of staff in their own branch.
     */
    public function test_branch_manager_can_view_route_history_of_staff_in_own_branch(): void
    {
        [$bmUser, $bmEmp] = $this->createStaffMember($this->branch1, 'Manager North 2', 'manager.north2@gtfpl.test', 'EMP-BM-02');
        $bmUser->assignRole($this->bmRole);

        [$northStaff, $northEmp] = $this->createStaffMember($this->branch1, 'Staff North 2', 'staff.north2@gtfpl.test', 'EMP-NORTH-02');

        FieldLocationLog::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'user_id' => $northStaff->id,
            'employee_id' => $northEmp->id,
            'event_type' => 'ping_in',
            'latitude' => 22.5726,
            'longitude' => 88.3639,
            'recorded_at' => now()->subHours(2),
        ]);

        FieldLocationLog::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'user_id' => $northStaff->id,
            'employee_id' => $northEmp->id,
            'event_type' => 'location_ping',
            'latitude' => 22.5800,
            'longitude' => 88.3700,
            'recorded_at' => now()->subHour(),
        ]);

        $response = $this->actingAs($bmUser)->getJson("/admin/field-tracking/route-history/{$northStaff->id}");
        $response->assertStatus(200)
            ->assertJsonPath('user_id', $northStaff->id)
            ->assertJsonPath('total_points', 2);
    }

    /**
     * Test Dashboard page renders duty tracker component without error.
     */
    public function test_dashboard_page_renders_duty_tracker_without_error(): void
    {
        [$user, $employee] = $this->createStaffMember($this->branch1, 'Gopal Sen', 'gopal@gtfpl.test', 'EMP-1007');

        $response = $this->actingAs($user)->get('/admin');

        $response->assertStatus(200)
            ->assertSee('mobile-app-fixed-header', false)
            ->assertSee('mobileAppDrawer', false)
            ->assertSee('mobileAppBottomNavigation', false)
            ->assertSee('Employee Duty & Live Attendance', false)
            ->assertSee('Check-In & Start Duty', false)
            ->assertSee('btnDutyCheckIn', false);
    }
}
