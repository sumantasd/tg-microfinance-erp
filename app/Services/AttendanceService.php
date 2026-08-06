<?php

namespace App\Services;

use App\Models\Attendance;
use App\Repositories\AttendanceRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

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
}
