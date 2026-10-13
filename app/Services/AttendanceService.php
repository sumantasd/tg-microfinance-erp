<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\FieldLocationLog;
use App\Models\User;
use App\Repositories\AttendanceRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class AttendanceService
{
    public function __construct(
        protected AttendanceRepositoryInterface $attendanceRepo,
        protected ActivityLogService $activityLogger
    ) {}

    public function getPaginatedAttendances(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->attendanceRepo->getPaginatedAttendances($filters, $perPage);
    }

    public function getAttendanceById(int $id): ?Attendance
    {
        return $this->attendanceRepo->findById($id);
    }

    public function markDailyAttendance(array $data): Attendance
    {
        $data['created_by'] = auth()->id();
        $attendance = $this->attendanceRepo->markAttendance($data);

        $this->activityLogger->log(
            'Mark Attendance',
            $attendance,
            null,
            ['status' => $attendance->status, 'date' => $attendance->attendance_date->format('Y-m-d')]
        );

        return $attendance;
    }

    /**
     * Get real-time duty state for an authenticated user for today (or specified date).
     */
    public function getDutyStatusForUser(User $user, ?string $date = null): array
    {
        $targetDate = $date ?? now()->toDateString();
        $employee = $user->resolveEmployeeProfile();

        if (!$employee) {
            return [
                'has_profile' => false,
                'duty_status' => 'unlinked',
                'status_label' => 'No Employee Profile',
                'badge_class' => 'bg-secondary',
                'attendance' => null,
                'clock_in' => null,
                'clock_in_formatted' => null,
                'clock_out' => null,
                'clock_out_formatted' => null,
                'elapsed_seconds' => 0,
                'working_duration' => null,
                'working_duration_minutes' => null,
                'last_ping' => null,
                'employee' => null,
            ];
        }

        $employee->loadMissing(['branch', 'designation', 'department']);

        $attendance = Attendance::where('employee_id', $employee->id)
            ->where('attendance_date', $targetDate)
            ->first();

        $lastPing = FieldLocationLog::where('user_id', $user->id)
            ->whereDate('recorded_at', $targetDate)
            ->latest('recorded_at')
            ->first();

        if (!$attendance || empty($attendance->clock_in)) {
            return [
                'has_profile' => true,
                'duty_status' => 'not_started',
                'status_label' => 'Off Duty',
                'badge_class' => 'bg-secondary text-white',
                'attendance' => null,
                'clock_in' => null,
                'clock_in_formatted' => null,
                'clock_out' => null,
                'clock_out_formatted' => null,
                'elapsed_seconds' => 0,
                'working_duration' => null,
                'working_duration_minutes' => null,
                'last_ping' => $lastPing,
                'employee' => $employee,
            ];
        }

        if (empty($attendance->clock_out)) {
            // Active shift
            $clockInDateTime = Carbon::parse("{$targetDate} {$attendance->clock_in}");
            $elapsedSeconds = max(0, $clockInDateTime->diffInSeconds(now()));
            $elapsedMinutes = intdiv($elapsedSeconds, 60);
            $hours = intdiv($elapsedMinutes, 60);
            $mins = $elapsedMinutes % 60;
            $durationFormatted = "{$hours}h {$mins}m";

            return [
                'has_profile' => true,
                'duty_status' => 'active',
                'status_label' => 'Active On Duty',
                'badge_class' => 'bg-success text-white',
                'attendance' => $attendance,
                'clock_in' => $attendance->clock_in,
                'clock_in_formatted' => $clockInDateTime->format('h:i A'),
                'clock_out' => null,
                'clock_out_formatted' => null,
                'elapsed_seconds' => $elapsedSeconds,
                'working_duration' => $durationFormatted,
                'working_duration_minutes' => $elapsedMinutes,
                'last_ping' => $lastPing,
                'employee' => $employee,
            ];
        }

        // Completed shift
        $clockInDateTime = Carbon::parse("{$targetDate} {$attendance->clock_in}");
        $clockOutDateTime = Carbon::parse("{$targetDate} {$attendance->clock_out}");

        return [
            'has_profile' => true,
            'duty_status' => 'completed',
            'status_label' => 'Duty Completed',
            'badge_class' => 'bg-primary text-white',
            'attendance' => $attendance,
            'clock_in' => $attendance->clock_in,
            'clock_in_formatted' => $clockInDateTime->format('h:i A'),
            'clock_out' => $attendance->clock_out,
            'clock_out_formatted' => $clockOutDateTime->format('h:i A'),
            'elapsed_seconds' => 0,
            'working_duration' => $attendance->formatted_duration,
            'working_duration_minutes' => $attendance->working_duration_minutes,
            'last_ping' => $lastPing,
            'employee' => $employee,
        ];
    }

    /**
     * Start duty session (Check-In) with GPS.
     */
    public function checkInDuty(
        User $user,
        float $latitude,
        float $longitude,
        ?float $accuracy = null,
        ?string $remarks = null
    ): array {
        $employee = $user->resolveEmployeeProfile();
        if (!$employee) {
            throw ValidationException::withMessages([
                'employee' => ['Authenticated staff user is not linked to an employee profile.'],
            ]);
        }

        $branchId = $employee->branch_id ?? $user->branch_id ?? 1;
        $companyId = $employee->company_id ?? $user->company_id ?? 1;

        $date = now()->toDateString();
        $time = now()->toTimeString();

        // Check for duplicate active sessions or already completed shifts
        $existing = Attendance::where('employee_id', $employee->id)
            ->where('attendance_date', $date)
            ->first();

        if ($existing && !empty($existing->clock_in)) {
            if (empty($existing->clock_out)) {
                throw ValidationException::withMessages([
                    'attendance' => ["You already have an active duty session started at {$existing->clock_in}. Duplicate check-in is not permitted."],
                ]);
            } else {
                throw ValidationException::withMessages([
                    'attendance' => ["Duty shift has already been completed for today ({$existing->clock_in} - {$existing->clock_out})."],
                ]);
            }
        }

        $gpsInfo = "GPS: {$latitude},{$longitude}";
        if ($accuracy !== null) {
            $gpsInfo .= " (acc: {$accuracy}m)";
        }
        $formattedRemarks = ($remarks ? "{$remarks} " : 'Duty Check-in ') . "[{$gpsInfo}]";

        $attendance = Attendance::updateOrCreate(
            ['employee_id' => $employee->id, 'attendance_date' => $date],
            [
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'clock_in' => $time,
                'status' => 'present',
                'remarks' => $formattedRemarks,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]
        );

        // Record ping_in event in field_location_logs
        $locationLog = FieldLocationLog::create([
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'user_id' => $user->id,
            'employee_id' => $employee->id,
            'event_type' => 'ping_in',
            'latitude' => $latitude,
            'longitude' => $longitude,
            'accuracy' => $accuracy,
            'notes' => 'Duty Check-in: ' . ($remarks ?? 'Staff duty started'),
            'recorded_at' => now(),
        ]);

        $this->activityLogger->log(
            'Duty Check-In',
            $attendance,
            null,
            ['employee' => $employee->full_name, 'clock_in' => $time, 'lat' => $latitude, 'lng' => $longitude]
        );

        return [
            'success' => true,
            'duty_status' => 'active',
            'attendance_id' => $attendance->id,
            'employee_id' => $employee->id,
            'employee_code' => $employee->employee_code,
            'employee_name' => $employee->full_name ?? $user->name,
            'branch_id' => $attendance->branch_id,
            'company_id' => $attendance->company_id,
            'date' => $date,
            'clock_in' => $time,
            'clock_in_formatted' => Carbon::parse($time)->format('h:i A'),
            'status' => $attendance->status,
            'gps_location' => [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'accuracy' => $accuracy,
            ],
            'location_log_id' => $locationLog->id,
        ];
    }

    /**
     * End duty session (Check-Out) with GPS and calculate working duration.
     */
    public function checkOutDuty(
        User $user,
        float $latitude,
        float $longitude,
        ?float $accuracy = null,
        ?string $remarks = null
    ): array {
        $employee = $user->resolveEmployeeProfile();
        if (!$employee) {
            throw ValidationException::withMessages([
                'employee' => ['Authenticated staff user is not linked to an employee profile.'],
            ]);
        }

        $date = now()->toDateString();
        $time = now()->toTimeString();

        $attendance = Attendance::where('employee_id', $employee->id)
            ->where('attendance_date', $date)
            ->first();

        if (!$attendance || empty($attendance->clock_in)) {
            throw ValidationException::withMessages([
                'attendance' => ['No active check-in record found for today. You must check in before checking out.'],
            ]);
        }

        if (!empty($attendance->clock_out)) {
            throw ValidationException::withMessages([
                'attendance' => ['Duty check-out has already been completed for today. Repeated check-out is not allowed.'],
            ]);
        }

        // Calculate server-side working duration
        $clockInDateTime = Carbon::parse("{$date} {$attendance->clock_in}");
        $clockOutDateTime = Carbon::parse("{$date} {$time}");
        $durationMinutes = max(1, $clockInDateTime->diffInMinutes($clockOutDateTime));
        $durationHours = round($durationMinutes / 60, 2);
        $hours = intdiv($durationMinutes, 60);
        $mins = $durationMinutes % 60;
        $durationFormatted = "{$hours} hrs {$mins} mins";

        $gpsInfo = "GPS Checkout: {$latitude},{$longitude}";
        if ($accuracy !== null) {
            $gpsInfo .= " (acc: {$accuracy}m)";
        }
        $updatedRemarks = trim(($attendance->remarks ? "{$attendance->remarks} | " : '') . ($remarks ? "{$remarks} " : 'Duty Check-out ') . "[{$gpsInfo}] [Duration: {$durationFormatted}]");

        $attendance->update([
            'clock_out' => $time,
            'working_duration_minutes' => $durationMinutes,
            'working_duration_hours' => $durationHours,
            'remarks' => $updatedRemarks,
            'updated_by' => $user->id,
        ]);

        // Record ping_out event in field_location_logs
        $locationLog = FieldLocationLog::create([
            'company_id' => $attendance->company_id,
            'branch_id' => $attendance->branch_id,
            'user_id' => $user->id,
            'employee_id' => $employee->id,
            'event_type' => 'ping_out',
            'latitude' => $latitude,
            'longitude' => $longitude,
            'accuracy' => $accuracy,
            'notes' => "Duty Check-out: Duration {$durationFormatted}",
            'recorded_at' => now(),
        ]);

        $this->activityLogger->log(
            'Duty Check-Out',
            $attendance,
            null,
            ['employee' => $employee->full_name, 'clock_out' => $time, 'duration' => $durationFormatted]
        );

        return [
            'success' => true,
            'duty_status' => 'completed',
            'attendance_id' => $attendance->id,
            'employee_id' => $employee->id,
            'employee_code' => $employee->employee_code,
            'employee_name' => $employee->full_name ?? $user->name,
            'branch_id' => $attendance->branch_id,
            'company_id' => $attendance->company_id,
            'date' => $date,
            'clock_in' => $attendance->clock_in,
            'clock_in_formatted' => Carbon::parse($attendance->clock_in)->format('h:i A'),
            'clock_out' => $time,
            'clock_out_formatted' => Carbon::parse($time)->format('h:i A'),
            'working_duration' => $durationFormatted,
            'working_duration_minutes' => $durationMinutes,
            'working_duration_hours' => $durationHours,
            'status' => $attendance->status,
            'gps_location' => [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'accuracy' => $accuracy,
            ],
            'location_log_id' => $locationLog->id,
        ];
    }

    /**
     * Record periodic GPS ping while duty is active.
     */
    public function logDutyLocationPing(
        User $user,
        float $latitude,
        float $longitude,
        ?float $accuracy = null,
        ?float $speed = null,
        ?int $batteryLevel = null,
        ?string $address = null,
        ?string $notes = null
    ): FieldLocationLog {
        $employee = $user->resolveEmployeeProfile();
        $date = now()->toDateString();

        // Verify if duty is currently active today
        $attendance = $employee ? Attendance::where('employee_id', $employee->id)->where('attendance_date', $date)->first() : null;

        $log = FieldLocationLog::create([
            'company_id' => $user->company_id ?? 1,
            'branch_id' => $user->branch_id ?? 1,
            'user_id' => $user->id,
            'employee_id' => $employee?->id,
            'event_type' => 'location_ping',
            'latitude' => $latitude,
            'longitude' => $longitude,
            'accuracy' => $accuracy,
            'speed' => $speed,
            'battery_level' => $batteryLevel,
            'location_address' => $address,
            'notes' => $notes ?? 'Periodic live GPS tracking ping',
            'recorded_at' => now(),
        ]);

        return $log;
    }
}
