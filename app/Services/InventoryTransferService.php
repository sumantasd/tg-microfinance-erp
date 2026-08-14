<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\InventoryTransfer;
use App\Models\Product;
use App\Repositories\InventoryRepositoryInterface;
use App\Repositories\InventoryTransferRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryTransferService
{
    public function __construct(
        protected InventoryTransferRepositoryInterface $transferRepository,
        protected InventoryRepositoryInterface $inventoryRepository,
        protected ActivityLogService $activityLogService
    ) {}

    public function getPaginatedTransfers(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $user = Auth::user();
        if ($user && !$user->isSuperAdmin()) {
            $filters['company_id'] = $user->company_id;
            if ($user->branch_id && !$user->isCompanyAdmin() && empty($filters['branch_id'])) {
                $filters['branch_id'] = $user->branch_id;
            }
        }

        return $this->transferRepository->getPaginatedTransfers($filters, $perPage);
    }

    public function getTransferById(int $id): ?InventoryTransfer
    {
        return $this->transferRepository->findById($id);
    }

    public function createTransfer(array $data, array $items): InventoryTransfer
    {
        if ($data['source_branch_id'] == $data['destination_branch_id']) {
            throw ValidationException::withMessages([
                'destination_branch_id' => 'Source and destination branches must be different.',
            ]);
        }

        if (empty($items)) {
            throw ValidationException::withMessages([
                'items' => 'At least one product item is required for a transfer.',
            ]);
        }

        return DB::transaction(function () use ($data, $items) {
            $sourceBranch = Branch::findOrFail($data['source_branch_id']);
            $destBranch = Branch::findOrFail($data['destination_branch_id']);

            $user = Auth::user();
            if ($user && !$user->isSuperAdmin()) {
                if ($user->company_id && $user->company_id !== $sourceBranch->company_id) {
                    throw ValidationException::withMessages([
                        'source_branch_id' => 'Source branch does not belong to your company.',
                    ]);
                }
            }

            $transferNumber = $this->transferRepository->generateTransferNumber($sourceBranch->id);

            $masterData = [
                'transfer_number' => $transferNumber,
                'source_company_id' => $sourceBranch->company_id,
                'source_branch_id' => $sourceBranch->id,
                'destination_company_id' => $destBranch->company_id,
                'destination_branch_id' => $destBranch->id,
                'status' => 'draft',
                'remarks' => $data['remarks'] ?? null,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ];

            // Build item records with unit prices from Product catalog
            $itemsData = [];
            foreach ($items as $item) {
                $product = Product::findOrFail($item['product_id']);
                $qty = (int) $item['quantity'];
                if ($qty <= 0) {
                    throw ValidationException::withMessages(['items' => "Quantity for product '{$product->name}' must be greater than zero."]);
                }

                $unitPrice = isset($item['unit_price']) ? (float) $item['unit_price'] : (float) $product->unit_price;

                $itemsData[] = [
                    'product_id' => $product->id,
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                ];
            }

            $transfer = $this->transferRepository->createTransfer($masterData, $itemsData);
            $this->activityLogService->log('transfer_created', $transfer);

            return $transfer;
        });
    }

    public function requestTransfer(InventoryTransfer $transfer): InventoryTransfer
    {
        if ($transfer->status !== 'draft') {
            throw ValidationException::withMessages(['status' => 'Only draft transfers can be requested.']);
        }

        return DB::transaction(function () use ($transfer) {
            $updated = $this->transferRepository->updateTransferStatus($transfer, 'requested', [
                'requested_by' => Auth::id(),
                'requested_at' => now(),
                'updated_by' => Auth::id(),
            ]);

            $this->activityLogService->log('transfer_requested', $updated);
            return $updated;
        });
    }

    public function approveTransfer(InventoryTransfer $transfer): InventoryTransfer
    {
        if (!in_array($transfer->status, ['requested', 'draft'])) {
            throw ValidationException::withMessages(['status' => 'Transfer must be requested or draft to approve.']);
        }

        return DB::transaction(function () use ($transfer) {
            $updated = $this->transferRepository->updateTransferStatus($transfer, 'approved', [
                'approved_by' => Auth::id(),
                'approved_at' => now(),
                'updated_by' => Auth::id(),
            ]);

            $this->activityLogService->log('transfer_approved', $updated);
            return $updated;
        });
    }

    public function rejectTransfer(InventoryTransfer $transfer, string $reason): InventoryTransfer
    {
        if (!in_array($transfer->status, ['draft', 'requested', 'approved'])) {
            throw ValidationException::withMessages(['status' => 'Cannot reject transfer in current status.']);
        }

        return DB::transaction(function () use ($transfer, $reason) {
            $updated = $this->transferRepository->updateTransferStatus($transfer, 'rejected', [
                'rejection_reason' => $reason,
                'updated_by' => Auth::id(),
            ]);

            $this->activityLogService->log('transfer_rejected', $updated);
            return $updated;
        });
    }

    /**
     * Dispatch Transfer (Status: approved -> in_transit)
     * Atomically deducts stock from Source Branch and records TRANSFER_OUT movement.
     */
    public function dispatchTransfer(InventoryTransfer $transfer): InventoryTransfer
    {
        if ($transfer->status !== 'approved') {
            throw ValidationException::withMessages(['status' => 'Transfer must be approved before dispatching.']);
        }

        return DB::transaction(function () use ($transfer) {
            // Lock and deduct stock at Source Branch for each transfer item
            foreach ($transfer->items as $item) {
                $stock = DB::table('inventory_stocks')
                    ->where('branch_id', $transfer->source_branch_id)
                    ->where('product_id', $item->product_id)
                    ->lockForUpdate()
                    ->first();

                $currentStock = $stock ? $stock->current_stock : 0;

                if ($currentStock < $item->quantity) {
                    $product = Product::find($item->product_id);
                    throw ValidationException::withMessages([
                        'stock' => "Insufficient stock at source branch for product '{$product->name}'. Available: {$currentStock}, Required: {$item->quantity}.",
                    ]);
                }

                $stockBefore = $currentStock;
                $stockAfter = $currentStock - $item->quantity;

                DB::table('inventory_stocks')
                    ->where('id', $stock->id)
                    ->update([
                        'current_stock' => $stockAfter,
                        'updated_at' => now(),
                    ]);

                // Create TRANSFER_OUT movement for Source Branch
                $movementCode = $this->inventoryRepository->generateMovementCode($transfer->source_branch_id);
                $this->inventoryRepository->recordStockMovement([
                    'company_id' => $transfer->source_company_id,
                    'branch_id' => $transfer->source_branch_id,
                    'product_id' => $item->product_id,
                    'movement_code' => $movementCode,
                    'movement_type' => 'transfer_out',
                    'quantity' => -$item->quantity,
                    'unit_price' => $item->unit_price,
                    'total_value' => $item->total_value,
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockAfter,
                    'reference_type' => 'inventory_transfer',
                    'reference_id' => $transfer->id,
                    'remarks' => "Dispatched transfer {$transfer->transfer_number} to Destination Branch.",
                    'created_by' => Auth::id(),
                ]);
            }

            $updated = $this->transferRepository->updateTransferStatus($transfer, 'in_transit', [
                'dispatched_by' => Auth::id(),
                'dispatched_at' => now(),
                'updated_by' => Auth::id(),
            ]);

            $this->activityLogService->log('transfer_dispatched', $updated);
            return $updated;
        });
    }

    /**
     * Receive Transfer (Status: in_transit -> received)
     * Atomically increases stock at Destination Branch and records TRANSFER_IN movement.
     */
    public function receiveTransfer(InventoryTransfer $transfer): InventoryTransfer
    {
        if ($transfer->status !== 'in_transit') {
            throw ValidationException::withMessages(['status' => 'Transfer must be in transit before receiving.']);
        }

        return DB::transaction(function () use ($transfer) {
            foreach ($transfer->items as $item) {
                $stock = DB::table('inventory_stocks')
                    ->where('branch_id', $transfer->destination_branch_id)
                    ->where('product_id', $item->product_id)
                    ->lockForUpdate()
                    ->first();

                $stockBefore = $stock ? $stock->current_stock : 0;
                $stockAfter = $stockBefore + $item->quantity;

                if ($stock) {
                    DB::table('inventory_stocks')
                        ->where('id', $stock->id)
                        ->update([
                            'current_stock' => $stockAfter,
                            'last_restocked_at' => now(),
                            'updated_at' => now(),
                        ]);
                } else {
                    DB::table('inventory_stocks')->insert([
                        'company_id' => $transfer->destination_company_id,
                        'branch_id' => $transfer->destination_branch_id,
                        'product_id' => $item->product_id,
                        'current_stock' => $stockAfter,
                        'reserved_stock' => 0,
                        'reorder_level' => 5,
                        'last_restocked_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                // Create TRANSFER_IN movement for Destination Branch
                $movementCode = $this->inventoryRepository->generateMovementCode($transfer->destination_branch_id);
                $this->inventoryRepository->recordStockMovement([
                    'company_id' => $transfer->destination_company_id,
                    'branch_id' => $transfer->destination_branch_id,
                    'product_id' => $item->product_id,
                    'movement_code' => $movementCode,
                    'movement_type' => 'transfer_in',
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'total_value' => $item->total_value,
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockAfter,
                    'reference_type' => 'inventory_transfer',
                    'reference_id' => $transfer->id,
                    'remarks' => "Received transfer {$transfer->transfer_number} from Source Branch.",
                    'created_by' => Auth::id(),
                ]);
            }

            $updated = $this->transferRepository->updateTransferStatus($transfer, 'received', [
                'received_by' => Auth::id(),
                'received_at' => now(),
                'updated_by' => Auth::id(),
            ]);

            $this->activityLogService->log('transfer_received', $updated);
            return $updated;
        });
    }

    public function cancelTransfer(InventoryTransfer $transfer): InventoryTransfer
    {
        if (in_array($transfer->status, ['in_transit', 'received'])) {
            throw ValidationException::withMessages(['status' => 'Cannot cancel transfer once dispatched or received.']);
        }

        return DB::transaction(function () use ($transfer) {
            $updated = $this->transferRepository->updateTransferStatus($transfer, 'cancelled', [
                'cancelled_by' => Auth::id(),
                'cancelled_at' => now(),
                'updated_by' => Auth::id(),
            ]);

            $this->activityLogService->log('transfer_cancelled', $updated);
            return $updated;
        });
    }
}
