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
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AttendanceTimezoneRegressionTest extends TestCase
{
    use DatabaseTransactions;

    protected Company $company;
    protected Branch $branch;
    protected Department $department;
    protected Designation $designation;
    protected User $user;
    protected Employee $employee;
    protected AttendanceService $attendanceService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->attendanceService = app(AttendanceService::class);

        $this->company = Company::create([
            'name' => 'Grihalaxmi Test Microfinance',
            'code' => 'GTM-TEST',
            'email' => 'tz_test@grihalaxmi.test',
            'phone' => '9800098000',
            'address' => 'Kolkata Central HQ',
            'is_active' => true,
        ]);

        $this->branch = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Kolkata Main Branch',
            'code' => 'BR-KOL-TEST',
            'phone' => '0331000001',
            'address' => 'Park Street',
            'city' => 'Kolkata',
            'state' => 'West Bengal',
            'pincode' => '700016',
            'is_active' => true,
        ]);

        $this->department = Department::create([
            'company_id' => $this->company->id,
            'name' => 'Operations',
            'code' => 'OPS-TZ',
        ]);

        $this->designation = Designation::create([
            'company_id' => $this->company->id,
            'department_id' => $this->department->id,
            'title' => 'Loan Officer',
            'code' => 'LO-TZ',
        ]);

        $this->user = User::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'Subhashish Bose',
            'email' => 'subhashish@grihalaxmi.test',
            'password' => Hash::make('Password123'),
            'status' => 'active',
        ]);

        $this->employee = Employee::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'user_id' => $this->user->id,
            'department_id' => $this->department->id,
            'designation_id' => $this->designation->id,
            'employee_code' => 'EMP-TZ-001',
            'first_name' => 'Subhashish',
            'last_name' => 'Bose',
            'email' => 'subhashish@grihalaxmi.test',
            'joining_date' => now()->toDateString(),
            'status' => 'active',
        ]);
    }

    /**
     * Requirement: Verify known timestamp 2026-10-13 13:37:00 UTC displays as 07:07 PM IST on the same date.
     */
    public function test_known_timestamp_utc_to_ist_conversion_displays_0707_pm(): void
    {
        $utcString = '2026-10-13 13:37:00';

        // 1. Service method with full datetime string
        $formatted = $this->attendanceService->formatToBusinessTime($utcString);
        $this->assertEquals('07:07 PM', $formatted, '2026-10-13 13:37:00 UTC must format to 07:07 PM IST');

        // 2. Service method with time and date context
        $formattedWithDate = $this->attendanceService->formatToBusinessTime('13:37:00', '2026-10-13');
        $this->assertEquals('07:07 PM', $formattedWithDate, '13:37:00 with date context must format to 07:07 PM IST');

        // 3. Carbon instance in UTC
        $utcCarbon = Carbon::parse($utcString, 'UTC');
        $formattedCarbon = $this->attendanceService->formatToBusinessTime($utcCarbon);
        $this->assertEquals('07:07 PM', $formattedCarbon, 'Carbon UTC instance must format to 07:07 PM IST');

        // 4. Verify on the same date in Asia/Kolkata
        $istDate = Carbon::parse($utcString, 'UTC')->setTimezone('Asia/Kolkata')->toDateString();
        $this->assertEquals('2026-10-13', $istDate, '13:37:00 UTC on 2026-10-13 remains on the same date in IST');
    }

    /**
     * Requirement: Verify Attendance model accessors format clock_in and clock_out in IST.
     */
    public function test_attendance_model_accessors_format_timestamps_in_ist(): void
    {
        $attendance = Attendance::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'employee_id' => $this->employee->id,
            'attendance_date' => '2026-10-13',
            'clock_in' => '13:37:00',
            'clock_out' => '15:37:00',
            'working_duration_minutes' => 120,
            'working_duration_hours' => 2.0,
            'status' => 'present',
        ]);

        $this->assertEquals('07:07 PM', $attendance->clock_in_formatted);
        $this->assertEquals('09:07 PM', $attendance->clock_out_formatted);
        $this->assertEquals('2 hrs 0 mins', $attendance->formatted_duration);
    }

    /**
     * Requirement: Verify FieldLocationLog accessor formats recorded_at in IST.
     */
    public function test_field_location_log_accessor_formats_recorded_at_in_ist(): void
    {
        $log = FieldLocationLog::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'user_id' => $this->user->id,
            'employee_id' => $this->employee->id,
            'event_type' => 'location_ping',
            'latitude' => 22.5726,
            'longitude' => 88.3639,
            'accuracy' => 5.0,
            'recorded_at' => Carbon::parse('2026-10-13 13:37:00', 'UTC'),
        ]);

        $this->assertEquals('07:07 PM', $log->formatted_time);
        $this->assertEquals('2026-10-13 19:07:00', $log->recorded_at_ist->format('Y-m-d H:i:s'));
    }

    /**
     * Requirement: Verify AttendanceService::getDutyStatusForUser returns IST formatted times for active shift.
     */
    public function test_duty_status_returns_ist_formatted_check_in_and_last_ping(): void
    {
        $targetDate = '2026-10-13';

        // Check in at 13:37:00 UTC (07:07 PM IST)
        Attendance::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'employee_id' => $this->employee->id,
            'attendance_date' => $targetDate,
            'clock_in' => '13:37:00',
            'status' => 'present',
        ]);

        FieldLocationLog::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'user_id' => $this->user->id,
            'employee_id' => $this->employee->id,
            'event_type' => 'ping_in',
            'latitude' => 22.5726,
            'longitude' => 88.3639,
            'accuracy' => 4.0,
            'recorded_at' => Carbon::parse('2026-10-13 13:37:00', 'UTC'),
        ]);

        $status = $this->attendanceService->getDutyStatusForUser($this->user, $targetDate);

        $this->assertEquals('active', $status['duty_status']);
        $this->assertEquals('13:37:00', $status['clock_in'], 'Authoritative raw clock_in must remain UTC string');
        $this->assertEquals('07:07 PM', $status['clock_in_formatted'], 'clock_in_formatted must be in IST');
        $this->assertEquals('07:07 PM', $status['last_ping_time_formatted'], 'last_ping_time_formatted must be in IST');
        $this->assertNull($status['clock_out']);
        $this->assertNull($status['clock_out_formatted']);
    }

    /**
     * Requirement: Verify AttendanceService::getDutyStatusForUser returns IST formatted times for completed shift.
     */
    public function test_duty_status_returns_ist_formatted_times_for_completed_shift(): void
    {
        $targetDate = '2026-10-13';

        Attendance::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'employee_id' => $this->employee->id,
            'attendance_date' => $targetDate,
            'clock_in' => '13:37:00',
            'clock_out' => '15:37:00',
            'working_duration_minutes' => 120,
            'working_duration_hours' => 2.0,
            'status' => 'present',
        ]);

        $status = $this->attendanceService->getDutyStatusForUser($this->user, $targetDate);

        $this->assertEquals('completed', $status['duty_status']);
        $this->assertEquals('07:07 PM', $status['clock_in_formatted']);
        $this->assertEquals('09:07 PM', $status['clock_out_formatted']);
        $this->assertEquals('2 hrs 0 mins', $status['working_duration']);
    }

    /**
     * Requirement: Verify Check-in HTTP endpoint returns authoritative IST clock_in_formatted.
     */
    public function test_check_in_api_returns_ist_formatted_time(): void
    {
        $res = $this->actingAs($this->user)->postJson('/admin/duty/check-in', [
            'latitude' => 22.5726,
            'longitude' => 88.3639,
            'accuracy' => 5.0,
        ]);

        $res->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.duty_status', 'active');

        $clockIn = $res->json('data.clock_in');
        $clockInFormatted = $res->json('data.clock_in_formatted');

        // Verify clock_in_formatted matches regex for 12-hour AM/PM format (e.g. 07:07 PM)
        $this->assertMatchesRegularExpression('/^(0[1-9]|1[0-2]):[0-5][0-9] (AM|PM)$/', $clockInFormatted);

        // Verify that clock_in_formatted matches the IST conversion of clock_in
        $expectedIst = Carbon::parse(now()->toDateString() . ' ' . $clockIn, 'UTC')
            ->setTimezone('Asia/Kolkata')
            ->format('h:i A');
        $this->assertEquals($expectedIst, $clockInFormatted);
    }

    /**
     * Requirement: Verify Check-out HTTP endpoint returns authoritative IST clock_out_formatted and working duration.
     */
    public function test_check_out_api_returns_ist_formatted_time_and_duration(): void
    {
        // First check in
        $this->actingAs($this->user)->postJson('/admin/duty/check-in', [
            'latitude' => 22.5726,
            'longitude' => 88.3639,
        ])->assertStatus(200);

        // Check out
        $outRes = $this->actingAs($this->user)->postJson('/admin/duty/check-out', [
            'latitude' => 22.5730,
            'longitude' => 88.3645,
            'accuracy' => 4.0,
        ]);

        $outRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.duty_status', 'completed');

        $clockOutFormatted = $outRes->json('data.clock_out_formatted');
        $this->assertMatchesRegularExpression('/^(0[1-9]|1[0-2]):[0-5][0-9] (AM|PM)$/', $clockOutFormatted);
        $this->assertNotNull($outRes->json('data.working_duration'));
    }

    /**
     * Requirement: Verify GPS ping HTTP endpoint returns authoritative IST recorded_at_formatted.
     */
    public function test_ping_api_returns_ist_formatted_timestamp(): void
    {
        $this->actingAs($this->user)->postJson('/admin/duty/check-in', [
            'latitude' => 22.5726,
            'longitude' => 88.3639,
        ]);

        $pingRes = $this->actingAs($this->user)->postJson('/admin/duty/ping', [
            'latitude' => 22.5740,
            'longitude' => 88.3650,
            'accuracy' => 3.5,
        ]);

        $pingRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('is_active', true);

        $recordedAtFormatted = $pingRes->json('recorded_at_formatted');
        $this->assertNotNull($recordedAtFormatted);
        $this->assertMatchesRegularExpression('/^(0[1-9]|1[0-2]):[0-5][0-9] (AM|PM)$/', $recordedAtFormatted);
    }

    /**
     * Requirement: Verify existing record timestamps in database are preserved and formatted correctly in IST.
     */
    public function test_existing_database_record_timestamps_are_unaltered(): void
    {
        // Simulate an existing attendance record (like Record 1 in production: clock_in = '10:32:35')
        $record = Attendance::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'employee_id' => $this->employee->id,
            'attendance_date' => '2026-10-13',
            'clock_in' => '10:32:35',
            'status' => 'present',
        ]);

        // Refresh from database to ensure raw column value is untouched
        $record->refresh();

        // 1. Assert raw database column has not been mutated
        $this->assertEquals('10:32:35', $record->clock_in, 'Database column clock_in must remain raw 10:32:35');
        $this->assertEquals('2026-10-13', $record->attendance_date->format('Y-m-d'));

        // 2. In Asia/Kolkata (+05:30), 10:32:35 UTC must format to 04:02 PM IST
        $this->assertEquals('04:02 PM', $record->clock_in_formatted, 'Record formatted in IST must display 04:02 PM');
    }
}
