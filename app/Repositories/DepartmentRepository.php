<?php

namespace App\Repositories;

use App\Models\Department;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class DepartmentRepository implements DepartmentRepositoryInterface
{
    /**
     * Apply strict multi-company data isolation based on authenticated user context.
     */
    protected function applyDepartmentScope($query)
    {
        $user = auth()->user();

        if ($user && !$user->isSuperAdmin()) {
            if ($user->company_id) {
                $query->where('company_id', $user->company_id);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        return $query;
    }

    public function getPaginatedDepartments(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = Department::with('company')->withCount(['designations', 'employees']);
        $query = $this->applyDepartmentScope($query);

        $user = auth()->user();
        if ($user && $user->isSuperAdmin() && !empty($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }

        if (!empty($filters['status']) && $filters['status'] === 'trashed') {
            $query->onlyTrashed();
        } elseif (!empty($filters['status']) && in_array($filters['status'], ['active', 'inactive'])) {
            $query->where('is_active', $filters['status'] === 'active');
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        return $query->latest('id')->paginate($perPage)->withQueryString();
    }

    public function findById(int $id): ?Department
    {
        $query = Department::with(['company', 'designations', 'creator', 'updater']);
        return $this->applyDepartmentScope($query)->find($id);
    }

    public function findWithTrashed(int $id): ?Department
    {
        $query = Department::withTrashed()->with(['company', 'designations', 'creator', 'updater']);
        return $this->applyDepartmentScope($query)->find($id);
    }

    public function create(array $data): Department
    {
        return Department::create($data);
    }

    public function update(Department $department, array $data): Department
    {
        $department->update($data);
        return $department->fresh();
    }

    public function delete(Department $department, int $userId): bool
    {
        $department->deleted_by = $userId;
        $department->save();
        return (bool) $department->delete();
    }

    public function restore(Department $department): bool
    {
        $department->deleted_by = null;
        $department->save();
        return (bool) $department->restore();
    }

    public function toggleStatus(Department $department, bool $isActive, int $userId): bool
    {
        $department->is_active = $isActive;
        $department->updated_by = $userId;
        return $department->save();
    }
}
