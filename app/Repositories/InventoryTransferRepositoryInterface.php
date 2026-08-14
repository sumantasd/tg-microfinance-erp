<?php

namespace App\Repositories;

use App\Models\InventoryTransfer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface InventoryTransferRepositoryInterface
{
    public function getPaginatedTransfers(array $filters = [], int $perPage = 10): LengthAwarePaginator;

    public function findById(int $id): ?InventoryTransfer;

    public function findByTransferNumber(string $transferNumber): ?InventoryTransfer;

    public function createTransfer(array $masterData, array $itemsData): InventoryTransfer;

    public function updateTransferStatus(InventoryTransfer $transfer, string $status, array $additionalData = []): InventoryTransfer;

    public function generateTransferNumber(int $sourceBranchId): string;
}
