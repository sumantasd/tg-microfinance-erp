<?php

namespace App\Repositories;

use App\Models\Designation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class DesignationRepository implements DesignationRepositoryInterface
{
    /**
     * Apply strict multi-company data isolation based on authenticated user context.
     */
    protected function applyDesignationScope($query)
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

    public function getPaginatedDesignations(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = Designation::with(['company', 'department'])->withCount('employees');
        $query = $this->applyDesignationScope($query);

        $user = auth()->user();
        if ($user && $user->isSuperAdmin() && !empty($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }

        if (!empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }

        if (!empty($filters['status']) && $filters['status'] === 'trashed') {
            $query->onlyTrashed();
        } elseif (!empty($filters['status']) && in_array($filters['status'], ['active', 'inactive'])) {
            $query->where('is_active', $filters['status'] === 'active');
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        return $query->latest('id')->paginate($perPage)->withQueryString();
    }

    public function findById(int $id): ?Designation
    {
        $query = Designation::with(['company', 'department', 'employees', 'creator', 'updater']);
        return $this->applyDesignationScope($query)->find($id);
    }

    public function findWithTrashed(int $id): ?Designation
    {
        $query = Designation::withTrashed()->with(['company', 'department', 'employees', 'creator', 'updater']);
        return $this->applyDesignationScope($query)->find($id);
    }

    public function create(array $data): Designation
    {
        return Designation::create($data);
    }

    public function update(Designation $designation, array $data): Designation
    {
        $designation->update($data);
        return $designation->fresh();
    }

    public function delete(Designation $designation, int $userId): bool
    {
        $designation->deleted_by = $userId;
        $designation->save();
        return (bool) $designation->delete();
    }

    public function restore(Designation $designation): bool
    {
        $designation->deleted_by = null;
        $designation->save();
        return (bool) $designation->restore();
    }

    public function toggleStatus(Designation $designation, bool $isActive, int $userId): bool
    {
        $designation->is_active = $isActive;
        $designation->updated_by = $userId;
        return $designation->save();
    }
}
