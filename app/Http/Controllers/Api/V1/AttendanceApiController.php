<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\AttendanceService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AttendanceApiController extends Controller
{
    use ApiResponse;

    public function __construct(protected AttendanceService $attendanceService) {}

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
        if (!$branchId) {
            return $this->errorResponse('No assigned branch found for attendance check-in', 422);
        }

        try {
            $data = $this->attendanceService->checkInDuty(
                user: $user,
                latitude: (float) $validated['latitude'],
                longitude: (float) $validated['longitude'],
                accuracy: isset($validated['accuracy']) ? (float) $validated['accuracy'] : null,
                remarks: $validated['remarks'] ?? 'Mobile Check-in'
            );

            return $this->successResponse($data, 'Staff check-in recorded successfully');
        } catch (ValidationException $e) {
            $firstError = collect($e->errors())->flatten()->first();
            return $this->errorResponse($firstError, 409);
        }
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

        try {
            $data = $this->attendanceService->checkOutDuty(
                user: $user,
                latitude: (float) $validated['latitude'],
                longitude: (float) $validated['longitude'],
                accuracy: isset($validated['accuracy']) ? (float) $validated['accuracy'] : null,
                remarks: $validated['remarks'] ?? 'Mobile Check-out'
            );

            return $this->successResponse($data, 'Staff check-out recorded successfully');
        } catch (ValidationException $e) {
            $firstError = collect($e->errors())->flatten()->first();
            // If no active check-in found, return 400 for backwards compatibility with Mobile API spec
            $status = str_contains($firstError, 'No active check-in') ? 400 : 409;
            return $this->errorResponse($firstError, $status);
        }
    }
}
