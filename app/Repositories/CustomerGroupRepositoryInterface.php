<?php

namespace App\Repositories;

use App\Models\CustomerGroup;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface CustomerGroupRepositoryInterface
{
    public function getPaginatedGroups(array $filters = [], int $perPage = 15): LengthAwarePaginator;
    public function getAllActiveGroups(array $filters = []): Collection;
    public function findById(int $id): ?CustomerGroup;
    public function createGroup(array $data): CustomerGroup;
    public function updateGroup(CustomerGroup $group, array $data): CustomerGroup;
    public function deleteGroup(CustomerGroup $group): bool;
    public function generateGroupCode(int $branchId): string;
}
