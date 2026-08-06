<?php

namespace App\Repositories;

use App\Models\Company;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CompanyRepository implements CompanyRepositoryInterface
{
    public function getPaginatedCompanies(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = Company::withCount(['branches', 'users']);

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
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('registration_number', 'like', "%{$search}%");
            });
        }

        return $query->latest('id')->paginate($perPage)->withQueryString();
    }

    public function findById(int $id): ?Company
    {
        return Company::with(['branches', 'creator', 'updater'])->find($id);
    }

    public function findWithTrashed(int $id): ?Company
    {
        return Company::withTrashed()->with(['branches', 'creator', 'updater'])->find($id);
    }

    public function create(array $data): Company
    {
        return Company::create($data);
    }

    public function update(Company $company, array $data): Company
    {
        $company->update($data);
        return $company->fresh();
    }

    public function delete(Company $company, int $userId): bool
    {
        $company->deleted_by = $userId;
        $company->save();
        return (bool) $company->delete();
    }

    public function restore(Company $company): bool
    {
        $company->deleted_by = null;
        $company->save();
        return (bool) $company->restore();
    }

    public function toggleStatus(Company $company, bool $isActive, int $userId): bool
    {
        $company->is_active = $isActive;
        $company->updated_by = $userId;
        return $company->save();
    }
}
