<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\LoanApplication;
use App\Models\LoanScheme;
use App\Models\Product;
use App\Repositories\InventoryRepositoryInterface;
use App\Repositories\LoanApplicationRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LoanApplicationService
{
    public function __construct(
        protected LoanApplicationRepositoryInterface $applicationRepository,
        protected InventoryRepositoryInterface $inventoryRepository,
        protected ActivityLogService $activityLogService
    ) {}

    public function getPaginatedApplications(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $user = Auth::user();
        if ($user && !$user->isSuperAdmin()) {
            $filters['company_id'] = $user->company_id;
            if ($user->branch_id && !$user->isCompanyAdmin() && empty($filters['branch_id'])) {
                $filters['branch_id'] = $user->branch_id;
            }
        }

        return $this->applicationRepository->getPaginatedApplications($filters, $perPage);
    }

    public function getApplicationById(int $id): ?LoanApplication
    {
        return $this->applicationRepository->findById($id);
    }

    public function createApplication(array $data, array $members = [], array $products = []): LoanApplication
    {
        return DB::transaction(function () use ($data, $members, $products) {
            $branch = Branch::findOrFail($data['branch_id']);
            $user = Auth::user();

            if ($user && !$user->isSuperAdmin()) {
                if ($user->company_id && $user->company_id !== $branch->company_id) {
                    throw ValidationException::withMessages(['branch_id' => 'Selected branch does not belong to your company.']);
                }
            }

            // Validate Loan Scheme & Terms
            $scheme = LoanScheme::findOrFail($data['loan_scheme_id']);
            if (!$scheme->is_active) {
                throw ValidationException::withMessages(['loan_scheme_id' => 'Selected loan scheme is not active.']);
            }

            $this->validateSchemeCompatibility($scheme, $data['loan_type'], $data['borrower_type']);

            $requestedAmount = (float) $data['requested_amount'];
            $tenureMonths = (int) ($data['tenure_months'] ?? $scheme->min_tenure_months);

            if ($requestedAmount < $scheme->min_amount || $requestedAmount > $scheme->max_amount) {
                throw ValidationException::withMessages([
                    'requested_amount' => "Requested amount ₹" . number_format($requestedAmount, 2) . " must be between ₹" . number_format($scheme->min_amount, 2) . " and ₹" . number_format($scheme->max_amount, 2) . " for scheme '{$scheme->name}'.",
                ]);
            }

            if ($tenureMonths < $scheme->min_tenure_months || $tenureMonths > $scheme->max_tenure_months) {
                throw ValidationException::withMessages([
                    'tenure_months' => "Tenure {$tenureMonths} months must be between {$scheme->min_tenure_months} and {$scheme->max_tenure_months} months for scheme '{$scheme->name}'.",
                ]);
            }

            // Borrower Type Validation
            $customerId = null;
            $customerGroupId = null;
            $membersData = [];

            if ($data['borrower_type'] === 'individual') {
                if (empty($data['customer_id'])) {
                    throw ValidationException::withMessages(['customer_id' => 'Customer selection is required for individual loans.']);
                }
                $customer = Customer::findOrFail($data['customer_id']);
                if ($customer->branch_id !== $branch->id && $customer->company_id !== $branch->company_id) {
                    throw ValidationException::withMessages(['customer_id' => 'Selected customer does not belong to this branch/company.']);
                }
                if ($customer->status !== 'active') {
                    throw ValidationException::withMessages(['customer_id' => "Customer '{$customer->full_name}' is not active ({$customer->status})."]);
                }
                $customerId = $customer->id;
            } else { // Group Loan
                if (empty($data['customer_group_id'])) {
                    throw ValidationException::withMessages(['customer_group_id' => 'Customer Group selection is required for group loans.']);
                }
                $group = CustomerGroup::with('members.customer')->findOrFail($data['customer_group_id']);
                if ($group->branch_id !== $branch->id && $group->company_id !== $branch->company_id) {
                    throw ValidationException::withMessages(['customer_group_id' => 'Selected customer group does not belong to this branch/company.']);
                }
                if ($group->status !== 'active') {
                    throw ValidationException::withMessages(['customer_group_id' => "Customer group '{$group->name}' is not active."]);
                }
                $customerGroupId = $group->id;

                // Process Member Allocation
                if (empty($members)) {
                    throw ValidationException::withMessages(['members' => 'Group member allocation is required for group loans.']);
                }

                $memberTotal = 0;
                foreach ($members as $m) {
                    $mQty = (float) $m['requested_amount'];
                    if ($mQty <= 0) {
                        throw ValidationException::withMessages(['members' => 'Each group member requested amount must be greater than zero.']);
                    }
                    $memberTotal += $mQty;
                    $membersData[] = [
                        'customer_id' => $m['customer_id'],
                        'requested_amount' => $mQty,
                        'approved_amount' => null,
                        'remarks' => $m['remarks'] ?? null,
                    ];
                }

                if (abs($memberTotal - $requestedAmount) > 0.01) {
                    throw ValidationException::withMessages([
                        'requested_amount' => "Sum of group member allocations (₹" . number_format($memberTotal, 2) . ") must equal application total requested amount (₹" . number_format($requestedAmount, 2) . ").",
                    ]);
                }
            }

            // Product Loan Items Validation
            $productsData = [];
            if ($data['loan_type'] === 'product') {
                if (empty($products)) {
                    throw ValidationException::withMessages(['products' => 'At least one product line item is required for product loans.']);
                }

                $productTotal = 0;
                foreach ($products as $p) {
                    $product = Product::findOrFail($p['product_id']);
                    if (!$product->is_active) {
                        throw ValidationException::withMessages(['products' => "Product '{$product->name}' is inactive."]);
                    }

                    if (!empty($p['category_id']) && $product->category_id && (int) $product->category_id !== (int) $p['category_id']) {
                        throw ValidationException::withMessages(['products' => "Product '{$product->name}' does not belong to the selected category."]);
                    }

                    $qty = (int) $p['quantity'];
                    if ($qty <= 0) {
                        throw ValidationException::withMessages(['products' => "Quantity for product '{$product->name}' must be greater than zero."]);
                    }

                    $unitPrice = isset($p['unit_price']) ? (float) $p['unit_price'] : (float) $product->unit_price;
                    $lineTotal = round($qty * $unitPrice, 2);
                    $productTotal += $lineTotal;

                    // Check stock availability in Branch Inventory (Without deducting stock!)
                    $stock = $this->inventoryRepository->getStock($branch->id, $product->id);
                    $availableStock = $stock ? $stock->available_stock : 0;

                    if ($availableStock < $qty) {
                        throw ValidationException::withMessages([
                            'products' => "Insufficient branch inventory for product '{$product->name}'. Available in stock: {$availableStock}, Requested: {$qty}.",
                        ]);
                    }

                    $productsData[] = [
                        'product_id' => $product->id,
                        'product_sku_snapshot' => $product->sku,
                        'product_name_snapshot' => $product->name,
                        'quantity' => $qty,
                        'unit_price_snapshot' => $unitPrice,
                        'total_value' => $lineTotal,
                        'remarks' => $p['remarks'] ?? null,
                    ];
                }
            }

            // Calculate fees from Loan Scheme snapshots
            $proFeeRate = (float) $scheme->processing_fee_percentage;
            $proFeeAmount = round($requestedAmount * ($proFeeRate / 100), 2);
            $insFeeRate = (float) $scheme->insurance_fee_percentage;
            $insFeeAmount = round($requestedAmount * ($insFeeRate / 100), 2);

            $applicationNumber = $this->applicationRepository->generateApplicationNumber($branch->id);

            $masterData = [
                'application_number' => $applicationNumber,
                'company_id' => $branch->company_id,
                'branch_id' => $branch->id,
                'loan_type' => $data['loan_type'],
                'borrower_type' => $data['borrower_type'],
                'customer_id' => $customerId,
                'customer_group_id' => $customerGroupId,
                'loan_scheme_id' => $scheme->id,
                'application_date' => $data['application_date'] ?? now()->toDateString(),
                'requested_amount' => $requestedAmount,
                'approved_amount' => null,
                'tenure_months' => $tenureMonths,
                'repayment_frequency' => $data['repayment_frequency'] ?? $scheme->repayment_frequency,
                'interest_type' => $scheme->interest_type,
                'interest_rate_per_annum' => $scheme->interest_rate_per_annum,
                'processing_fee_percentage' => $proFeeRate,
                'processing_fee_amount' => $proFeeAmount,
                'insurance_fee_percentage' => $insFeeRate,
                'insurance_fee_amount' => $insFeeAmount,
                'late_fee_percentage' => $scheme->late_fee_percentage,
                'grace_period_days' => $scheme->grace_period_days,
                'purpose' => $data['purpose'] ?? null,
                'status' => 'draft',
                'remarks' => $data['remarks'] ?? null,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ];

            $application = $this->applicationRepository->createApplication($masterData, $membersData, $productsData);
            $this->activityLogService->log('loan_application_created', $application);

            return $application;
        });
    }

    public function updateApplication(LoanApplication $application, array $data, array $members = [], array $products = []): LoanApplication
    {
        if ($application->status !== 'draft') {
            throw ValidationException::withMessages(['status' => 'Only draft loan applications can be edited.']);
        }

        return DB::transaction(function () use ($application, $data, $members, $products) {
            $scheme = LoanScheme::findOrFail($data['loan_scheme_id'] ?? $application->loan_scheme_id);
            $requestedAmount = (float) ($data['requested_amount'] ?? $application->requested_amount);
            $tenureMonths = (int) ($data['tenure_months'] ?? $application->tenure_months);

            if ($requestedAmount < $scheme->min_amount || $requestedAmount > $scheme->max_amount) {
                throw ValidationException::withMessages([
                    'requested_amount' => "Requested amount ₹" . number_format($requestedAmount, 2) . " must be between ₹" . number_format($scheme->min_amount, 2) . " and ₹" . number_format($scheme->max_amount, 2) . ".",
                ]);
            }

            $proFeeRate = (float) $scheme->processing_fee_percentage;
            $proFeeAmount = round($requestedAmount * ($proFeeRate / 100), 2);
            $insFeeRate = (float) $scheme->insurance_fee_percentage;
            $insFeeAmount = round($requestedAmount * ($insFeeRate / 100), 2);

            $masterData = [
                'loan_scheme_id' => $scheme->id,
                'requested_amount' => $requestedAmount,
                'tenure_months' => $tenureMonths,
                'repayment_frequency' => $data['repayment_frequency'] ?? $application->repayment_frequency,
                'interest_type' => $scheme->interest_type,
                'interest_rate_per_annum' => $scheme->interest_rate_per_annum,
                'processing_fee_percentage' => $proFeeRate,
                'processing_fee_amount' => $proFeeAmount,
                'insurance_fee_percentage' => $insFeeRate,
                'insurance_fee_amount' => $insFeeAmount,
                'purpose' => $data['purpose'] ?? $application->purpose,
                'remarks' => $data['remarks'] ?? $application->remarks,
                'updated_by' => Auth::id(),
            ];

            // Re-process members if group
            $membersData = [];
            if ($application->borrower_type === 'group' && !empty($members)) {
                foreach ($members as $m) {
                    $membersData[] = [
                        'customer_id' => $m['customer_id'],
                        'requested_amount' => (float) $m['requested_amount'],
                        'remarks' => $m['remarks'] ?? null,
                    ];
                }
            }

            // Re-process products if product loan
            $productsData = [];
            if ($application->loan_type === 'product' && !empty($products)) {
                foreach ($products as $p) {
                    $product = Product::findOrFail($p['product_id']);
                    if (!empty($p['category_id']) && $product->category_id && (int) $product->category_id !== (int) $p['category_id']) {
                        throw ValidationException::withMessages(['products' => "Product '{$product->name}' does not belong to the selected category."]);
                    }
                    $qty = (int) $p['quantity'];
                    $unitPrice = isset($p['unit_price']) ? (float) $p['unit_price'] : (float) $product->unit_price;
                    $lineTotal = round($qty * $unitPrice, 2);

                    $productsData[] = [
                        'product_id' => $product->id,
                        'product_sku_snapshot' => $product->sku,
                        'product_name_snapshot' => $product->name,
                        'quantity' => $qty,
                        'unit_price_snapshot' => $unitPrice,
                        'total_value' => $lineTotal,
                    ];
                }
            }

            $updated = $this->applicationRepository->updateApplication($application, $masterData, $membersData, $productsData);
            $this->activityLogService->log('loan_application_updated', $updated);

            return $updated;
        });
    }

    public function submitApplication(LoanApplication $application): LoanApplication
    {
        if ($application->status !== 'draft') {
            throw ValidationException::withMessages(['status' => 'Only draft applications can be submitted.']);
        }

        return DB::transaction(function () use ($application) {
            $updated = $this->applicationRepository->updateStatus($application, 'submitted', [
                'submitted_at' => now(),
                'updated_by' => Auth::id(),
            ]);

            $this->activityLogService->log('loan_application_submitted', $updated);
            return $updated;
        });
    }

    public function startReview(LoanApplication $application): LoanApplication
    {
        if (!in_array($application->status, ['submitted', 'draft'])) {
            throw ValidationException::withMessages(['status' => 'Application must be submitted to start review.']);
        }

        return DB::transaction(function () use ($application) {
            $updated = $this->applicationRepository->updateStatus($application, 'under_review', [
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
                'updated_by' => Auth::id(),
            ]);

            $this->activityLogService->log('loan_application_review_started', $updated);
            return $updated;
        });
    }

    /**
     * Approve Loan Application
     * CRITICAL BUSINESS RULE: Does NOT deduct physical inventory stock or disburse cash in Phase 7.2.
     */
    public function approveApplication(LoanApplication $application, ?float $approvedAmount = null): LoanApplication
    {
        if (!in_array($application->status, ['submitted', 'under_review'])) {
            throw ValidationException::withMessages(['status' => 'Application must be submitted or under review to approve.']);
        }

        return DB::transaction(function () use ($application, $approvedAmount) {
            $finalApprovedAmount = $approvedAmount ?? $application->requested_amount;

            // Update member approved amounts proportionally for group loan
            if ($application->borrower_type === 'group' && $application->members->count() > 0) {
                $ratio = $finalApprovedAmount / $application->requested_amount;
                foreach ($application->members as $member) {
                    $memberApproved = round($member->requested_amount * $ratio, 2);
                    $member->update(['approved_amount' => $memberApproved]);
                }
            }

            $updated = $this->applicationRepository->updateStatus($application, 'approved', [
                'approved_amount' => $finalApprovedAmount,
                'approved_by' => Auth::id(),
                'approved_at' => now(),
                'updated_by' => Auth::id(),
            ]);

            $this->activityLogService->log('loan_application_approved', $updated);
            return $updated;
        });
    }

    public function rejectApplication(LoanApplication $application, string $reason): LoanApplication
    {
        if (!in_array($application->status, ['submitted', 'under_review'])) {
            throw ValidationException::withMessages(['status' => 'Cannot reject application in current status.']);
        }

        if (empty(trim($reason))) {
            throw ValidationException::withMessages(['rejection_reason' => 'A clear rejection reason is required to reject a loan application.']);
        }

        return DB::transaction(function () use ($application, $reason) {
            $updated = $this->applicationRepository->updateStatus($application, 'rejected', [
                'rejected_by' => Auth::id(),
                'rejected_at' => now(),
                'rejection_reason' => $reason,
                'updated_by' => Auth::id(),
            ]);

            $this->activityLogService->log('loan_application_rejected', $updated);
            return $updated;
        });
    }

    public function cancelApplication(LoanApplication $application): LoanApplication
    {
        if ($application->status === 'approved') {
            throw ValidationException::withMessages(['status' => 'Approved loan applications cannot be cancelled.']);
        }

        return DB::transaction(function () use ($application) {
            $updated = $this->applicationRepository->updateStatus($application, 'cancelled', [
                'cancelled_by' => Auth::id(),
                'cancelled_at' => now(),
                'updated_by' => Auth::id(),
            ]);

            $this->activityLogService->log('loan_application_cancelled', $updated);
            return $updated;
        });
    }

    protected function validateSchemeCompatibility(LoanScheme $scheme, string $loanType, string $borrowerType): void
    {
        if ($scheme->loan_type !== 'both' && $scheme->loan_type !== $loanType) {
            throw ValidationException::withMessages([
                'loan_scheme_id' => "Scheme '{$scheme->name}' only supports '{$scheme->loan_type}' loans, but '{$loanType}' loan was selected.",
            ]);
        }

        if ($scheme->applicant_type !== 'both' && $scheme->applicant_type !== $borrowerType) {
            throw ValidationException::withMessages([
                'loan_scheme_id' => "Scheme '{$scheme->name}' only supports '{$scheme->applicant_type}' applicants, but '{$borrowerType}' was selected.",
            ]);
        }
    }
}
