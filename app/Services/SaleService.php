<?php

namespace App\Services;

use App\Models\InventoryStock;
use App\Models\InventoryStockMovement;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaleService
{
    public function __construct(
        protected BillingService $billingService,
        protected ActivityLogService $activityLogService
    ) {}

    /**
     * Generate Unique Sale Number.
     * Format: SALE-2026-000001
     */
    public function generateSaleNumber(): string
    {
        $year = date('Y');
        $maxId = DB::table('sales')->max('id') ?? 0;
        $nextSeq = str_pad($maxId + 1, 6, '0', STR_PAD_LEFT);

        $candidate = "SALE-{$year}-{$nextSeq}";

        while (Sale::where('sale_number', $candidate)->exists()) {
            $maxId++;
            $nextSeq = str_pad($maxId + 1, 6, '0', STR_PAD_LEFT);
            $candidate = "SALE-{$year}-{$nextSeq}";
        }

        return $candidate;
    }

    /**
     * Process Direct Product Sale and generate Invoice + Stock Movement.
     */
    public function createDirectSale(array $data): Sale
    {
        $user = Auth::user();
        $companyId = $data['company_id'] ?? ($user?->company_id ?? 1);
        $branchId = $data['branch_id'] ?? ($user?->branch_id ?? 1);
        $items = $data['items'] ?? [];

        if (empty($items)) {
            throw ValidationException::withMessages(['items' => 'At least one product item is required for direct sale.']);
        }

        return DB::transaction(function () use ($data, $companyId, $branchId, $items, $user) {
            // 1. Validate & Lock inventory stock for each product line item
            $validatedItems = [];
            $subtotal = 0.00;
            $totalDiscount = 0.00;
            $totalTax = 0.00;

            foreach ($items as $itemData) {
                $productId = (int) $itemData['product_id'];
                $quantity = (int) $itemData['quantity'];
                $discount = (float) ($itemData['discount'] ?? 0);
                $tax = (float) ($itemData['tax'] ?? 0);

                if ($quantity <= 0) {
                    throw ValidationException::withMessages(['quantity' => 'Item quantity must be greater than zero.']);
                }

                $product = Product::findOrFail($productId);
                $unitPrice = (float) ($itemData['unit_price'] ?? $product->unit_price);

                $stock = InventoryStock::where('branch_id', $branchId)
                    ->where('product_id', $productId)
                    ->lockForUpdate()
                    ->first();

                $availableStock = $stock ? $stock->available_stock : 0;

                if (!$stock || $availableStock < $quantity) {
                    throw ValidationException::withMessages([
                        'inventory' => "Insufficient inventory stock for '{$product->name}' at transaction branch. Required: {$quantity}, Available: {$availableStock}.",
                    ]);
                }

                $lineSubtotal = round($quantity * $unitPrice, 2);
                $lineTotal = max(0, round($lineSubtotal - $discount + $tax, 2));

                $subtotal += $lineSubtotal;
                $totalDiscount += $discount;
                $totalTax += $tax;

                $validatedItems[] = [
                    'product' => $product,
                    'stock' => $stock,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'discount' => $discount,
                    'tax' => $tax,
                    'total_amount' => $lineTotal,
                ];
            }

            $grandTotal = max(0, round($subtotal - $totalDiscount + $totalTax, 2));
            $paidAmount = min($grandTotal, (float) ($data['paid_amount'] ?? $grandTotal));
            $balanceAmount = max(0, round($grandTotal - $paidAmount, 2));
            $paymentStatus = ($balanceAmount <= 0) ? 'paid' : (($paidAmount > 0) ? 'partial' : 'unpaid');

            // 2. Create Sale Record
            $saleNumber = $this->generateSaleNumber();

            $sale = Sale::create([
                'sale_number' => $saleNumber,
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'customer_id' => $data['customer_id'] ?? null,
                'customer_name' => trim($data['customer_name'] ?? 'Walk-in Customer'),
                'customer_phone' => $data['customer_phone'] ?? null,
                'customer_address' => $data['customer_address'] ?? null,
                'sale_date' => $data['sale_date'] ?? now()->toDateString(),
                'subtotal' => $subtotal,
                'discount_amount' => $totalDiscount,
                'tax_amount' => $totalTax,
                'total_amount' => $grandTotal,
                'paid_amount' => $paidAmount,
                'balance_amount' => $balanceAmount,
                'payment_method' => $data['payment_method'] ?? 'cash',
                'payment_status' => $paymentStatus,
                'remarks' => $data['remarks'] ?? null,
                'created_by' => $data['created_by'] ?? $user?->id ?? \App\Models\User::value('id'),
            ]);

            // 3. Create Sale Items and Deduct Inventory Stock
            foreach ($validatedItems as $vItem) {
                /** @var Product $product */
                $product = $vItem['product'];
                /** @var InventoryStock $stock */
                $stock = $vItem['stock'];
                $quantity = $vItem['quantity'];

                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_sku' => $product->sku,
                    'quantity' => $quantity,
                    'unit_price' => $vItem['unit_price'],
                    'discount' => $vItem['discount'],
                    'tax' => $vItem['tax'],
                    'total_amount' => $vItem['total_amount'],
                ]);

                // Deduct stock
                $stockBefore = $stock->current_stock;
                $stock->current_stock -= $quantity;
                $stock->last_restocked_at = now();
                $stock->save();

                // Log movement
                $seq = str_pad(DB::table('inventory_stock_movements')->max('id') + 1, 5, '0', STR_PAD_LEFT);
                $movementCode = "STK-DIR-{$seq}";

                InventoryStockMovement::create([
                    'movement_code' => $movementCode,
                    'company_id' => $companyId,
                    'branch_id' => $branchId,
                    'product_id' => $product->id,
                    'movement_type' => 'sales_issue',
                    'quantity' => -$quantity,
                    'stock_before' => $stockBefore,
                    'stock_after' => $stock->current_stock,
                    'unit_price' => $vItem['unit_price'],
                    'total_value' => $vItem['total_amount'],
                    'reference_type' => 'sale',
                    'reference_id' => $sale->id,
                    'created_by' => $data['created_by'] ?? $user?->id ?? \App\Models\User::value('id'),
                    'remarks' => "Direct product sale #{$saleNumber}",
                ]);
            }

            // 4. Generate Centralized Invoice
            $this->billingService->generateInvoiceForDirectSale($sale);

            $this->activityLogService->log('direct_sale_created', $sale);

            return $sale;
        });
    }
}
