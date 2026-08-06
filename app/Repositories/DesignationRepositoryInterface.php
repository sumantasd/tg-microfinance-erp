<?php

namespace App\Repositories;

use App\Models\Designation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface DesignationRepositoryInterface
{
    public function getPaginatedDesignations(array $filters = [], int $perPage = 10): LengthAwarePaginator;

    public function findById(int $id): ?Designation;

    public function findWithTrashed(int $id): ?Designation;

    public function create(array $data): Designation;

    public function update(Designation $designation, array $data): Designation;

    public function delete(Designation $designation, int $userId): bool;

    public function restore(Designation $designation): bool;

    public function toggleStatus(Designation $designation, bool $isActive, int $userId): bool;
}
