<?php

namespace App\Repositories;

use App\Models\Employee;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface EmployeeRepositoryInterface
{
    public function getPaginatedEmployees(array $filters = [], int $perPage = 10): LengthAwarePaginator;

    public function findById(int $id): ?Employee;

    public function findByUuid(string $uuid): ?Employee;

    public function findWithTrashed(int $id): ?Employee;

    public function create(array $data): Employee;

    public function update(Employee $employee, array $data): Employee;

    public function delete(Employee $employee, int $userId): bool;

    public function restore(Employee $employee): bool;

    public function toggleStatus(Employee $employee, string $status, int $userId): bool;
}
