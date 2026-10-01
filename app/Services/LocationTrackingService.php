<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\FieldLocationLog;
use App\Models\User;
use Carbon\Carbon;

class LocationTrackingService
{
    /**
     * Log a single location ping or event.
     */
    public function logPing(
        User $user,
        float $latitude,
        float $longitude,
        ?string $eventType = 'location_ping',
        ?float $accuracy = null,
        ?float $speed = null,
        ?int $batteryLevel = null,
        ?string $address = null,
        ?string $notes = null,
        ?string $offlineSyncId = null,
        ?string $recordedAt = null
    ): FieldLocationLog {
        // Deduplicate using offline_sync_id if provided
        if ($offlineSyncId) {
            $existing = FieldLocationLog::where('offline_sync_id', $offlineSyncId)->first();
            if ($existing) {
                return $existing;
            }
        }

        $employee = Employee::where('user_id', $user->id)->first();
        $recordTime = $recordedAt ? Carbon::parse($recordedAt) : now();

        return FieldLocationLog::create([
            'company_id' => $user->company_id ?? 1,
            'branch_id' => $user->branch_id ?? 1,
            'user_id' => $user->id,
            'employee_id' => $employee?->id,
            'event_type' => $eventType ?? 'location_ping',
            'latitude' => $latitude,
            'longitude' => $longitude,
            'accuracy' => $accuracy,
            'speed' => $speed,
            'battery_level' => $batteryLevel,
            'location_address' => $address,
            'notes' => $notes,
            'offline_sync_id' => $offlineSyncId,
            'recorded_at' => $recordTime,
        ]);
    }

    /**
     * Process batch offline location queue sync.
     */
    public function syncBatchPings(User $user, array $pings): array
    {
        $synced = 0;
        $ignored = 0;

        foreach ($pings as $ping) {
            if (!isset($ping['latitude'], $ping['longitude'])) {
                $ignored++;
                continue;
            }

            $syncId = $ping['offline_sync_id'] ?? null;
            if ($syncId && FieldLocationLog::where('offline_sync_id', $syncId)->exists()) {
                $ignored++;
                continue;
            }

            $this->logPing(
                user: $user,
                latitude: (float) $ping['latitude'],
                longitude: (float) $ping['longitude'],
                eventType: $ping['event_type'] ?? 'location_ping',
                accuracy: isset($ping['accuracy']) ? (float) $ping['accuracy'] : null,
                speed: isset($ping['speed']) ? (float) $ping['speed'] : null,
                batteryLevel: isset($ping['battery_level']) ? (int) $ping['battery_level'] : null,
                address: $ping['location_address'] ?? null,
                notes: $ping['notes'] ?? null,
                offlineSyncId: $syncId,
                recordedAt: $ping['recorded_at'] ?? null
            );
            $synced++;
        }

        return [
            'synced_count' => $synced,
            'ignored_count' => $ignored,
        ];
    }

    /**
     * Handle Ping-In duty start.
     */
    public function pingIn(User $user, float $lat, float $lng, ?float $accuracy = null, ?string $notes = null, ?string $syncId = null): FieldLocationLog
    {
        $log = $this->logPing(
            user: $user,
            latitude: $lat,
            longitude: $lng,
            eventType: 'ping_in',
            accuracy: $accuracy,
            notes: $notes,
            offlineSyncId: $syncId
        );

        // Update attendance clock-in if employee exists and no clock-in today
        $employee = Employee::where('user_id', $user->id)->first();
        if ($employee) {
            $today = now()->toDateString();
            $time = now()->toTimeString();
            Attendance::firstOrCreate(
                ['employee_id' => $employee->id, 'attendance_date' => $today],
                [
                    'company_id' => $user->company_id ?? 1,
                    'branch_id' => $user->branch_id ?? 1,
                    'clock_in' => $time,
                    'status' => 'present',
                    'remarks' => 'Duty Ping-In via Mobile App [GPS: ' . $lat . ',' . $lng . ']',
                    'created_by' => $user->id,
                ]
            );
        }

        return $log;
    }

    /**
     * Handle Ping-Out duty end.
     */
    public function pingOut(User $user, float $lat, float $lng, ?float $accuracy = null, ?string $notes = null, ?string $syncId = null): FieldLocationLog
    {
        $log = $this->logPing(
            user: $user,
            latitude: $lat,
            longitude: $lng,
            eventType: 'ping_out',
            accuracy: $accuracy,
            notes: $notes,
            offlineSyncId: $syncId
        );

        // Update attendance clock-out if employee exists
        $employee = Employee::where('user_id', $user->id)->first();
        if ($employee) {
            $today = now()->toDateString();
            $time = now()->toTimeString();
            $attendance = Attendance::where('employee_id', $employee->id)->where('attendance_date', $today)->first();
            if ($attendance) {
                $attendance->update([
                    'clock_out' => $time,
                    'updated_by' => $user->id,
                ]);
            }
        }

        return $log;
    }

