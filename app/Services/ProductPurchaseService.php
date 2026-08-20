<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Product;
use App\Models\ProductPurchase;
use App\Repositories\InventoryRepositoryInterface;
use App\Repositories\ProductPurchaseRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductPurchaseService
{
    public function __construct(
        protected ProductPurchaseRepositoryInterface $purchaseRepository,
        protected InventoryRepositoryInterface $inventoryRepository,
        protected ActivityLogService $activityLogService
    ) {}

    public function getPaginatedPurchases(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $user = Auth::user();
        if ($user && !$user->isSuperAdmin()) {
            $filters['company_id'] = $user->company_id;
            if ($user->branch_id && !$user->isCompanyAdmin() && empty($filters['branch_id'])) {
                $filters['branch_id'] = $user->branch_id;
            }
        }

        return $this->purchaseRepository->getPaginatedPurchases($filters, $perPage);
    }

    public function getPurchaseById(int $id): ?ProductPurchase
    {
        return $this->purchaseRepository->findById($id);
    }

    public function createPurchase(array $data, array $items): ProductPurchase
    {
        if (empty($items)) {
            throw ValidationException::withMessages(['items' => 'At least one product item is required for a purchase.']);
        }

        return DB::transaction(function () use ($data, $items) {
            $branch = Branch::findOrFail($data['branch_id']);

            $user = Auth::user();
            if ($user && !$user->isSuperAdmin()) {
                if ($user->company_id && $user->company_id !== $branch->company_id) {
                    throw ValidationException::withMessages(['branch_id' => 'Selected branch does not belong to your company.']);
                }
            }

            $purchaseNumber = $this->purchaseRepository->generatePurchaseNumber($branch->id);

            // Compute Financial Totals & Items Snapshots
            $computed = $this->calculateFinancials($items, $data['discount_amount'] ?? 0, $data['other_charges'] ?? 0, $data['paid_amount'] ?? 0);

            $supplierId = $data['supplier_id'] ?? null;
            $supplierName = $data['supplier_name'] ?? null;
            if ($supplierId && empty($supplierName)) {
                $supplier = \App\Models\Supplier::find($supplierId);
                if ($supplier) {
                    $supplierName = $supplier->supplier_name;
                }
            }

            $masterData = [
                'purchase_number' => $purchaseNumber,
                'company_id' => $branch->company_id,
                'branch_id' => $branch->id,
                'supplier_id' => $supplierId,
                'supplier_name' => $supplierName ?? 'General Supplier',
                'supplier_reference' => $data['supplier_reference'] ?? null,
                'supplier_invoice_number' => $data['supplier_invoice_number'] ?? null,
                'purchase_date' => $data['purchase_date'] ?? now()->toDateString(),
                'subtotal' => $computed['subtotal'],
                'discount_amount' => $computed['discount_amount'],
                'tax_amount' => $computed['tax_amount'],
                'other_charges' => $computed['other_charges'],
                'grand_total' => $computed['grand_total'],
                'paid_amount' => $computed['paid_amount'],
                'due_amount' => $computed['due_amount'],
                'payment_status' => $computed['payment_status'],
                'payment_method' => $data['payment_method'] ?? 'bank_transfer',
                'purchase_status' => 'draft',
                'remarks' => $data['remarks'] ?? null,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ];

            $purchase = $this->purchaseRepository->createPurchase($masterData, $computed['items']);
            $this->activityLogService->log('purchase_created', $purchase);

            return $purchase;
        });
    }

    public function updatePurchase(ProductPurchase $purchase, array $data, array $items): ProductPurchase
    {
        if ($purchase->purchase_status !== 'draft') {
            throw ValidationException::withMessages(['purchase_status' => 'Only draft purchases can be edited.']);
        }

        return DB::transaction(function () use ($purchase, $data, $items) {
            $computed = $this->calculateFinancials($items, $data['discount_amount'] ?? 0, $data['other_charges'] ?? 0, $data['paid_amount'] ?? 0);

            $supplierId = $data['supplier_id'] ?? $purchase->supplier_id;
            $supplierName = $data['supplier_name'] ?? null;
            if ($supplierId && empty($supplierName)) {
                $supplier = \App\Models\Supplier::find($supplierId);
                if ($supplier) {
                    $supplierName = $supplier->supplier_name;
                }
            }

            $masterData = [
                'supplier_id' => $supplierId,
                'supplier_name' => $supplierName ?? $purchase->supplier_name,
                'supplier_reference' => $data['supplier_reference'] ?? null,
                'supplier_invoice_number' => $data['supplier_invoice_number'] ?? null,
                'purchase_date' => $data['purchase_date'],
                'subtotal' => $computed['subtotal'],
                'discount_amount' => $computed['discount_amount'],
                'tax_amount' => $computed['tax_amount'],
                'other_charges' => $computed['other_charges'],
                'grand_total' => $computed['grand_total'],
                'paid_amount' => $computed['paid_amount'],
                'due_amount' => $computed['due_amount'],
                'payment_status' => $computed['payment_status'],
                'payment_method' => $data['payment_method'] ?? 'bank_transfer',
                'remarks' => $data['remarks'] ?? null,
                'updated_by' => Auth::id(),
            ];

            $updated = $this->purchaseRepository->updatePurchase($purchase, $masterData, $computed['items']);
            $this->activityLogService->log('purchase_updated', $updated);

            return $updated;
        });
    }

    public function confirmPurchase(ProductPurchase $purchase): ProductPurchase
    {
        return DB::transaction(function () use ($purchase) {
            $locked = ProductPurchase::where('id', $purchase->id)->lockForUpdate()->first();
            if (!$locked) {
                $locked = $purchase;
            }

            if ($locked->purchase_status === 'cancelled') {
                throw ValidationException::withMessages(['purchase_status' => 'Cannot confirm a cancelled purchase.']);
            }

            // Auto-update stock on approval if not already processed (Idempotent check)
            if (!$locked->is_inventory_processed && !\App\Models\InventoryStockMovement::where('reference_type', 'product_purchase')->where('reference_id', $locked->id)->exists()) {
                $this->processInventoryReceipt($locked);
            }

            $updated = $this->purchaseRepository->updateStatus($locked, 'confirmed', [
                'updated_by' => Auth::id(),
            ]);

            $this->activityLogService->log('purchase_confirmed', $updated);
            return $updated;
        });
    }

    public function receivePurchase(ProductPurchase $purchase): ProductPurchase
    {
        return DB::transaction(function () use ($purchase) {
            $locked = ProductPurchase::where('id', $purchase->id)->lockForUpdate()->first();
            if (!$locked) {
                $locked = $purchase;
            }

            if ($locked->purchase_status === 'cancelled') {
                throw ValidationException::withMessages(['purchase_status' => 'Cannot receive a cancelled purchase.']);
            }

            // Auto-update stock if not already processed (Idempotent check)
            if (!$locked->is_inventory_processed && !\App\Models\InventoryStockMovement::where('reference_type', 'product_purchase')->where('reference_id', $locked->id)->exists()) {
                $this->processInventoryReceipt($locked);
            }

            $updated = $this->purchaseRepository->updateStatus($locked, 'received', [
                'received_by' => Auth::id(),
                'received_at' => $locked->received_at ?? now(),
                'updated_by' => Auth::id(),
            ]);

            $this->activityLogService->log('purchase_received', $updated);
            return $updated;
        });
    }

    protected function processInventoryReceipt(ProductPurchase $purchase): void
    {
        foreach ($purchase->items as $item) {
            $stock = DB::table('inventory_stocks')
                ->where('branch_id', $purchase->branch_id)
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
                    'company_id' => $purchase->company_id,
                    'branch_id' => $purchase->branch_id,
                    'product_id' => $item->product_id,
                    'current_stock' => $stockAfter,
                    'reserved_stock' => 0,
                    'reorder_level' => 5,
                    'last_restocked_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $movementCode = $this->inventoryRepository->generateMovementCode($purchase->branch_id);
            $this->inventoryRepository->recordStockMovement([
                'company_id' => $purchase->company_id,
                'branch_id' => $purchase->branch_id,
                'product_id' => $item->product_id,
                'movement_code' => $movementCode,
                'movement_type' => 'purchase_in',
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_purchase_cost,
                'total_value' => $item->line_total,
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
                'reference_type' => 'product_purchase',
                'reference_id' => $purchase->id,
                'remarks' => "Received Purchase {$purchase->purchase_number} from Supplier '{$purchase->supplier_name}' (Invoice: {$purchase->supplier_invoice_number}).",
                'created_by' => Auth::id(),
            ]);
        }

        $purchase->update([
            'is_inventory_processed' => true,
            'received_at' => now(),
            'received_by' => Auth::id(),
        ]);
    }

    public function cancelPurchase(ProductPurchase $purchase): ProductPurchase
    {
        if ($purchase->purchase_status === 'received') {
            throw ValidationException::withMessages(['purchase_status' => 'Cannot cancel a purchase that has already been received into inventory.']);
        }

        return DB::transaction(function () use ($purchase) {
            $updated = $this->purchaseRepository->updateStatus($purchase, 'cancelled', [
                'updated_by' => Auth::id(),
            ]);

            $this->activityLogService->log('purchase_cancelled', $updated);
            return $updated;
        });
    }

    protected function calculateFinancials(array $items, float $discountAmount, float $otherCharges, float $paidAmount): array
    {
        $subtotal = 0.0;
        $totalTax = 0.0;
        $processedItems = [];

        foreach ($items as $idx => $rawItem) {
            $itemNum = $idx + 1;
            if (empty($rawItem['category_id'])) {
                throw ValidationException::withMessages(['items' => "Product Category is required for item #{$itemNum}."]);
            }
            if (empty($rawItem['brand_id'])) {
                throw ValidationException::withMessages(['items' => "Product Brand is required for item #{$itemNum}."]);
            }
            if (empty($rawItem['product_id'])) {
                throw ValidationException::withMessages(['items' => "Product selection is required for item #{$itemNum}."]);
            }

            $product = Product::findOrFail($rawItem['product_id']);
            if (!$product->is_active) {
                throw ValidationException::withMessages(['items' => "Product '{$product->name}' is inactive."]);
            }

            if ($product->category_id && (int) $product->category_id !== (int) $rawItem['category_id']) {
                throw ValidationException::withMessages(['items' => "Product '{$product->name}' does not belong to the selected category."]);
            }

            if ($product->brand_id && (int) $product->brand_id !== (int) $rawItem['brand_id']) {
                throw ValidationException::withMessages(['items' => "Product '{$product->name}' does not belong to the selected brand."]);
            }

            $user = Auth::user();
            if ($user && !$user->isSuperAdmin() && $user->company_id && $product->company_id && (int) $product->company_id !== (int) $user->company_id) {
                throw ValidationException::withMessages(['items' => "Product '{$product->name}' does not belong to your company."]);
            }

            $qty = (int) $rawItem['quantity'];
            if ($qty <= 0) {
                throw ValidationException::withMessages(['items' => "Quantity for product '{$product->name}' must be greater than zero."]);
            }

            $unitCost = isset($rawItem['unit_purchase_cost']) ? (float) $rawItem['unit_purchase_cost'] : (float) ($product->cost_price ?? $product->unit_price);
            $taxRate = isset($rawItem['tax_rate']) ? (float) $rawItem['tax_rate'] : (float) ($product->tax_percentage ?? 18.00);
            $discount = isset($rawItem['discount']) ? (float) $rawItem['discount'] : 0.0;

            $lineSubtotal = round(($qty * $unitCost) - $discount, 2);
            $lineTax = round($lineSubtotal * ($taxRate / 100), 2);
            $lineTotal = round($lineSubtotal + $lineTax, 2);

            $subtotal += $lineSubtotal;
            $totalTax += $lineTax;

            $processedItems[] = [
                'product_id' => $product->id,
                'product_sku_snapshot' => $product->sku,
                'product_name_snapshot' => $product->name,
                'quantity' => $qty,
                'unit_purchase_cost' => $unitCost,
                'mrp_snapshot' => (float) $product->unit_price,
                'discount' => $discount,
                'tax_rate' => $taxRate,
                'tax_amount' => $lineTax,
                'line_subtotal' => $lineSubtotal,
                'line_total' => $lineTotal,
            ];
        }

        $grandTotal = round($subtotal + $totalTax - $discountAmount + $otherCharges, 2);
        $dueAmount = max(0.0, round($grandTotal - $paidAmount, 2));

        if ($paidAmount <= 0) {
            $paymentStatus = 'unpaid';
        } elseif ($paidAmount >= $grandTotal) {
            $paymentStatus = 'paid';
            $dueAmount = 0.0;
        } else {
            $paymentStatus = 'partially_paid';
        }

        return [
            'subtotal' => $subtotal,
            'tax_amount' => $totalTax,
            'discount_amount' => $discountAmount,
            'other_charges' => $otherCharges,
            'grand_total' => $grandTotal,
            'paid_amount' => $paidAmount,
            'due_amount' => $dueAmount,
            'payment_status' => $paymentStatus,
            'items' => $processedItems,
        ];
    }
}
