<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class StaffDutyController extends Controller
{
    public function __construct(protected AttendanceService $attendanceService) {}

    /**
     * Get current authenticated staff member's duty status for today.
     */
    public function status(Request $request): JsonResponse
    {
        $user = Auth::user();
        $dutyInfo = $this->attendanceService->getDutyStatusForUser($user);

        return response()->json([
            'success' => true,
            'data' => $dutyInfo,
        ]);
    }

    /**
     * Start duty session (Check-in) with GPS.
     */
    public function checkIn(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'accuracy' => 'nullable|numeric|min:0',
            'remarks' => 'nullable|string|max:255',
        ]);

        $user = Auth::user();

        try {
            $result = $this->attendanceService->checkInDuty(
                user: $user,
                latitude: (float) $validated['latitude'],
                longitude: (float) $validated['longitude'],
                accuracy: isset($validated['accuracy']) ? (float) $validated['accuracy'] : null,
                remarks: $validated['remarks'] ?? 'Web Dashboard Check-in'
            );

            return response()->json([
                'success' => true,
                'message' => 'Duty check-in successful. Have a productive shift!',
                'data' => $result,
            ]);
        } catch (ValidationException $e) {
            $msg = collect($e->errors())->flatten()->first();
            return response()->json([
                'success' => false,
                'message' => $msg,
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to record check-in: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * End duty session (Check-out) with GPS and calculate working duration.
     */
    public function checkOut(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'accuracy' => 'nullable|numeric|min:0',
            'remarks' => 'nullable|string|max:255',
        ]);

        $user = Auth::user();

        try {
            $result = $this->attendanceService->checkOutDuty(
                user: $user,
                latitude: (float) $validated['latitude'],
                longitude: (float) $validated['longitude'],
                accuracy: isset($validated['accuracy']) ? (float) $validated['accuracy'] : null,
                remarks: $validated['remarks'] ?? 'Web Dashboard Check-out'
            );

            return response()->json([
                'success' => true,
                'message' => "Duty shift ended successfully. Total duration: {$result['working_duration']}.",
                'data' => $result,
            ]);
        } catch (ValidationException $e) {
            $msg = collect($e->errors())->flatten()->first();
            return response()->json([
                'success' => false,
                'message' => $msg,
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to record check-out: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Periodic live GPS ping from active dashboard session.
     */
    public function ping(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'accuracy' => 'nullable|numeric|min:0',
            'speed' => 'nullable|numeric|min:0',
            'battery_level' => 'nullable|integer|between:0,100',
            'location_address' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:255',
        ]);

        $user = Auth::user();
        $dutyInfo = $this->attendanceService->getDutyStatusForUser($user);

        if ($dutyInfo['duty_status'] !== 'active') {
            return response()->json([
                'success' => false,
                'is_active' => false,
                'message' => 'Duty shift is not currently active. Periodic GPS tracking paused.',
            ]);
        }

        $log = $this->attendanceService->logDutyLocationPing(
            user: $user,
            latitude: (float) $validated['latitude'],
            longitude: (float) $validated['longitude'],
            accuracy: isset($validated['accuracy']) ? (float) $validated['accuracy'] : null,
            speed: isset($validated['speed']) ? (float) $validated['speed'] : null,
            batteryLevel: isset($validated['battery_level']) ? (int) $validated['battery_level'] : null,
            address: $validated['location_address'] ?? null,
            notes: $validated['notes'] ?? 'Web dashboard active duty ping'
        );

        $timeFormatted = $this->attendanceService->formatToBusinessTime($log->recorded_at);

        return response()->json([
            'success' => true,
            'is_active' => true,
            'log_id' => $log->id,
            'recorded_at' => $log->recorded_at->toDateTimeString(),
            'recorded_at_formatted' => $timeFormatted,
        ]);
    }
}
