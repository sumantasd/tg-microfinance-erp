<?php

namespace App\Repositories;

use App\Models\Attendance;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface AttendanceRepositoryInterface
{
    public function getPaginatedAttendances(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function findById(int $id): ?Attendance;

    public function markAttendance(array $data): Attendance;

    public function updateAttendance(Attendance $attendance, array $data): Attendance;
}
