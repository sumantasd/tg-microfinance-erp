<?php

namespace App\Repositories;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ProductRepository implements ProductRepositoryInterface
{
    public function getPaginatedProducts(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = Product::with(['company']);

        if (!empty($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }

        if (!empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('brand', 'like', "%{$search}%")
                  ->orWhere('model_number', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('id', 'desc')->paginate($perPage);
    }

    public function findById(int $id): ?Product
    {
        return Product::with(['company', 'stocks.branch'])->find($id);
    }

    public function findBySku(string $sku): ?Product
    {
        return Product::where('sku', $sku)->first();
    }

    public function createProduct(array $data): Product
    {
        return Product::create($data);
    }

    public function updateProduct(Product $product, array $data): Product
    {
        $product->update($data);
        return $product->fresh(['company']);
    }

    public function deleteProduct(Product $product): bool
    {
        return (bool) $product->delete();
    }

    public function generateSku(int $companyId): string
    {
        return DB::transaction(function () use ($companyId) {
            $lastId = DB::table('products')
                ->where('company_id', $companyId)
                ->lockForUpdate()
                ->max('id') ?? 0;

            $nextSeq = str_pad($lastId + 1, 5, '0', STR_PAD_LEFT);
            return "PRD-{$nextSeq}";
        });
    }
}
