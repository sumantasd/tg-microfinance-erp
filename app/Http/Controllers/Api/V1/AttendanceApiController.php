<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Employee;
use App\Services\AttendanceService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceApiController extends Controller
{
    use ApiResponse;

    protected AttendanceService $attendanceService;

    public function __construct(AttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }

    /**
     * Mobile check-in with GPS location.
     */
    public function checkIn(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'accuracy' => 'nullable|numeric',
            'remarks' => 'nullable|string|max:255',
        ]);

        $employee = $user->resolveEmployeeProfile();
        if (!$employee) {
            return $this->errorResponse('Authenticated user is not linked to an employee profile', 422);
        }

        $branchId = $employee->branch_id ?? $user->branch_id;
        $companyId = $employee->company_id ?? $user->company_id;

        if (!$branchId) {
            return $this->errorResponse('No assigned branch found for attendance check-in', 422);
        }

        $date = now()->toDateString();
        $time = now()->toTimeString();

        $gpsInfo = "GPS: {$validated['latitude']},{$validated['longitude']}";
        if (isset($validated['accuracy'])) {
            $gpsInfo .= " (acc: {$validated['accuracy']}m)";
        }
        $remarks = ($validated['remarks'] ?? 'Mobile Check-in') . " [{$gpsInfo}]";

        $attendance = Attendance::firstOrCreate(
            ['employee_id' => $employee->id, 'attendance_date' => $date],
            [
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'clock_in' => $time,
                'status' => 'present',
                'remarks' => $remarks,
                'created_by' => $user->id,
            ]
        );

        if (!$attendance->wasRecentlyCreated) {
            $attendance->update([
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'clock_in' => $time,
                'status' => 'present',
                'remarks' => $remarks,
                'updated_by' => $user->id,
            ]);
        }

        return $this->successResponse([
            'attendance_id' => $attendance->id,
            'employee_id' => $employee->id,
            'employee_code' => $employee->employee_code,
            'employee_name' => $employee->full_name ?? $user->name,
            'branch_id' => $attendance->branch_id,
            'company_id' => $attendance->company_id,
            'date' => $date,
            'clock_in' => $time,
            'status' => $attendance->status,
            'gps_location' => [
                'latitude' => (float) $validated['latitude'],
                'longitude' => (float) $validated['longitude'],
                'accuracy' => isset($validated['accuracy']) ? (float) $validated['accuracy'] : null,
            ],
        ], 'Staff check-in recorded successfully');
    }

    /**
     * Mobile check-out with GPS location.
     */
    public function checkOut(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'accuracy' => 'nullable|numeric',
            'remarks' => 'nullable|string|max:255',
        ]);

        $employee = $user->resolveEmployeeProfile();
        if (!$employee) {
            return $this->errorResponse('Authenticated user is not linked to an employee profile', 422);
        }

        $date = now()->toDateString();
        $time = now()->toTimeString();

        $attendance = Attendance::where('employee_id', $employee->id)->where('attendance_date', $date)->first();

        if (!$attendance) {
            return $this->errorResponse('No active check-in record found for today', 400);
        }

        $gpsInfo = "GPS Checkout: {$validated['latitude']},{$validated['longitude']}";
        $remarks = $attendance->remarks . " | " . ($validated['remarks'] ?? 'Mobile Check-out') . " [{$gpsInfo}]";

        $attendance->update([
            'clock_out' => $time,
            'remarks' => $remarks,
            'updated_by' => $user->id,
        ]);

        return $this->successResponse([
            'attendance_id' => $attendance->id,
            'employee_id' => $employee->id,
            'employee_code' => $employee->employee_code,
            'employee_name' => $employee->full_name ?? $user->name,
            'branch_id' => $attendance->branch_id,
            'company_id' => $attendance->company_id,
            'date' => $date,
            'clock_in' => $attendance->clock_in,
            'clock_out' => $time,
            'status' => $attendance->status,
            'gps_location' => [
                'latitude' => (float) $validated['latitude'],
                'longitude' => (float) $validated['longitude'],
                'accuracy' => isset($validated['accuracy']) ? (float) $validated['accuracy'] : null,
            ],
        ], 'Staff check-out recorded successfully');
    }
}
