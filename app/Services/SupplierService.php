<?php

namespace App\Services;

use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\Company;
use App\Models\ProductPurchaseItem;
use App\Repositories\SupplierRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class SupplierService
{
    public function __construct(
        protected SupplierRepositoryInterface $supplierRepository,
        protected ActivityLogService $activityLogService
    ) {}

    public function getPaginatedSuppliers(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $user = Auth::user();
        if ($user && !$user->isSuperAdmin()) {
            $filters['company_id'] = $user->company_id;
        }

        return $this->supplierRepository->getPaginatedSuppliers($filters, $perPage);
    }

    public function getActiveSuppliers(?int $companyId = null): Collection
    {
        $user = Auth::user();
        $targetCompanyId = $companyId ?: ($user ? $user->company_id : 1);
        return $this->supplierRepository->getAllActiveSuppliers($targetCompanyId);
    }

    public function getSupplierById(int $id): ?Supplier
    {
        return $this->supplierRepository->findById($id);
    }

    public function createSupplier(array $data): Supplier
    {
        $user = Auth::user();
        $requestedCompanyId = isset($data['company_id']) ? (int) $data['company_id'] : null;
        $companyId = $user
            ? $user->resolveScopedCompanyId($requestedCompanyId)
            : ($requestedCompanyId ?: (\App\Models\Company::first()?->id));

        if (!$companyId || !\App\Models\Company::where('id', $companyId)->exists()) {
            throw ValidationException::withMessages([
                'company_id' => 'Unable to determine the active company. Please select a company before creating a supplier.',
            ]);
        }

        return DB::transaction(function () use ($data, $companyId, $user) {
            if (empty($data['supplier_code'])) {
                $data['supplier_code'] = $this->supplierRepository->generateSupplierCode($companyId);
            }

            $data['company_id'] = $companyId;
            $data['created_by'] = $user ? $user->id : null;
            $data['updated_by'] = $user ? $user->id : null;

            $supplier = $this->supplierRepository->createSupplier($data);
            $this->activityLogService->log('supplier_created', $supplier);

            return $supplier;
        });
    }

    public function updateSupplier(Supplier $supplier, array $data): Supplier
    {
        return DB::transaction(function () use ($supplier, $data) {
            $user = Auth::user();
            $data['updated_by'] = $user ? $user->id : null;

            $updated = $this->supplierRepository->updateSupplier($supplier, $data);
            $this->activityLogService->log('supplier_updated', $updated);

            return $updated;
        });
    }

    public function deleteSupplier(Supplier $supplier): bool
    {
        return DB::transaction(function () use ($supplier) {
            $result = $this->supplierRepository->deleteSupplier($supplier);
            $this->activityLogService->log('supplier_deleted', $supplier);
            return $result;
        });
    }

    public function recordPayment(array $data): SupplierPayment
    {
        return DB::transaction(function () use ($data) {
            $supplier = Supplier::findOrFail($data['supplier_id']);
            $user = Auth::user();

            if ($user && !$user->isSuperAdmin() && $supplier->company_id !== $user->company_id) {
                throw ValidationException::withMessages(['supplier_id' => 'Selected supplier does not belong to your organization.']);
            }

            $paymentNumber = $this->generatePaymentNumber($supplier->company_id);

            $method = $data['payment_method'] ?? 'bank';
            if ($method === 'bank_transfer') {
                $method = 'bank';
            }

            $payment = SupplierPayment::create([
                'uuid' => (string) Str::uuid(),
                'company_id' => $supplier->company_id,
                'branch_id' => $data['branch_id'] ?? ($user ? $user->branch_id : null),
                'supplier_id' => $supplier->id,
                'purchase_id' => $data['purchase_id'] ?? null,
                'payment_number' => $paymentNumber,
                'payment_date' => $data['payment_date'] ?? now()->toDateString(),
                'amount' => $data['amount'],
                'payment_method' => $method,
                'bank_account_id' => $data['bank_account_id'] ?? null,
                'reference_number' => $data['reference_number'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $user ? $user->id : null,
                'updated_by' => $user ? $user->id : null,
            ]);

            // Payment Allocation Logic
            $paymentAmount = (float) $data['amount'];
            
            if (!empty($data['purchase_id'])) {
                // 1. Direct allocation to a specific purchase order
                $purchase = \App\Models\ProductPurchase::findOrFail($data['purchase_id']);
                $allocatedAmount = min($paymentAmount, (float) $purchase->due_amount);
                if ($allocatedAmount > 0) {
                    \App\Models\SupplierPaymentAllocation::create([
                        'company_id' => $supplier->company_id,
                        'supplier_id' => $supplier->id,
                        'supplier_payment_id' => $payment->id,
                        'product_purchase_id' => $purchase->id,
                        'allocated_amount' => $allocatedAmount,
                        'created_by' => $user ? $user->id : null,
                    ]);
                }
            } elseif (!empty($data['allocations']) && is_array($data['allocations'])) {
                // 2. Manual allocation array passed from user form
                $totalAllocated = 0.0;
                foreach ($data['allocations'] as $alloc) {
                    if (empty($alloc['purchase_id']) || empty($alloc['amount'])) continue;
                    $allocAmt = (float) $alloc['amount'];
                    if ($allocAmt <= 0) continue;

                    $purchase = \App\Models\ProductPurchase::findOrFail($alloc['purchase_id']);
                    if ($allocAmt > (float) $purchase->due_amount + 0.01) {
                        throw ValidationException::withMessages([
                            'allocations' => "Allocated amount ₹{$allocAmt} for purchase #{$purchase->purchase_number} exceeds outstanding due ₹{$purchase->due_amount}."
                        ]);
                    }

                    $totalAllocated += $allocAmt;
                    if ($totalAllocated > $paymentAmount + 0.01) {
                        throw ValidationException::withMessages([
                            'allocations' => "Total allocated amount (₹{$totalAllocated}) cannot exceed total payment amount (₹{$paymentAmount})."
                        ]);
                    }

                    \App\Models\SupplierPaymentAllocation::create([
                        'company_id' => $supplier->company_id,
                        'supplier_id' => $supplier->id,
                        'supplier_payment_id' => $payment->id,
                        'product_purchase_id' => $purchase->id,
                        'allocated_amount' => $allocAmt,
                        'created_by' => $user ? $user->id : null,
                    ]);
                }
            } else {
                // 3. FIFO Auto-Allocation across outstanding purchases
                $this->autoAllocatePayment($payment);
            }

            $this->syncSupplierPurchasesPaymentStatus($supplier->id);
            $this->activityLogService->log('supplier_payment_recorded', $payment);

            return $payment;
        });
    }

    public function autoAllocatePayment(SupplierPayment $payment): void
    {
        $remaining = (float) $payment->amount;
        if ($remaining <= 0) return;

        $outstandingPurchases = \App\Models\ProductPurchase::where('supplier_id', $payment->supplier_id)
            ->whereIn('purchase_status', ['confirmed', 'received', 'completed'])
            ->where('due_amount', '>', 0)
            ->orderBy('purchase_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        foreach ($outstandingPurchases as $purchase) {
            $due = (float) $purchase->due_amount;
            if ($due <= 0) continue;

            $allocate = min($remaining, $due);
            if ($allocate > 0) {
                \App\Models\SupplierPaymentAllocation::create([
                    'company_id' => $payment->company_id,
                    'supplier_id' => $payment->supplier_id,
                    'supplier_payment_id' => $payment->id,
                    'product_purchase_id' => $purchase->id,
                    'allocated_amount' => $allocate,
                    'created_by' => Auth::id(),
                ]);

                $remaining -= $allocate;
            }

            if ($remaining <= 0.001) break;
        }
    }

    public function allocateExistingPayment(SupplierPayment $payment, array $data): SupplierPayment
    {
        return DB::transaction(function () use ($payment, $data) {
            $user = Auth::user();
            $available = $payment->unallocated_amount;

            if ($available <= 0.001) {
                throw ValidationException::withMessages(['payment' => 'This payment has already been fully allocated to purchase invoices.']);
            }

            $mode = $data['allocation_mode'] ?? 'auto';

            if ($mode === 'manual' && !empty($data['allocations']) && is_array($data['allocations'])) {
                $totalAllocated = 0.0;
                foreach ($data['allocations'] as $alloc) {
                    if (empty($alloc['purchase_id']) || empty($alloc['amount'])) continue;
                    $allocAmt = (float) $alloc['amount'];
                    if ($allocAmt <= 0) continue;

                    $purchase = \App\Models\ProductPurchase::findOrFail($alloc['purchase_id']);
                    if ($purchase->supplier_id !== $payment->supplier_id) {
                        throw ValidationException::withMessages([
                            'allocations' => "Purchase #{$purchase->purchase_number} does not belong to supplier."
                        ]);
                    }

                    if ($allocAmt > (float) $purchase->due_amount + 0.01) {
                        throw ValidationException::withMessages([
                            'allocations' => "Allocated amount ₹{$allocAmt} for purchase #{$purchase->purchase_number} exceeds outstanding due balance of ₹{$purchase->due_amount}."
                        ]);
                    }

                    $totalAllocated += $allocAmt;
                    if ($totalAllocated > $available + 0.01) {
                        throw ValidationException::withMessages([
                            'allocations' => "Total allocated amount (₹{$totalAllocated}) cannot exceed available unallocated payment balance (₹{$available})."
                        ]);
                    }

                    \App\Models\SupplierPaymentAllocation::create([
                        'company_id' => $payment->company_id,
                        'supplier_id' => $payment->supplier_id,
                        'supplier_payment_id' => $payment->id,
                        'product_purchase_id' => $purchase->id,
                        'allocated_amount' => $allocAmt,
                        'created_by' => $user ? $user->id : null,
                    ]);
                }
            } else {
                $outstandingPurchases = \App\Models\ProductPurchase::where('supplier_id', $payment->supplier_id)
                    ->whereIn('purchase_status', ['confirmed', 'received', 'completed'])
                    ->where('due_amount', '>', 0)
                    ->orderBy('purchase_date', 'asc')
                    ->orderBy('id', 'asc')
                    ->get();

                $remaining = $available;
                foreach ($outstandingPurchases as $purchase) {
                    $due = (float) $purchase->due_amount;
                    if ($due <= 0) continue;

                    $allocateAmt = min($remaining, $due);
                    if ($allocateAmt > 0) {
                        \App\Models\SupplierPaymentAllocation::create([
                            'company_id' => $payment->company_id,
                            'supplier_id' => $payment->supplier_id,
                            'supplier_payment_id' => $payment->id,
                            'product_purchase_id' => $purchase->id,
                            'allocated_amount' => $allocateAmt,
                            'created_by' => $user ? $user->id : null,
                        ]);

                        $remaining -= $allocateAmt;
                    }

                    if ($remaining <= 0.001) break;
                }
            }

            $this->syncSupplierPurchasesPaymentStatus($payment->supplier_id);
            $this->activityLogService->log('supplier_payment_allocated', $payment);

            return $payment->fresh(['allocations.purchase']);
        });
    }

    public function syncSupplierPurchasesPaymentStatus(int $supplierId): void
    {
        $supplier = Supplier::find($supplierId);
        if (!$supplier) return;

        $purchases = \App\Models\ProductPurchase::where('supplier_id', $supplierId)
            ->whereIn('purchase_status', ['confirmed', 'received', 'completed'])
            ->orderBy('purchase_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        foreach ($purchases as $purchase) {
            $allocatedFromTable = (float) \App\Models\SupplierPaymentAllocation::where('product_purchase_id', $purchase->id)->sum('allocated_amount');
            $directPayments = (float) SupplierPayment::where('purchase_id', $purchase->id)->whereDoesntHave('allocations')->sum('amount');

            $totalPaidForPurchase = $allocatedFromTable + $directPayments;
            $paidAmount = min((float) $purchase->grand_total, $totalPaidForPurchase);
            $dueAmount = max(0.0, (float) $purchase->grand_total - $paidAmount);

            $paymentStatus = 'unpaid';
            if ($paidAmount >= (float) $purchase->grand_total) {
                $paymentStatus = 'paid';
            } elseif ($paidAmount > 0) {
                $paymentStatus = 'partially_paid';
            }

            $purchase->update([
                'paid_amount' => $paidAmount,
                'due_amount' => $dueAmount,
                'payment_status' => $paymentStatus,
            ]);
        }
    }

    public function getSupplierLedger(Supplier $supplier, ?string $startDate = null, ?string $endDate = null): array
    {
        $entries = collect();

        // 1. Opening balance entry
        $opBal = $supplier->opening_balance_payable;
        $entries->push([
            'date' => $supplier->created_at ? $supplier->created_at->format('Y-m-d') : '2026-01-01',
            'type' => 'Opening Balance',
            'reference' => $supplier->supplier_code,
            'description' => 'Initial Supplier Opening Balance (' . ucfirst($supplier->opening_balance_type) . ')',
            'debit' => $supplier->opening_balance_type === 'receivable' ? (float) $supplier->opening_balance : 0.0,
            'credit' => $supplier->opening_balance_type === 'payable' ? (float) $supplier->opening_balance : 0.0,
            'timestamp' => $supplier->created_at ? $supplier->created_at->timestamp : 0,
        ]);

        // 2. Product Purchases (Credit / Increases Payable)
        $purchasesQuery = $supplier->purchases()
            ->whereIn('purchase_status', ['confirmed', 'received']);
            
        if ($startDate) {
            $purchasesQuery->where('purchase_date', '>=', $startDate);
        }
        if ($endDate) {
            $purchasesQuery->where('purchase_date', '<=', $endDate);
        }

        foreach ($purchasesQuery->get() as $purchase) {
            $entries->push([
                'date' => $purchase->purchase_date ? $purchase->purchase_date->format('Y-m-d') : $purchase->created_at->format('Y-m-d'),
                'type' => 'Purchase Invoice',
                'reference' => $purchase->purchase_number,
                'description' => "Product Purchase Bill #{$purchase->purchase_number}" . ($purchase->supplier_invoice_number ? " (Inv: {$purchase->supplier_invoice_number})" : ''),
                'debit' => 0.0,
                'credit' => (float) $purchase->grand_total,
                'timestamp' => strtotime($purchase->purchase_date),
            ]);
        }

        // 3. Supplier Payments (Debit / Decreases Payable)
        $paymentsQuery = $supplier->payments();
        if ($startDate) {
            $paymentsQuery->where('payment_date', '>=', $startDate);
        }
        if ($endDate) {
            $paymentsQuery->where('payment_date', '<=', $endDate);
        }

        foreach ($paymentsQuery->get() as $payment) {
            $entries->push([
                'date' => $payment->payment_date ? $payment->payment_date->format('Y-m-d') : $payment->created_at->format('Y-m-d'),
                'type' => 'Supplier Payment',
                'reference' => $payment->payment_number,
                'description' => "Payment via " . strtoupper(str_replace('_', ' ', $payment->payment_method)) . ($payment->reference_number ? " (Ref: {$payment->reference_number})" : ''),
                'debit' => (float) $payment->amount,
                'credit' => 0.0,
                'timestamp' => strtotime($payment->payment_date),
            ]);
        }

        // Sort chronologically
        $sorted = $entries->sortBy('timestamp')->values();

        // Compute running balance
        $runningBalance = 0.0;
        $processedLedger = $sorted->map(function ($row) use (&$runningBalance) {
            // Payable increases with Credit, decreases with Debit
            $runningBalance += ($row['credit'] - $row['debit']);
            $row['balance'] = $runningBalance;
            return $row;
        });

        return [
            'entries' => $processedLedger,
            'closing_balance' => $runningBalance,
        ];
    }

    public function getSupplierProducts(Supplier $supplier): Collection
    {
        $purchaseIds = $supplier->purchases()
            ->whereIn('purchase_status', ['confirmed', 'received'])
            ->pluck('id');

        return DB::table('product_purchase_items')
            ->join('products', 'product_purchase_items.product_id', '=', 'products.id')
            ->whereIn('product_purchase_items.purchase_id', $purchaseIds)
            ->select(
                'products.id',
                'products.sku',
                'products.name',
                'products.brand',
                'products.category',
                DB::raw('SUM(product_purchase_items.quantity) as total_qty_purchased'),
                DB::raw('AVG(product_purchase_items.unit_purchase_cost) as avg_unit_cost'),
                DB::raw('SUM(product_purchase_items.line_total) as total_spent'),
                DB::raw('MAX(product_purchase_items.created_at) as last_purchased_at')
            )
            ->groupBy('products.id', 'products.sku', 'products.name', 'products.brand', 'products.category')
            ->get();
    }

    public function getSupplierDashboardMetrics(?int $companyId = null): array
    {
        $user = Auth::user();
        $targetCompanyId = $companyId ?: ($user && !$user->isSuperAdmin() ? $user->company_id : null);

        $supplierQuery = Supplier::query();
        if ($targetCompanyId) {
            $supplierQuery->where('company_id', $targetCompanyId);
        }

        $totalSuppliers = (clone $supplierQuery)->count();
        $activeSuppliers = (clone $supplierQuery)->where('status', 'active')->count();

        $newThisMonth = (clone $supplierQuery)
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->count();

        $allSuppliers = $supplierQuery->with(['purchases', 'payments'])->get();

        $totalPurchaseValue = 0.0;
        $totalOutstanding = 0.0;

        foreach ($allSuppliers as $sup) {
            $totalPurchaseValue += $sup->total_purchase;
            $totalOutstanding += $sup->outstanding_payable;
        }

        $paymentQuery = SupplierPayment::query();
        if ($targetCompanyId) {
            $paymentQuery->where('company_id', $targetCompanyId);
        }

        $paymentsThisMonth = $paymentQuery
            ->whereYear('payment_date', now()->year)
            ->whereMonth('payment_date', now()->month)
            ->sum('amount');

        return [
            'total_suppliers' => $totalSuppliers,
            'active_suppliers' => $activeSuppliers,
            'total_purchase_value' => $totalPurchaseValue,
            'total_outstanding' => $totalOutstanding,
            'payments_this_month' => (float) $paymentsThisMonth,
            'new_suppliers_this_month' => $newThisMonth,
        ];
    }

    protected function generatePaymentNumber(int $companyId): string
    {
        $prefix = 'PAY-SUP-' . date('Y') . '-';
        $latest = SupplierPayment::where('company_id', $companyId)
            ->where('payment_number', 'like', $prefix . '%')
            ->withTrashed()
            ->orderBy('id', 'desc')
            ->first();

        if ($latest) {
            $sequenceStr = str_replace($prefix, '', $latest->payment_number);
            $sequence = (int) $sequenceStr + 1;
        } else {
            $sequence = 1;
        }

        return $prefix . str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);
    }
}
