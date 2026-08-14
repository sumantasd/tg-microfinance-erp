<?php

namespace App\Repositories;

use App\Models\Branch;
use App\Models\ProductPurchase;
use App\Models\ProductPurchaseItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ProductPurchaseRepository implements ProductPurchaseRepositoryInterface
{
    public function getPaginatedPurchases(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = ProductPurchase::with(['company', 'branch', 'creator', 'receiver', 'items.product']);

        if (!empty($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }

        if (!empty($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }

        if (!empty($filters['purchase_status'])) {
            $query->where('purchase_status', $filters['purchase_status']);
        }

        if (!empty($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }

        if (!empty($filters['supplier'])) {
            $query->where('supplier_name', 'like', "%{$filters['supplier']}%");
        }

        if (!empty($filters['product_id'])) {
            $query->whereHas('items', function ($q) use ($filters) {
                $q->where('product_id', $filters['product_id']);
            });
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('purchase_number', 'like', "%{$search}%")
                  ->orWhere('supplier_name', 'like', "%{$search}%")
                  ->orWhere('supplier_invoice_number', 'like', "%{$search}%")
                  ->orWhere('remarks', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->whereBetween('purchase_date', [$filters['start_date'], $filters['end_date']]);
        }

        return $query->orderBy('id', 'desc')->paginate($perPage);
    }

    public function findById(int $id): ?ProductPurchase
    {
        return ProductPurchase::with(['company', 'branch', 'creator', 'updater', 'receiver', 'items.product'])->find($id);
    }

    public function findByPurchaseNumber(string $purchaseNumber): ?ProductPurchase
    {
        return ProductPurchase::where('purchase_number', $purchaseNumber)->first();
    }

    public function createPurchase(array $masterData, array $itemsData): ProductPurchase
    {
        return DB::transaction(function () use ($masterData, $itemsData) {
            $purchase = ProductPurchase::create($masterData);

            foreach ($itemsData as $item) {
                $item['purchase_id'] = $purchase->id;
                ProductPurchaseItem::create($item);
            }

            return $purchase->fresh(['items.product']);
        });
    }

    public function updatePurchase(ProductPurchase $purchase, array $masterData, array $itemsData): ProductPurchase
    {
        return DB::transaction(function () use ($purchase, $masterData, $itemsData) {
            $purchase->update($masterData);
            $purchase->items()->delete();

            foreach ($itemsData as $item) {
                $item['purchase_id'] = $purchase->id;
                ProductPurchaseItem::create($item);
            }

            return $purchase->fresh(['items.product']);
        });
    }

    public function updateStatus(ProductPurchase $purchase, string $status, array $additionalData = []): ProductPurchase
    {
        $additionalData['purchase_status'] = $status;
        $purchase->update($additionalData);
        return $purchase->fresh(['items.product']);
    }

    public function generatePurchaseNumber(int $branchId): string
    {
        return DB::transaction(function () use ($branchId) {
            $branch = Branch::findOrFail($branchId);
            $branchCode = $branch->code ?? 'BR001';
            $year = date('Y');

            $lastId = DB::table('product_purchases')
                ->where('branch_id', $branchId)
                ->lockForUpdate()
                ->max('id') ?? 0;

            $nextSeq = str_pad($lastId + 1, 5, '0', STR_PAD_LEFT);
            return "PUR-{$branchCode}-{$year}-{$nextSeq}";
        });
    }
}
