<?php

namespace App\Repositories;

use App\Models\Branch;
use App\Models\InventoryStock;
use App\Models\InventoryStockMovement;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class InventoryRepository implements InventoryRepositoryInterface
{
    public function getPaginatedStock(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = InventoryStock::with(['company', 'branch', 'product']);

        if (!empty($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }

        if (!empty($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }

        if (!empty($filters['product_id'])) {
            $query->where('product_id', $filters['product_id']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('brand', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('id', 'desc')->paginate($perPage);
    }

    public function getPaginatedMovements(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = InventoryStockMovement::with(['company', 'branch', 'product', 'creator']);

        if (!empty($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }

        if (!empty($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }

        if (!empty($filters['product_id'])) {
            $query->where('product_id', $filters['product_id']);
        }

        if (!empty($filters['movement_type'])) {
            $query->where('movement_type', $filters['movement_type']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('movement_code', 'like', "%{$search}%")
                  ->orWhere('remarks', 'like', "%{$search}%")
                  ->orWhereHas('product', function ($p) use ($search) {
                      $p->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%");
                  });
            });
        }

        return $query->orderBy('id', 'desc')->paginate($perPage);
    }

    public function getStock(int $branchId, int $productId): ?InventoryStock
    {
        return InventoryStock::with(['product', 'branch'])
            ->where('branch_id', $branchId)
            ->where('product_id', $productId)
            ->first();
    }

    public function getOrInitializeStock(int $companyId, int $branchId, int $productId): InventoryStock
    {
        return InventoryStock::firstOrCreate(
            ['branch_id' => $branchId, 'product_id' => $productId],
            ['company_id' => $companyId, 'current_stock' => 0, 'reserved_stock' => 0, 'reorder_level' => 5]
        );
    }

    public function recordStockMovement(array $data): InventoryStockMovement
    {
        return InventoryStockMovement::create($data);
    }

    public function generateMovementCode(int $branchId): string
    {
        return DB::transaction(function () use ($branchId) {
            $branch = Branch::findOrFail($branchId);
            $branchCode = $branch->code ?? 'BR001';
            $year = date('Y');

            $lastId = DB::table('inventory_stock_movements')
                ->where('branch_id', $branchId)
                ->lockForUpdate()
                ->max('id') ?? 0;

            $nextSeq = str_pad($lastId + 1, 5, '0', STR_PAD_LEFT);
            return "STK-{$branchCode}-{$year}-{$nextSeq}";
        });
    }
}
