<?php

namespace App\Repositories;

use App\Models\Attendance;
use App\Models\Branch;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AttendanceRepository implements AttendanceRepositoryInterface
{
    /**
     * Apply strict multi-company and branch-level data isolation based on role and context.
     */
    protected function applyAttendanceScope($query)
    {
        $user = auth()->user();

        if ($user && !$user->isSuperAdmin()) {
            if ($user->hasRole('Branch Manager')) {
                $assignedBranchId = $user->branch_id;
                $userId = $user->id;

                if ($assignedBranchId) {
                    $query->where('branch_id', $assignedBranchId);
                } else {
                    $managedBranchIds = Branch::where('manager_id', $userId)->pluck('id')->toArray();
                    if (!empty($managedBranchIds)) {
                        $query->whereIn('branch_id', $managedBranchIds);
                    } else {
                        $query->whereRaw('1 = 0');
                    }
                }
            } elseif ($user->hasRole('Company Admin') || $user->company_id) {
                if ($user->company_id) {
                    $query->where('company_id', $user->company_id);
                } else {
                    $query->whereRaw('1 = 0');
                }
            } else {
                if ($user->branch_id) {
                    $query->where('branch_id', $user->branch_id);
                } elseif ($user->company_id) {
                    $query->where('company_id', $user->company_id);
                } else {
                    $query->whereRaw('1 = 0');
                }
            }
        }

        return $query;
    }

    public function getPaginatedAttendances(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Attendance::with(['company', 'branch', 'employee']);
        $query = $this->applyAttendanceScope($query);

        $user = auth()->user();
        if ($user && $user->isSuperAdmin()) {
            if (!empty($filters['company_id'])) {
                $query->where('company_id', $filters['company_id']);
            }
            if (!empty($filters['branch_id'])) {
                $query->where('branch_id', $filters['branch_id']);
            }
        }

        if (!empty($filters['date'])) {
            $query->whereDate('attendance_date', $filters['date']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('employee', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('employee_code', 'like', "%{$search}%");
            });
        }

        return $query->latest('attendance_date')->paginate($perPage)->withQueryString();
    }

    public function findById(int $id): ?Attendance
    {
        $query = Attendance::with(['company', 'branch', 'employee', 'creator']);
        return $this->applyAttendanceScope($query)->find($id);
    }

    public function markAttendance(array $data): Attendance
    {
        return Attendance::updateOrCreate(
            [
                'employee_id' => $data['employee_id'],
                'attendance_date' => $data['attendance_date'],
            ],
            $data
        );
    }

    public function updateAttendance(Attendance $attendance, array $data): Attendance
    {
        $attendance->update($data);
        return $attendance->fresh();
    }
}
