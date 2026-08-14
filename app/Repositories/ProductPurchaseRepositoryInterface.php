<?php

namespace App\Repositories;

use App\Models\ProductPurchase;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ProductPurchaseRepositoryInterface
{
    public function getPaginatedPurchases(array $filters = [], int $perPage = 10): LengthAwarePaginator;

    public function findById(int $id): ?ProductPurchase;

    public function findByPurchaseNumber(string $purchaseNumber): ?ProductPurchase;

    public function createPurchase(array $masterData, array $itemsData): ProductPurchase;

    public function updatePurchase(ProductPurchase $purchase, array $masterData, array $itemsData): ProductPurchase;

    public function updateStatus(ProductPurchase $purchase, string $status, array $additionalData = []): ProductPurchase;

    public function generatePurchaseNumber(int $branchId): string;
}
