<?php

namespace App\Repositories;

use App\Models\Department;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface DepartmentRepositoryInterface
{
    public function getPaginatedDepartments(array $filters = [], int $perPage = 10): LengthAwarePaginator;

    public function findById(int $id): ?Department;

    public function findWithTrashed(int $id): ?Department;

    public function create(array $data): Department;

    public function update(Department $department, array $data): Department;

    public function delete(Department $department, int $userId): bool;

    public function restore(Department $department): bool;

    public function toggleStatus(Department $department, bool $isActive, int $userId): bool;
}
