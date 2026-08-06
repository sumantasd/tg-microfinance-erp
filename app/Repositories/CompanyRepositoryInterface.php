<?php

namespace App\Repositories;

use App\Models\Company;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface CompanyRepositoryInterface
{
    public function getPaginatedCompanies(array $filters = [], int $perPage = 10): LengthAwarePaginator;

    public function findById(int $id): ?Company;

    public function findWithTrashed(int $id): ?Company;

    public function create(array $data): Company;

    public function update(Company $company, array $data): Company;

    public function delete(Company $company, int $userId): bool;

    public function restore(Company $company): bool;

    public function toggleStatus(Company $company, bool $isActive, int $userId): bool;
}
