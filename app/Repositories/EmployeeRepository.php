<?php

namespace App\Repositories;

use App\Models\Branch;
use App\Models\Employee;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EmployeeRepository implements EmployeeRepositoryInterface
{
    /**
     * Apply strict multi-company and branch-level data isolation based on role and context.
     */
    protected function applyEmployeeScope($query)
    {
        $user = auth()->user();

        if ($user && !$user->isSuperAdmin()) {
            if ($user->hasRole('Branch Manager')) {
                // Branch Manager: Query ONLY employees assigned to their branch
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
                // Company Admin: Query ONLY employees belonging to their company
                if ($user->company_id) {
                    $query->where('company_id', $user->company_id);
                } else {
                    $query->whereRaw('1 = 0');
                }
            } else {
                // Fallback for other restricted roles
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

    public function getPaginatedEmployees(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = Employee::with(['company', 'branch', 'user', 'department', 'designation']);
        $query = $this->applyEmployeeScope($query);

        $user = auth()->user();
        if ($user && $user->isSuperAdmin()) {
            if (!empty($filters['company_id'])) {
                $query->where('company_id', $filters['company_id']);
            }
            if (!empty($filters['branch_id'])) {
                $query->where('branch_id', $filters['branch_id']);
            }
        }

        if (!empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }

        if (!empty($filters['designation_id'])) {
            $query->where('designation_id', $filters['designation_id']);
        }

        if (!empty($filters['status']) && $filters['status'] === 'trashed') {
            $query->onlyTrashed();
        } elseif (!empty($filters['status']) && in_array($filters['status'], ['active', 'resigned', 'terminated', 'on_leave', 'suspended'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('employee_code', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        return $query->latest('id')->paginate($perPage)->withQueryString();
    }

    public function findById(int $id): ?Employee
    {
        $query = Employee::with(['company', 'branch', 'user', 'department', 'designation', 'creator', 'updater']);
        return $this->applyEmployeeScope($query)->find($id);
    }

    public function findByUuid(string $uuid): ?Employee
    {
        $query = Employee::with(['company', 'branch', 'user', 'department', 'designation', 'creator', 'updater'])->where('uuid', $uuid);
        return $this->applyEmployeeScope($query)->first();
    }

    public function findWithTrashed(int $id): ?Employee
    {
        $query = Employee::withTrashed()->with(['company', 'branch', 'user', 'department', 'designation', 'creator', 'updater']);
        return $this->applyEmployeeScope($query)->find($id);
    }

    public function create(array $data): Employee
    {
        return Employee::create($data);
    }

    public function update(Employee $employee, array $data): Employee
    {
        $employee->update($data);
        return $employee->fresh();
    }

    public function delete(Employee $employee, int $userId): bool
    {
        $employee->deleted_by = $userId;
        $employee->save();
        return (bool) $employee->delete();
    }

    public function restore(Employee $employee): bool
    {
        $employee->deleted_by = null;
        $employee->save();
        return (bool) $employee->restore();
    }

    public function toggleStatus(Employee $employee, string $status, int $userId): bool
    {
        $employee->status = $status;
        $employee->updated_by = $userId;
        return $employee->save();
    }
}
