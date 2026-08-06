<?php

namespace App\Repositories;

use App\Models\Branch;
use App\Models\Leave;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class LeaveRepository implements LeaveRepositoryInterface
{
    /**
     * Apply strict multi-company and branch-level data isolation based on role and context.
     */
    protected function applyLeaveScope($query)
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

    public function getPaginatedLeaves(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Leave::with(['company', 'branch', 'employee', 'leaveType', 'approver']);
        $query = $this->applyLeaveScope($query);

        $user = auth()->user();
        if ($user && $user->isSuperAdmin()) {
            if (!empty($filters['company_id'])) {
                $query->where('company_id', $filters['company_id']);
            }
            if (!empty($filters['branch_id'])) {
                $query->where('branch_id', $filters['branch_id']);
            }
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['leave_type_id'])) {
            $query->where('leave_type_id', $filters['leave_type_id']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('employee', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('employee_code', 'like', "%{$search}%");
            });
        }

        return $query->latest('id')->paginate($perPage)->withQueryString();
    }

    public function findById(int $id): ?Leave
    {
        $query = Leave::with(['company', 'branch', 'employee', 'leaveType', 'approver']);
        return $this->applyLeaveScope($query)->find($id);
    }

    public function createLeave(array $data): Leave
    {
        return Leave::create($data);
    }

    public function updateStatus(Leave $leave, string $status, ?int $approvedBy = null, ?string $rejectionReason = null): bool
    {
        $leave->status = $status;
        if ($approvedBy) {
            $leave->approved_by = $approvedBy;
            $leave->approved_at = now();
        }
        if ($rejectionReason) {
            $leave->rejection_reason = $rejectionReason;
        }

        return $leave->save();
    }
}