    /**
     * Get staff's real status (Online / Stale / Offline) based on latest ping.
     */
    public function getStaffStatus(User $user): array
    {
        $latest = FieldLocationLog::where('user_id', $user->id)->latest('recorded_at')->first();

        if (!$latest) {
            return [
                'status' => 'offline',
                'status_label' => 'Offline',
                'badge_class' => 'bg-secondary',
                'last_ping' => null,
                'battery_level' => null,
                'minutes_ago' => null,
            ];
        }

        $minutesAgo = (int) Carbon::parse($latest->recorded_at)->diffInMinutes(now());

        if ($latest->event_type === 'ping_out') {
            $status = 'offline';
            $statusLabel = 'Off Duty (Ping Out)';
            $badgeClass = 'bg-secondary';
        } elseif ($minutesAgo <= 15) {
            $status = 'online';
            $statusLabel = 'Online';
            $badgeClass = 'bg-success';
        } elseif ($minutesAgo <= 60) {
            $status = 'stale';
            $statusLabel = 'Stale (' . $minutesAgo . 'm ago)';
            $badgeClass = 'bg-warning text-dark';
        } else {
            $status = 'offline';
            $statusLabel = 'Offline (' . round($minutesAgo / 60, 1) . 'h ago)';
            $badgeClass = 'bg-danger';
        }

        return [
            'status' => $status,
            'status_label' => $statusLabel,
            'badge_class' => $badgeClass,
            'last_ping' => $latest,
            'battery_level' => $latest->battery_level,
            'minutes_ago' => $minutesAgo,
        ];
    }

    /**
     * Calculate verified GPS distance in KM between consecutive pings on a date using Haversine formula.
     */
    public function calculateVerifiedDistanceKm(int $userId, string $date): float
    {
        $logs = FieldLocationLog::where('user_id', $userId)
            ->whereDate('recorded_at', $date)
            ->orderBy('recorded_at', 'asc')
            ->get();

        if ($logs->count() < 2) {
            return 0.0;
        }

        $totalKm = 0.0;
        $previousLog = null;

        foreach ($logs as $log) {
            if ($previousLog) {
                // Ignore identical or zero movement
                if ($log->latitude == $previousLog->latitude && $log->longitude == $previousLog->longitude) {
                    continue;
                }

                $dist = $this->haversineGreatCircleDistance(
                    $previousLog->latitude,
                    $previousLog->longitude,
                    $log->latitude,
                    $log->longitude
                );

                // Filter out GPS teleportation noise (> 100km per single ping interval)
                if ($dist <= 100.0) {
                    $totalKm += $dist;
                }
            }
            $previousLog = $log;
        }

        return round($totalKm, 2);
    }

    /**
     * Haversine formula distance in Kilometers.
     */
    protected function haversineGreatCircleDistance(
        float $latitudeFrom,
        float $longitudeFrom,
        float $latitudeTo,
        float $longitudeTo,
        float $earthRadius = 6371.0
    ): float {
        $latFrom = deg2rad($latitudeFrom);
        $lonFrom = deg2rad($longitudeFrom);
        $latTo = deg2rad($latitudeTo);
        $lonTo = deg2rad($longitudeTo);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));

        return $angle * $earthRadius;
    }

    /**
     * Fetch ordered route logs for map playback / polyline.
     */
    public function getDailyRoute(int $userId, string $date): array
    {
        $logs = FieldLocationLog::where('user_id', $userId)
            ->whereDate('recorded_at', $date)
            ->orderBy('recorded_at', 'asc')
            ->get();

        $points = $logs->map(function ($log) {
            return [
                'id' => $log->id,
                'event_type' => $log->event_type,
                'lat' => (float) $log->latitude,
                'lng' => (float) $log->longitude,
                'accuracy' => $log->accuracy,
                'battery' => $log->battery_level,
                'time' => Carbon::parse($log->recorded_at)->format('h:i:s A'),
                'timestamp' => $log->recorded_at->toISOString(),
                'address' => $log->location_address,
                'notes' => $log->notes,
            ];
        })->toArray();

        $distanceKm = $this->calculateVerifiedDistanceKm($userId, $date);

        return [
            'user_id' => $userId,
            'date' => $date,
            'total_points' => count($points),
            'verified_distance_km' => $distanceKm,
            'points' => $points,
        ];
    }
}
