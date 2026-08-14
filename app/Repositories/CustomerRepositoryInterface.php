<?php

namespace App\Repositories;

use App\Models\Customer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface CustomerRepositoryInterface
{
    public function getPaginatedCustomers(array $filters = [], int $perPage = 10): LengthAwarePaginator;

    public function findById(int $id): ?Customer;

    public function create(array $data): Customer;

    public function update(Customer $customer, array $data): Customer;

    public function delete(Customer $customer, int $userId): bool;

    public function restore(Customer $customer): bool;

    public function changeStatus(Customer $customer, string $status, int $userId): bool;

    public function generateCustomerCode(int $companyId, int $branchId): string;
}
