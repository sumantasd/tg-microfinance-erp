<?php

namespace App\Repositories;

use App\Models\Branch;
use App\Models\InventoryTransfer;
use App\Models\InventoryTransferItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class InventoryTransferRepository implements InventoryTransferRepositoryInterface
{
    public function getPaginatedTransfers(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = InventoryTransfer::with(['sourceBranch', 'destinationBranch', 'requester', 'approver', 'items.product']);

        if (!empty($filters['source_branch_id'])) {
            $query->where('source_branch_id', $filters['source_branch_id']);
        }

        if (!empty($filters['destination_branch_id'])) {
            $query->where('destination_branch_id', $filters['destination_branch_id']);
        }

        if (!empty($filters['branch_id'])) {
            $branchId = $filters['branch_id'];
            $query->where(function ($q) use ($branchId) {
                $q->where('source_branch_id', $branchId)
                  ->orWhere('destination_branch_id', $branchId);
            });
        }

        if (!empty($filters['company_id'])) {
            $companyId = $filters['company_id'];
            $query->where(function ($q) use ($companyId) {
                $q->where('source_company_id', $companyId)
                  ->orWhere('destination_company_id', $companyId);
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('transfer_number', 'like', "%{$search}%")
                  ->orWhere('remarks', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('id', 'desc')->paginate($perPage);
    }

    public function findById(int $id): ?InventoryTransfer
    {
        return InventoryTransfer::with([
            'sourceCompany', 'sourceBranch', 'destinationCompany', 'destinationBranch',
            'requester', 'approver', 'dispatcher', 'receiver', 'canceller', 'items.product'
        ])->find($id);
    }

    public function findByTransferNumber(string $transferNumber): ?InventoryTransfer
    {
        return InventoryTransfer::where('transfer_number', $transferNumber)->first();
    }

    public function createTransfer(array $masterData, array $itemsData): InventoryTransfer
    {
        return DB::transaction(function () use ($masterData, $itemsData) {
            $transfer = InventoryTransfer::create($masterData);
            
            $totalQty = 0;
            $totalVal = 0;
            $itemCount = 0;

            foreach ($itemsData as $item) {
                $qty = (int) $item['quantity'];
                $price = (float) $item['unit_price'];
                $val = round($qty * $price, 2);

                InventoryTransferItem::create([
                    'transfer_id' => $transfer->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $qty,
                    'unit_price' => $price,
                    'total_value' => $val,
                ]);

                $totalQty += $qty;
                $totalVal += $val;
                $itemCount++;
            }

            $transfer->update([
                'total_items' => $itemCount,
                'total_quantity' => $totalQty,
                'total_value' => $totalVal,
            ]);

            return $transfer->fresh(['items.product']);
        });
    }

    public function updateTransferStatus(InventoryTransfer $transfer, string $status, array $additionalData = []): InventoryTransfer
    {
        $additionalData['status'] = $status;
        $transfer->update($additionalData);
        return $transfer->fresh(['items.product']);
    }

    public function generateTransferNumber(int $sourceBranchId): string
    {
        return DB::transaction(function () use ($sourceBranchId) {
            $branch = Branch::findOrFail($sourceBranchId);
            $branchCode = $branch->code ?? 'BR001';
            $year = date('Y');

            $lastId = DB::table('inventory_transfers')
                ->where('source_branch_id', $sourceBranchId)
                ->lockForUpdate()
                ->max('id') ?? 0;

            $nextSeq = str_pad($lastId + 1, 5, '0', STR_PAD_LEFT);
            return "TRF-{$branchCode}-{$year}-{$nextSeq}";
        });
    }
}
