<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\LocationTrackingService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocationApiController extends Controller
{
    use ApiResponse;

    public function __construct(protected LocationTrackingService $locationTrackingService) {}

    /**
     * Start duty shift with GPS (Ping-In).
     */
    public function pingIn(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'accuracy' => 'nullable|numeric',
            'notes' => 'nullable|string|max:255',
            'offline_sync_id' => 'nullable|string|max:100',
        ]);

        $log = $this->locationTrackingService->pingIn(
            user: $request->user(),
            lat: (float) $validated['latitude'],
            lng: (float) $validated['longitude'],
            accuracy: isset($validated['accuracy']) ? (float) $validated['accuracy'] : null,
            notes: $validated['notes'] ?? null,
            syncId: $validated['offline_sync_id'] ?? null
        );

        return $this->successResponse([
            'log_id' => $log->id,
            'event_type' => 'ping_in',
            'user_name' => $request->user()->name,
            'latitude' => $log->latitude,
            'longitude' => $log->longitude,
            'recorded_at' => $log->recorded_at->toDateTimeString(),
        ], 'Duty ping-in recorded successfully');
    }

    /**
     * End duty shift with GPS (Ping-Out).
     */
    public function pingOut(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'accuracy' => 'nullable|numeric',
            'notes' => 'nullable|string|max:255',
            'offline_sync_id' => 'nullable|string|max:100',
        ]);

        $log = $this->locationTrackingService->pingOut(
            user: $request->user(),
            lat: (float) $validated['latitude'],
            lng: (float) $validated['longitude'],
            accuracy: isset($validated['accuracy']) ? (float) $validated['accuracy'] : null,
            notes: $validated['notes'] ?? null,
            syncId: $validated['offline_sync_id'] ?? null
        );

        return $this->successResponse([
            'log_id' => $log->id,
            'event_type' => 'ping_out',
            'user_name' => $request->user()->name,
            'latitude' => $log->latitude,
            'longitude' => $log->longitude,
            'recorded_at' => $log->recorded_at->toDateTimeString(),
        ], 'Duty ping-out recorded successfully');
    }

    /**
     * Log a single live location ping.
     */
    public function ping(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'event_type' => 'nullable|string|in:location_ping,field_visit,ping_in,ping_out',
            'accuracy' => 'nullable|numeric',
            'speed' => 'nullable|numeric',
            'battery_level' => 'nullable|integer|between:0,100',
            'location_address' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:255',
            'offline_sync_id' => 'nullable|string|max:100',
            'recorded_at' => 'nullable|date',
        ]);

        $log = $this->locationTrackingService->logPing(
            user: $request->user(),
            latitude: (float) $validated['latitude'],
            longitude: (float) $validated['longitude'],
            eventType: $validated['event_type'] ?? 'location_ping',
            accuracy: isset($validated['accuracy']) ? (float) $validated['accuracy'] : null,
            speed: isset($validated['speed']) ? (float) $validated['speed'] : null,
            batteryLevel: isset($validated['battery_level']) ? (int) $validated['battery_level'] : null,
            address: $validated['location_address'] ?? null,
            notes: $validated['notes'] ?? null,
            offlineSyncId: $validated['offline_sync_id'] ?? null,
            recordedAt: $validated['recorded_at'] ?? null
        );

        return $this->successResponse([
            'log_id' => $log->id,
            'event_type' => $log->event_type,
            'latitude' => $log->latitude,
            'longitude' => $log->longitude,
            'recorded_at' => $log->recorded_at->toDateTimeString(),
        ], 'Location ping recorded successfully');
    }

    /**
     * Process offline batch location queue synchronization.
     */
    public function syncBatchPings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pings' => 'required|array|min:1',
            'pings.*.latitude' => 'required|numeric|between:-90,90',
            'pings.*.longitude' => 'required|numeric|between:-180,180',
            'pings.*.offline_sync_id' => 'nullable|string|max:100',
            'pings.*.event_type' => 'nullable|string',
            'pings.*.accuracy' => 'nullable|numeric',
            'pings.*.speed' => 'nullable|numeric',
            'pings.*.battery_level' => 'nullable|integer',
            'pings.*.location_address' => 'nullable|string',
            'pings.*.notes' => 'nullable|string',
            'pings.*.recorded_at' => 'nullable|date',
        ]);

        $result = $this->locationTrackingService->syncBatchPings(
            user: $request->user(),
            pings: $validated['pings']
        );

        return $this->successResponse([
            'synced_count' => $result['synced_count'],
            'ignored_count' => $result['ignored_count'],
            'total_received' => count($validated['pings']),
        ], 'Batch location pings synchronized successfully');
    }

    /**
     * Get staff's real-time duty & location status.
     */
    public function status(Request $request): JsonResponse
    {
        $statusInfo = $this->locationTrackingService->getStaffStatus($request->user());
        $today = now()->toDateString();
        $verifiedDistance = $this->locationTrackingService->calculateVerifiedDistanceKm($request->user()->id, $today);

        return $this->successResponse([
            'user_id' => $request->user()->id,
            'user_name' => $request->user()->name,
            'duty_status' => $statusInfo['status'],
            'status_label' => $statusInfo['status_label'],
            'badge_class' => $statusInfo['badge_class'],
            'today_date' => $today,
            'today_verified_distance_km' => $verifiedDistance,
            'battery_level' => $statusInfo['battery_level'],
            'last_ping_time' => $statusInfo['last_ping'] ? $statusInfo['last_ping']->recorded_at->toDateTimeString() : null,
            'last_latitude' => $statusInfo['last_ping']?->latitude,
            'last_longitude' => $statusInfo['last_ping']?->longitude,
        ], 'Staff location status retrieved');
    }
}
