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

        $employee = Employee::where('user_id', $user->id)->first();
        if (!$employee) {
            return $this->errorResponse('Authenticated user is not linked to an employee profile', 422);
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
                'company_id' => $user->company_id,
                'branch_id' => $user->branch_id,
                'clock_in' => $time,
                'status' => 'present',
                'remarks' => $remarks,
                'created_by' => $user->id,
            ]
        );

        if (!$attendance->wasRecentlyCreated) {
            $attendance->update([
                'clock_in' => $time,
                'status' => 'present',
                'remarks' => $remarks,
                'updated_by' => $user->id,
            ]);
        }

        return $this->successResponse([
            'attendance_id' => $attendance->id,
            'employee_name' => $user->name,
            'date' => $date,
            'clock_in' => $time,
            'status' => $attendance->status,
            'gps_location' => [
                'latitude' => $validated['latitude'],
                'longitude' => $validated['longitude'],
                'accuracy' => $validated['accuracy'] ?? null,
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

        $employee = Employee::where('user_id', $user->id)->first();
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
            'employee_name' => $user->name,
            'date' => $date,
            'clock_in' => $attendance->clock_in,
            'clock_out' => $time,
            'status' => $attendance->status,
            'gps_location' => [
                'latitude' => $validated['latitude'],
                'longitude' => $validated['longitude'],
                'accuracy' => $validated['accuracy'] ?? null,
            ],
        ], 'Staff check-out recorded successfully');
    }
}
