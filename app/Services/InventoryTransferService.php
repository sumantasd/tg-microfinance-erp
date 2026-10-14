<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\InventoryTransfer;
use App\Models\InventoryTransferItem;
use App\Models\InventoryTransferCancellation;
use App\Models\InventoryTransferCancellationItem;
use App\Models\InventoryTransferReceipt;
use App\Models\InventoryTransferReceiptItem;
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

    /**
     * Approve Transfer (Ready to Ship).
     * Validates stock availability without altering physical stock.
     */
    public function approveTransfer(InventoryTransfer $transfer, ?int $userId = null): InventoryTransfer
    {
        if (!in_array($transfer->status, ['requested', 'draft', 'pending'])) {
            throw ValidationException::withMessages(['status' => 'Transfer has already been approved or processed.']);
        }

        $transfer->load('items.product');

        return DB::transaction(function () use ($transfer, $userId) {
            $actorId = $userId ?? Auth::id();

            // Validate that source branch has sufficient stock available for each item
            foreach ($transfer->items as $item) {
                $sourceStock = DB::table('inventory_stocks')
                    ->where('branch_id', $transfer->source_branch_id)
                    ->where('product_id', $item->product_id)
                    ->first();

                $sourceCurrent = $sourceStock ? $sourceStock->current_stock : 0;
                if ($sourceCurrent < $item->quantity) {
                    $productName = $item->product->name ?? "Product #{$item->product_id}";
                    throw ValidationException::withMessages([
                        'stock' => "Insufficient stock at source branch for product '{$productName}'. Available: {$sourceCurrent}, Required: {$item->quantity}.",
                    ]);
                }
            }

            $updated = $this->transferRepository->updateTransferStatus($transfer, 'approved', [
                'approved_by' => $actorId,
                'approved_at' => now(),
                'updated_by' => $actorId,
            ]);

            $this->activityLogService->log('transfer_approved', $updated);
            return $updated;
        });
    }

    public function rejectTransfer(InventoryTransfer $transfer, string $reason, ?int $userId = null): InventoryTransfer
    {
        if (!in_array($transfer->status, ['draft', 'requested', 'approved'])) {
            throw ValidationException::withMessages(['status' => 'Cannot reject transfer once dispatched or received.']);
        }

        return DB::transaction(function () use ($transfer, $reason, $userId) {
            $actorId = $userId ?? Auth::id();

            $updated = $this->transferRepository->updateTransferStatus($transfer, 'rejected', [
                'rejection_reason' => $reason,
                'updated_by' => $actorId,
            ]);

            $this->activityLogService->log('transfer_rejected', $updated);
            return $updated;
        });
    }

    /**
     * Dispatch Transfer (Status: approved/draft/requested -> in_transit).
     * Atomically deducts stock from Source Branch exactly once and records TRANSFER_OUT movement.
     */
    public function dispatchTransfer(InventoryTransfer $transfer, ?int $userId = null): InventoryTransfer
    {
        if (!in_array($transfer->status, ['approved', 'draft', 'requested'])) {
            throw ValidationException::withMessages(['status' => 'Transfer is not in a dispatchable state.']);
        }

        $transfer->load('items.product');
        $actorId = $userId ?? Auth::id();

        return DB::transaction(function () use ($transfer, $actorId) {
            // Guard against duplicate deduction for historical transfers
            $alreadyDeducted = DB::table('inventory_stock_movements')
                ->where('reference_type', 'inventory_transfer')
                ->where('reference_id', $transfer->id)
                ->where('movement_type', 'transfer_out')
                ->exists();

            if (!$alreadyDeducted) {
                // Lock and deduct stock at Source Branch for each transfer item
                foreach ($transfer->items as $item) {
                    $stock = DB::table('inventory_stocks')
                        ->where('branch_id', $transfer->source_branch_id)
                        ->where('product_id', $item->product_id)
                        ->lockForUpdate()
                        ->first();

                    $currentStock = $stock ? $stock->current_stock : 0;

                    if ($currentStock < $item->quantity) {
                        $productName = $item->product->name ?? "Product #{$item->product_id}";
                        throw ValidationException::withMessages([
                            'stock' => "Insufficient stock at source branch for product '{$productName}'. Available: {$currentStock}, Required: {$item->quantity}.",
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

                    // Create immutable TRANSFER_OUT movement for Source Branch
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
                        'created_by' => $actorId,
                    ]);
                }
            }

            $updateData = [
                'dispatched_by' => $actorId,
                'dispatched_at' => now(),
                'updated_by' => $actorId,
            ];

            if (!$transfer->approved_at) {
                $updateData['approved_by'] = $actorId;
                $updateData['approved_at'] = now();
            }

            $updated = $this->transferRepository->updateTransferStatus($transfer, 'in_transit', $updateData);

            $this->activityLogService->log('transfer_dispatched', $updated);
            return $updated;
        });
    }

    /**
     * Receive Transfer at Destination Branch (Full or Partial).
     * Accepts received good, damaged, and shortage quantities.
     * Credits only good received quantities to sellable stock.
     */
    public function receiveTransfer(
        InventoryTransfer $transfer,
        array|int|null $receivedItemsOrUserId = null,
        ?array $receivedItems = null,
        ?string $receiptNotes = null
    ): InventoryTransfer {
        // Normalize arguments for backward compatibility
        if (is_int($receivedItemsOrUserId)) {
            $actorId = $receivedItemsOrUserId;
            $itemsPayload = $receivedItems ?? [];
        } else {
            $actorId = Auth::id();
            $itemsPayload = is_array($receivedItemsOrUserId) ? $receivedItemsOrUserId : ($receivedItems ?? []);
        }

        if (!in_array($transfer->status, ['in_transit', 'partially_received'])) {
            throw ValidationException::withMessages([
                'status' => 'Transfer must be in transit or partially received before receiving.',
            ]);
        }

        $user = Auth::user();
        if ($user && !$user->isSuperAdmin() && !$user->isCompanyAdmin()) {
            if ($user->branch_id && (int) $user->branch_id !== (int) $transfer->destination_branch_id) {
                throw ValidationException::withMessages([
                    'branch' => 'Unauthorized. You can only accept stock transfers sent to your assigned branch.',
                ]);
            }
        }

        return DB::transaction(function () use ($transfer, $actorId, $itemsPayload, $receiptNotes) {
            // Lock transfer row
            $transfer = InventoryTransfer::where('id', $transfer->id)->lockForUpdate()->firstOrFail();
            $transfer->load('items.product');

            // Check if this transfer had its destination stock already credited at approval under legacy code
            $historicallyCreditedAtApproval = DB::table('inventory_stock_movements')
                ->where('reference_type', 'inventory_transfer')
                ->where('reference_id', $transfer->id)
                ->where('movement_type', 'transfer_in')
                ->where('remarks', 'like', 'Stock received on transfer approval%')
                ->exists();

            $batchGoodTotal = 0;
            $batchDamagedTotal = 0;
            $batchShortageTotal = 0;
            $processedReceiptItems = [];

            // If payload is empty, default to receiving all remaining items as good items (full receipt)
            $isDefaultFullReceipt = empty($itemsPayload);

            foreach ($transfer->items as $item) {
                $itemPayload = null;

                if ($isDefaultFullReceipt) {
                    $remaining = $item->remaining_quantity;
                    if ($remaining > 0) {
                        $itemPayload = [
                            'good_quantity' => $remaining,
                            'damaged_quantity' => 0,
                            'shortage_quantity' => 0,
                            'remarks' => null,
                        ];
                    }
                } else {
                    // Check if payload is keyed by item_id or product_id
                    if (isset($itemsPayload[$item->id])) {
                        $itemPayload = is_array($itemsPayload[$item->id]) ? $itemsPayload[$item->id] : ['good_quantity' => (int) $itemsPayload[$item->id]];
                    } elseif (isset($itemsPayload[$item->product_id])) {
                        $itemPayload = is_array($itemsPayload[$item->product_id]) ? $itemsPayload[$item->product_id] : ['good_quantity' => (int) $itemsPayload[$item->product_id]];
                    } else {
                        // Check if payload is a sequential array of item objects
                        foreach ($itemsPayload as $p) {
                            if (is_array($p)) {
                                if ((isset($p['item_id']) && (int) $p['item_id'] === $item->id) ||
                                    (isset($p['transfer_item_id']) && (int) $p['transfer_item_id'] === $item->id) ||
                                    (isset($p['product_id']) && (int) $p['product_id'] === $item->product_id)) {
                                    $itemPayload = $p;
                                    break;
                                }
                            }
                        }
                    }
                }

                if (!$itemPayload) {
                    continue;
                }

                $goodQty = max(0, (int) ($itemPayload['good_quantity'] ?? $itemPayload['received_qty'] ?? $itemPayload['quantity'] ?? 0));
                $damagedQty = max(0, (int) ($itemPayload['damaged_quantity'] ?? $itemPayload['damaged_qty'] ?? 0));
                $shortageQty = max(0, (int) ($itemPayload['shortage_quantity'] ?? $itemPayload['shortage_qty'] ?? 0));
                $remarks = $itemPayload['remarks'] ?? $itemPayload['discrepancy_remarks'] ?? null;

                $totalThisReceipt = $goodQty + $damagedQty + $shortageQty;
                $currentRemaining = $item->remaining_quantity;

                if ($totalThisReceipt > $currentRemaining) {
                    $productName = $item->product->name ?? "Product #{$item->product_id}";
                    throw ValidationException::withMessages([
                        'items' => "Cannot receive {$totalThisReceipt} units for '{$productName}'. Only {$currentRemaining} units remain pending.",
                    ]);
                }

                if ($totalThisReceipt <= 0) {
                    continue;
                }

                $batchGoodTotal += $goodQty;
                $batchDamagedTotal += $damagedQty;
                $batchShortageTotal += $shortageQty;

                // Credit newly accepted GOOD stock to destination branch (only if not historically credited at approval)
                if ($goodQty > 0 && !$historicallyCreditedAtApproval) {
                    $destStock = DB::table('inventory_stocks')
                        ->where('branch_id', $transfer->destination_branch_id)
                        ->where('product_id', $item->product_id)
                        ->lockForUpdate()
                        ->first();

                    $stockBefore = $destStock ? $destStock->current_stock : 0;
                    $stockAfter = $stockBefore + $goodQty;

                    if ($destStock) {
                        DB::table('inventory_stocks')
                            ->where('id', $destStock->id)
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

                    // Record immutable TRANSFER_IN movement for Destination Branch
                    $movementCode = $this->inventoryRepository->generateMovementCode($transfer->destination_branch_id);
                    $this->inventoryRepository->recordStockMovement([
                        'company_id' => $transfer->destination_company_id,
                        'branch_id' => $transfer->destination_branch_id,
                        'product_id' => $item->product_id,
                        'movement_code' => $movementCode,
                        'movement_type' => 'transfer_in',
                        'quantity' => $goodQty,
                        'unit_price' => $item->unit_price,
                        'total_value' => round($goodQty * (float) $item->unit_price, 2),
                        'stock_before' => $stockBefore,
                        'stock_after' => $stockAfter,
                        'reference_type' => 'inventory_transfer',
                        'reference_id' => $transfer->id,
                        'remarks' => "Stock received on transfer {$transfer->transfer_number}.",
                        'created_by' => $actorId,
                    ]);
                }

                // Update item cumulative counts
                $item->received_quantity += $goodQty;
                $item->damaged_quantity += $damagedQty;
                $item->shortage_quantity += $shortageQty;
                if ($remarks) {
                    $item->discrepancy_remarks = trim(($item->discrepancy_remarks ? $item->discrepancy_remarks . "\n" : '') . $remarks);
                }
                $item->save();

                $processedReceiptItems[] = [
                    'transfer_item_id' => $item->id,
                    'product_id' => $item->product_id,
                    'good_quantity' => $goodQty,
                    'damaged_quantity' => $damagedQty,
                    'shortage_quantity' => $shortageQty,
                    'remarks' => $remarks,
                ];
            }

            if (empty($processedReceiptItems)) {
                throw ValidationException::withMessages([
                    'items' => 'At least one item must have a quantity greater than zero to record a receipt.',
                ]);
            }

            // Create InventoryTransferReceipt record
            $receiptSeq = $transfer->receipts()->count() + 1;
            $receiptNumber = "RCP-{$transfer->transfer_number}-" . str_pad($receiptSeq, 2, '0', STR_PAD_LEFT);

            $receipt = InventoryTransferReceipt::create([
                'transfer_id' => $transfer->id,
                'receipt_number' => $receiptNumber,
                'received_by' => $actorId,
                'received_at' => now(),
                'total_good_quantity' => $batchGoodTotal,
                'total_damaged_quantity' => $batchDamagedTotal,
                'total_shortage_quantity' => $batchShortageTotal,
                'notes' => $receiptNotes,
            ]);

            foreach ($processedReceiptItems as $pItem) {
                $pItem['receipt_id'] = $receipt->id;
                InventoryTransferReceiptItem::create($pItem);
            }

            // Refresh items to compute overall remaining quantities
            $allItems = $transfer->items()->get();
            $totalRemaining = $allItems->sum(fn($it) => $it->remaining_quantity);
            $totalDamaged = $allItems->sum(fn($it) => $it->damaged_quantity);
            $totalShortage = $allItems->sum(fn($it) => $it->shortage_quantity);

            if ($totalRemaining === 0) {
                if ($totalDamaged === 0 && $totalShortage === 0) {
                    $finalStatus = 'received';
                } else {
                    $finalStatus = 'discrepancy';
                }
            } else {
                $finalStatus = 'partially_received';
            }

            $updateData = [
                'status' => $finalStatus,
                'received_by' => $actorId,
                'received_at' => now(),
                'updated_by' => $actorId,
            ];

            $transfer = $this->transferRepository->updateTransferStatus($transfer, $finalStatus, $updateData);

            $this->activityLogService->log(
                $finalStatus === 'received' ? 'transfer_received' : 'transfer_partially_received',
                $transfer
            );

            return $transfer->fresh([
                'items.product.categoryRel',
                'receipts.receiver',
                'receipts.items.product',
            ]);
        });
    }

    public function cancelTransfer(InventoryTransfer $transfer, ?int $userId = null): InventoryTransfer
    {
        if (in_array($transfer->status, ['in_transit', 'partially_received', 'received', 'discrepancy'])) {
            throw ValidationException::withMessages([
                'status' => 'Cannot cancel transfer once dispatched or received. Stock has already moved.',
            ]);
        }

        return DB::transaction(function () use ($transfer, $userId) {
            $actorId = $userId ?? Auth::id();

            $updated = $this->transferRepository->updateTransferStatus($transfer, 'cancelled', [
                'cancelled_by' => $actorId,
                'cancelled_at' => now(),
                'updated_by' => $actorId,
            ]);

            $this->activityLogService->log('transfer_cancelled', $updated);
            return $updated;
        });
    }

    /**
     * Cancel remaining pending quantities for specific transfer items.
     * Preserves already received good stock.
     * Prevents cancelled quantities from ever being received later in this transfer.
     */
    public function cancelRemainingItems(
        InventoryTransfer $transfer,
        array $itemsPayload,
        string $reason,
        ?int $userId = null
    ): InventoryTransfer {
        $trimmedReason = trim($reason);
        if (empty($trimmedReason)) {
            throw ValidationException::withMessages([
                'cancellation_reason' => 'A valid cancellation reason is required.',
            ]);
        }

        if (!in_array($transfer->status, ['in_transit', 'partially_received', 'discrepancy'])) {
            throw ValidationException::withMessages([
                'status' => 'Remaining quantities can only be cancelled for transfers that are in transit or partially received.',
            ]);
        }

        $actorId = $userId ?? Auth::id();

        return DB::transaction(function () use ($transfer, $itemsPayload, $trimmedReason, $actorId) {
            $transfer = InventoryTransfer::where('id', $transfer->id)->lockForUpdate()->firstOrFail();
            $transfer->load('items.product.categoryRel');

            $processedItems = [];
            $totalCancelledInBatch = 0;

            foreach ($transfer->items as $item) {
                $cancelQty = null;

                // Check if this item is targeted in $itemsPayload
                if (isset($itemsPayload[$item->id])) {
                    $cancelQty = is_array($itemsPayload[$item->id]) 
                        ? ($itemsPayload[$item->id]['quantity'] ?? null) 
                        : $itemsPayload[$item->id];
                } elseif (in_array($item->id, $itemsPayload, true)) {
                    // Passed as simple array of item IDs [1, 2, 3]
                    $cancelQty = null;
                } else {
                    // Check if sequential array with 'item_id' or 'transfer_item_id'
                    foreach ($itemsPayload as $p) {
                        if (is_array($p) && (
                            (isset($p['item_id']) && (int)$p['item_id'] === $item->id) ||
                            (isset($p['transfer_item_id']) && (int)$p['transfer_item_id'] === $item->id)
                        )) {
                            $cancelQty = $p['quantity'] ?? null;
                            break;
                        }
                    }
                }

                if ($cancelQty === null && !isset($itemsPayload[$item->id]) && !in_array($item->id, $itemsPayload, true)) {
                    continue;
                }

                $currentRemaining = $item->remaining_quantity;
                if ($currentRemaining <= 0) {
                    continue;
                }

                // If null or <= 0 provided, default to cancelling all remaining for this item
                $cancelQty = ($cancelQty !== null && (int)$cancelQty > 0) ? (int)$cancelQty : $currentRemaining;

                if ($cancelQty > $currentRemaining) {
                    $productName = $item->product->name ?? "Product #{$item->product_id}";
                    throw ValidationException::withMessages([
                        'items' => "Cannot cancel {$cancelQty} units for '{$productName}'. Only {$currentRemaining} units are pending.",
                    ]);
                }

                // Update transfer item
                $item->cancelled_quantity += $cancelQty;
                $item->cancellation_reason = trim(($item->cancellation_reason ? $item->cancellation_reason . "\n" : '') . $trimmedReason);
                $item->cancelled_by = $actorId;
                $item->cancelled_at = now();
                $item->save();

                $totalCancelledInBatch += $cancelQty;
                $processedItems[] = [
                    'transfer_item_id' => $item->id,
                    'product_id' => $item->product_id,
                    'cancelled_quantity' => $cancelQty,
                    'reason' => $trimmedReason,
                ];
            }

            if (empty($processedItems)) {
                throw ValidationException::withMessages([
                    'items' => 'No eligible pending items found to cancel.',
                ]);
            }

            // Create batch cancellation record
            $cancellation = InventoryTransferCancellation::create([
                'transfer_id' => $transfer->id,
                'cancelled_by' => $actorId,
                'cancelled_at' => now(),
                'scope' => 'item',
                'category_id' => null,
                'total_cancelled_quantity' => $totalCancelledInBatch,
                'reason' => $trimmedReason,
            ]);

            foreach ($processedItems as $pItem) {
                $pItem['cancellation_id'] = $cancellation->id;
                InventoryTransferCancellationItem::create($pItem);
            }

            // Evaluate overall remaining quantities to determine new status
            $allItems = $transfer->items()->get();
            $totalRemaining = $allItems->sum(fn($it) => $it->remaining_quantity);
            $totalReceived = $allItems->sum(fn($it) => $it->received_quantity);
            $totalDamaged = $allItems->sum(fn($it) => $it->damaged_quantity);
            $totalShortage = $allItems->sum(fn($it) => $it->shortage_quantity);

            if ($totalRemaining === 0) {
                if ($totalReceived > 0) {
                    if ($totalDamaged > 0 || $totalShortage > 0) {
                        $newStatus = 'discrepancy';
                    } else {
                        $newStatus = 'received';
                    }
                } else {
                    $newStatus = 'cancelled';
                }
            } else {
                $newStatus = 'partially_received';
            }

            $updateData = [
                'status' => $newStatus,
                'updated_by' => $actorId,
            ];
            if ($newStatus === 'cancelled') {
                $updateData['cancelled_by'] = $actorId;
                $updateData['cancelled_at'] = now();
            }

            $transfer = $this->transferRepository->updateTransferStatus($transfer, $newStatus, $updateData);

            $this->activityLogService->log('transfer_items_cancelled', $transfer, [
                'total_cancelled' => $totalCancelledInBatch,
                'reason' => $trimmedReason,
                'new_status' => $newStatus,
            ]);

            return $transfer->fresh([
                'items.product.categoryRel',
                'receipts.receiver',
                'receipts.items.product',
                'cancellations.canceller',
                'cancellations.category',
                'cancellations.items.product',
            ]);
        });
    }

    /**
     * Cancel remaining pending quantities for an entire product category.
     */
    public function cancelCategoryRemaining(
        InventoryTransfer $transfer,
        int $categoryId,
        string $reason,
        ?int $userId = null
    ): InventoryTransfer {
        $trimmedReason = trim($reason);
        if (empty($trimmedReason)) {
            throw ValidationException::withMessages([
                'cancellation_reason' => 'A valid cancellation reason is required.',
            ]);
        }

        if (!in_array($transfer->status, ['in_transit', 'partially_received', 'discrepancy'])) {
            throw ValidationException::withMessages([
                'status' => 'Remaining quantities can only be cancelled for transfers that are in transit or partially received.',
            ]);
        }

        $transfer->load('items.product');
        $categoryItemIds = [];
        foreach ($transfer->items as $item) {
            if ($item->product && (int) $item->product->category_id === (int) $categoryId && $item->remaining_quantity > 0) {
                $categoryItemIds[] = $item->id;
            }
        }

        if (empty($categoryItemIds)) {
            throw ValidationException::withMessages([
                'category_id' => 'No pending items found for the selected category.',
            ]);
        }

        $actorId = $userId ?? Auth::id();

        return DB::transaction(function () use ($transfer, $categoryItemIds, $categoryId, $trimmedReason, $actorId) {
            $result = $this->cancelRemainingItems($transfer, $categoryItemIds, $trimmedReason, $actorId);

            $latestCancellation = $result->cancellations()->first();
            if ($latestCancellation) {
                $latestCancellation->update([
                    'scope' => 'category',
                    'category_id' => $categoryId,
                ]);
            }

            return $result;
        });
    }

    /**
     * Cancel all remaining pending quantities across all items in a transfer.
     */
    public function cancelAllRemaining(
        InventoryTransfer $transfer,
        string $reason,
        ?int $userId = null
    ): InventoryTransfer {
        $trimmedReason = trim($reason);
        if (empty($trimmedReason)) {
            throw ValidationException::withMessages([
                'cancellation_reason' => 'A valid cancellation reason is required.',
            ]);
        }

        if (!in_array($transfer->status, ['in_transit', 'partially_received', 'discrepancy'])) {
            throw ValidationException::withMessages([
                'status' => 'Remaining quantities can only be cancelled for transfers that are in transit or partially received.',
            ]);
        }

        $transfer->load('items');
        $pendingItemIds = [];
        foreach ($transfer->items as $item) {
            if ($item->remaining_quantity > 0) {
                $pendingItemIds[] = $item->id;
            }
        }

        if (empty($pendingItemIds)) {
            throw ValidationException::withMessages([
                'items' => 'There are no pending items remaining in this transfer.',
            ]);
        }

        $actorId = $userId ?? Auth::id();

        return DB::transaction(function () use ($transfer, $pendingItemIds, $trimmedReason, $actorId) {
            $result = $this->cancelRemainingItems($transfer, $pendingItemIds, $trimmedReason, $actorId);

            $latestCancellation = $result->cancellations()->first();
            if ($latestCancellation) {
                $latestCancellation->update([
                    'scope' => 'all_remaining',
                ]);
            }

            return $result;
        });
    }
}
