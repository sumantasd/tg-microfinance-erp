<?php

namespace App\Repositories;

use App\Models\Branch;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class BranchRepository implements BranchRepositoryInterface
{
    /**
     * Apply strict multi-tenant and branch-level data isolation based on role and context.
     */
    protected function applyBranchScope($query)
    {
        $user = auth()->user();

        if ($user && !$user->isSuperAdmin()) {
            if ($user->hasRole('Branch Manager')) {
                // Branch Manager: Query ONLY assigned branch or managed branch
                $assignedBranchId = $user->branch_id;
                $userId = $user->id;

                if ($assignedBranchId) {
                    $query->where('id', $assignedBranchId);
                } else {
                    $query->where('manager_id', $userId);
                }

                if (!$assignedBranchId && !Branch::where('manager_id', $userId)->exists()) {
                    $query->whereRaw('1 = 0');
                }
            } elseif ($user->hasRole('Company Admin') || $user->company_id) {
                // Company Admin: Query ONLY branches belonging to their company
                if ($user->company_id) {
                    $query->where('company_id', $user->company_id);
                } else {
                    $query->whereRaw('1 = 0');
                }
            } else {
                // Fallback for other restricted roles
                if ($user->branch_id) {
                    $query->where('id', $user->branch_id);
                } elseif ($user->company_id) {
                    $query->where('company_id', $user->company_id);
                } else {
                    $query->whereRaw('1 = 0');
                }
            }
        }

        return $query;
    }

    public function getPaginatedBranches(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = Branch::with(['company', 'manager'])->withCount('users');
        $query = $this->applyBranchScope($query);

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
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%")
                  ->orWhere('state', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        return $query->latest('id')->paginate($perPage)->withQueryString();
    }

    public function findById(int $id): ?Branch
    {
        $query = Branch::with(['company', 'manager', 'creator', 'updater']);
        return $this->applyBranchScope($query)->find($id);
    }

    public function findWithTrashed(int $id): ?Branch
    {
        $query = Branch::withTrashed()->with(['company', 'manager', 'creator', 'updater']);
        return $this->applyBranchScope($query)->find($id);
    }

    public function create(array $data): Branch
    {
        return Branch::create($data);
    }

    public function update(Branch $branch, array $data): Branch
    {
        $branch->update($data);
        return $branch->fresh();
    }

    public function delete(Branch $branch, int $userId): bool
    {
        $branch->deleted_by = $userId;
        $branch->save();
        return (bool) $branch->delete();
    }

    public function restore(Branch $branch): bool
    {
        $branch->deleted_by = null;
        $branch->save();
        return (bool) $branch->restore();
    }

    public function toggleStatus(Branch $branch, bool $isActive, int $userId): bool
    {
        $branch->is_active = $isActive;
        $branch->updated_by = $userId;
        return $branch->save();
    }
}
