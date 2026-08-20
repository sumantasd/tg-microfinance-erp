<?php

namespace App\Repositories;

use App\Models\Supplier;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface SupplierRepositoryInterface
{
    public function getPaginatedSuppliers(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function getAllActiveSuppliers(int $companyId): Collection;

    public function findById(int $id): ?Supplier;

    public function createSupplier(array $data): Supplier;

    public function updateSupplier(Supplier $supplier, array $data): Supplier;

    public function deleteSupplier(Supplier $supplier): bool;

    public function generateSupplierCode(int $companyId): string;
}
