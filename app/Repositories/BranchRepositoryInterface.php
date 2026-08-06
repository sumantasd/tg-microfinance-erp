<?php

namespace App\Repositories;

use App\Models\Branch;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface BranchRepositoryInterface
{
    public function getPaginatedBranches(array $filters = [], int $perPage = 10): LengthAwarePaginator;

    public function findById(int $id): ?Branch;

    public function findWithTrashed(int $id): ?Branch;

    public function create(array $data): Branch;

    public function update(Branch $branch, array $data): Branch;

    public function delete(Branch $branch, int $userId): bool;

    public function restore(Branch $branch): bool;

    public function toggleStatus(Branch $branch, bool $isActive, int $userId): bool;
}
