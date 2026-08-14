<?php

namespace App\Repositories;

use App\Models\InventoryStock;
use App\Models\InventoryStockMovement;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface InventoryRepositoryInterface
{
    public function getPaginatedStock(array $filters = [], int $perPage = 10): LengthAwarePaginator;

    public function getPaginatedMovements(array $filters = [], int $perPage = 10): LengthAwarePaginator;

    public function getStock(int $branchId, int $productId): ?InventoryStock;

    public function getOrInitializeStock(int $companyId, int $branchId, int $productId): InventoryStock;

    public function recordStockMovement(array $data): InventoryStockMovement;

    public function generateMovementCode(int $branchId): string;
}
