<?php

namespace App\Repositories;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ProductRepositoryInterface
{
    public function getPaginatedProducts(array $filters = [], int $perPage = 10): LengthAwarePaginator;

    public function findById(int $id): ?Product;

    public function findBySku(string $sku): ?Product;

    public function createProduct(array $data): Product;

    public function updateProduct(Product $product, array $data): Product;

    public function deleteProduct(Product $product): bool;

    public function generateSku(int $companyId): string;
}
