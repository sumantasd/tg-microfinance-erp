<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\InventoryStock;
use App\Models\InventoryStockMovement;
use App\Models\LoanAccount;
use App\Models\LoanApplication;
use App\Repositories\InventoryRepositoryInterface;
use App\Repositories\LoanAccountRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LoanAccountService
{
    public function __construct(
        protected LoanAccountRepositoryInterface $accountRepository,
        protected InventoryRepositoryInterface $inventoryRepository,
        protected ActivityLogService $activityLogService
    ) {}

    public function getPaginatedAccounts(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $user = Auth::user();
        if ($user && !$user->isSuperAdmin()) {
            $filters['company_id'] = $user->company_id;
            if ($user->branch_id && !$user->isCompanyAdmin() && empty($filters['branch_id'])) {
                $filters['branch_id'] = $user->branch_id;
            }
        }

        return $this->accountRepository->getPaginatedAccounts($filters, $perPage);
    }

    public function getAccountById(int $id): ?LoanAccount
    {
        return $this->accountRepository->findById($id);
    }

    /**
     * Sanction Loan Account from Approved Application
     * CRITICAL FINANCIAL RULE: Down Payment MUST NOT be treated as principal.
     * Interest and EMI are calculated ONLY on the Financed Amount (sanctioned_amount).
     */
    public function sanctionLoanFromApplication(
        LoanApplication|int $application,
        float $downPaymentAmount = 0.00,
        float $otherChargesAmount = 0.00,
        ?string $sanctionDate = null
    ): LoanAccount {
        $app = is_numeric($application) ? LoanApplication::findOrFail($application) : $application;

        if ($app->status !== 'approved') {
            throw ValidationException::withMessages(['loan_application_id' => 'Only approved loan applications can be sanctioned into loan accounts.']);
        }

        $existingAccount = LoanAccount::where('loan_application_id', $app->id)->first();
        if ($existingAccount) {
            throw ValidationException::withMessages(['loan_application_id' => "Loan account '{$existingAccount->loan_number}' already exists for this application."]);
        }

        return DB::transaction(function () use ($app, $downPaymentAmount, $otherChargesAmount, $sanctionDate) {
            $productPrice = 0.00;
            $sanctionedPrincipal = (float) ($app->approved_amount ?? $app->requested_amount);

            if ($app->loan_type === 'product') {
                $productPrice = (float) $app->products()->sum('total_value');
                if ($productPrice <= 0) {
                    $productPrice = $sanctionedPrincipal;
                }
                // Sanctioned Principal = Product Price - Down Payment
                $sanctionedPrincipal = round(max(0, $productPrice - $downPaymentAmount), 2);
            }

            if ($sanctionedPrincipal <= 0) {
                throw ValidationException::withMessages(['down_payment_amount' => 'Down payment cannot equal or exceed the total product price / loan value.']);
            }

            // Generate EMI Schedule based ONLY on sanctionedPrincipal!
            $sDate = $sanctionDate ? Carbon::parse($sanctionDate) : now();
            $scheduleData = $this->calculateRepaymentSchedule(
                $sanctionedPrincipal,
                $app->tenure_months,
                $app->repayment_frequency,
                $app->interest_type,
                $app->interest_rate_per_annum,
                $sDate
            );

            $loanNumber = $this->accountRepository->generateLoanNumber($app->branch_id);

            $masterData = [
                'loan_number' => $loanNumber,
                'company_id' => $app->company_id,
                'branch_id' => $app->branch_id,
                'loan_application_id' => $app->id,
                'customer_id' => $app->customer_id,
                'customer_group_id' => $app->customer_group_id,
                'loan_scheme_id' => $app->loan_scheme_id,
                'loan_type' => $app->loan_type,
                'borrower_type' => $app->borrower_type,
                'product_price_amount' => $productPrice,
                'down_payment_amount' => $downPaymentAmount,
                'sanctioned_amount' => $sanctionedPrincipal,
                'disbursed_amount' => 0.00,
                'tenure_months' => $app->tenure_months,
                'repayment_frequency' => $app->repayment_frequency,
                'interest_type' => $app->interest_type,
                'interest_rate_per_annum' => $app->interest_rate_per_annum,
                'processing_fee_percentage' => $app->processing_fee_percentage,
                'processing_fee_amount' => $app->processing_fee_amount,
                'insurance_fee_percentage' => $app->insurance_fee_percentage,
                'insurance_fee_amount' => $app->insurance_fee_amount,
                'other_charges_amount' => $otherChargesAmount,
                'total_interest_amount' => $scheduleData['total_interest'],
                'total_repayment_amount' => $scheduleData['total_repayment'],
                'principal_outstanding' => $sanctionedPrincipal,
                'interest_outstanding' => $scheduleData['total_interest'],
                'fee_outstanding' => round($app->processing_fee_amount + $app->insurance_fee_amount + $otherChargesAmount, 2),
                'penalty_outstanding' => 0.00,
                'total_outstanding' => round($sanctionedPrincipal + $scheduleData['total_interest'] + $app->processing_fee_amount + $app->insurance_fee_amount + $otherChargesAmount, 2),
                'status' => 'sanctioned',
                'sanction_date' => $sDate->toDateString(),
                'maturity_date' => $scheduleData['maturity_date'],
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ];

            $membersData = [];
            if ($app->borrower_type === 'group' && $app->members->count() > 0) {
                $ratio = $sanctionedPrincipal / max(1, $app->requested_amount);
                foreach ($app->members as $m) {
                    $mSanctioned = round(($m->approved_amount ?? $m->requested_amount) * $ratio, 2);
                    $membersData[] = [
                        'customer_id' => $m->customer_id,
                        'sanctioned_amount' => $mSanctioned,
                        'down_payment_amount' => 0.00,
                        'principal_outstanding' => $mSanctioned,
                        'interest_outstanding' => 0.00,
                        'total_outstanding' => $mSanctioned,
                    ];
                }
            }

            $loanAccount = $this->accountRepository->createLoanAccount($masterData, $membersData, $scheduleData['installments']);

            if ($downPaymentAmount > 0) {
                $this->accountRepository->recordDownPayment($loanAccount, [
                    'customer_id' => $app->customer_id,
                    'amount' => $downPaymentAmount,
                    'payment_date' => $sDate->toDateString(),
                    'payment_method' => 'cash',
                    'received_by' => Auth::id(),
                    'remarks' => 'Initial down payment at loan sanction',
                ]);
            }

            $this->activityLogService->log('loan_sanctioned', $loanAccount);
            $this->activityLogService->log('emi_schedule_generated', $loanAccount);

            return $loanAccount;
        });
    }

    public function recordDownPayment(LoanAccount $loanAccount, float $amount, string $paymentMethod = 'cash', ?string $refNo = null, ?string $remarks = null): LoanAccount
    {
        if ($amount <= 0) {
            throw ValidationException::withMessages(['amount' => 'Down payment amount must be greater than zero.']);
        }

        return DB::transaction(function () use ($loanAccount, $amount, $paymentMethod, $refNo, $remarks) {
            $updated = $this->accountRepository->recordDownPayment($loanAccount, [
                'customer_id' => $loanAccount->customer_id,
                'amount' => $amount,
                'payment_date' => now()->toDateString(),
                'payment_method' => $paymentMethod,
                'reference_number' => $refNo,
                'received_by' => Auth::id(),
                'remarks' => $remarks,
            ]);

            $this->activityLogService->log('down_payment_received', $updated);
            return $updated;
        });
    }

    /**
     * Fulfill Product Loan & Issue Physical Inventory Stock
     * ATOMICALLY DEDUCTS PHYSICAL INVENTORY STOCK!
     */
    public function issueProductLoan(LoanAccount $loanAccount, ?string $remarks = null): LoanAccount
    {
        if ($loanAccount->loan_type !== 'product') {
            throw ValidationException::withMessages(['loan_type' => 'Only product loans can be fulfilled via product issue.']);
        }

        if (in_array($loanAccount->status, ['active', 'closed', 'cancelled'])) {
            throw ValidationException::withMessages(['status' => "Loan account is already in status '{$loanAccount->status}'."]);
        }

        return DB::transaction(function () use ($loanAccount, $remarks) {
            $application = $loanAccount->application;
            if (!$application || $application->products->count() === 0) {
                throw ValidationException::withMessages(['products' => 'No product items associated with this loan application.']);
            }

            // Verify and deduct physical inventory stock for each product line item!
            foreach ($application->products as $item) {
                $stock = InventoryStock::where('branch_id', $loanAccount->branch_id)
                    ->where('product_id', $item->product_id)
                    ->lockForUpdate()
                    ->first();

                if (!$stock || $stock->available_stock < $item->quantity) {
                    $avail = $stock ? $stock->available_stock : 0;
                    throw ValidationException::withMessages([
                        'products' => "Insufficient stock in branch inventory for product '{$item->product_name_snapshot}'. Available: {$avail}, Required: {$item->quantity}.",
                    ]);
                }

                $stockBefore = $stock->current_stock;
                $stock->current_stock -= $item->quantity;
                $stock->last_restocked_at = now();
                $stock->save();

                // Log immutable stock movement with product_loan_issue type!
                $seq = str_pad(DB::table('inventory_stock_movements')->max('id') + 1, 5, '0', STR_PAD_LEFT);
                $branchCode = $loanAccount->branch->code ?? 'BR';
                $movementCode = "STK-{$branchCode}-" . date('Y') . "-{$seq}";

                InventoryStockMovement::create([
                    'movement_code' => $movementCode,
                    'company_id' => $loanAccount->company_id,
                    'branch_id' => $loanAccount->branch_id,
                    'product_id' => $item->product_id,
                    'movement_type' => 'product_loan_issue',
                    'quantity' => -$item->quantity,
                    'stock_before' => $stockBefore,
                    'stock_after' => $stock->current_stock,
                    'unit_price' => $item->unit_price_snapshot,
                    'total_value' => round(abs($item->quantity) * $item->unit_price_snapshot, 2),
                    'reference_type' => 'loan_account',
                    'reference_id' => $loanAccount->id,
                    'created_by' => Auth::id(),
                    'remarks' => $remarks ?? "Product issue for Product Loan #{$loanAccount->loan_number}",
                ]);
            }

            $disbNo = $this->accountRepository->generateDisbursementNumber($loanAccount->branch_id);
            $this->accountRepository->recordDisbursement($loanAccount, [
                'disbursement_number' => $disbNo,
                'disbursement_date' => now()->toDateString(),
                'disbursed_amount' => $loanAccount->sanctioned_amount,
                'payment_method' => 'product_fulfillment',
                'disbursed_by' => Auth::id(),
                'remarks' => $remarks ?? "Physical product fulfillment for Product Loan #{$loanAccount->loan_number}",
            ]);

            $this->activityLogService->log('product_issued_against_loan', $loanAccount);
            $this->activityLogService->log('loan_disbursed', $loanAccount);

            return $loanAccount->fresh();
        });
    }

    /**
     * Disburse Cash Loan
     */
    public function disburseCashLoan(LoanAccount $loanAccount, string $paymentMethod = 'cash', ?string $refNo = null, ?string $remarks = null): LoanAccount
    {
        if ($loanAccount->loan_type !== 'cash') {
            throw ValidationException::withMessages(['loan_type' => 'Only cash loans can be disbursed via cash/bank payout.']);
        }

        if (in_array($loanAccount->status, ['active', 'closed', 'cancelled'])) {
            throw ValidationException::withMessages(['status' => "Loan account is already in status '{$loanAccount->status}'."]);
        }

        return DB::transaction(function () use ($loanAccount, $paymentMethod, $refNo, $remarks) {
            $disbNo = $this->accountRepository->generateDisbursementNumber($loanAccount->branch_id);

            $updated = $this->accountRepository->recordDisbursement($loanAccount, [
                'disbursement_number' => $disbNo,
                'disbursement_date' => now()->toDateString(),
                'disbursed_amount' => $loanAccount->sanctioned_amount,
                'payment_method' => $paymentMethod,
                'reference_number' => $refNo,
                'disbursed_by' => Auth::id(),
                'remarks' => $remarks ?? "Cash disbursement for Loan #{$loanAccount->loan_number}",
            ]);

            $this->activityLogService->log('loan_disbursed', $updated);
            return $updated;
        });
    }

    /**
     * Calculate Repayment Installment Schedule
     * Supports Flat and Reducing Balance interest.
     */
    public function calculateRepaymentSchedule(
        float $principal,
        int $tenureMonths,
        string $frequency,
        string $interestType,
        float $annualInterestRate,
        Carbon $startDate
    ): array {
        $numPeriods = match($frequency) {
            'weekly' => $tenureMonths * 4,
            'bi_weekly' => $tenureMonths * 2,
            default => $tenureMonths, // monthly
        };

        $periodsPerYear = match($frequency) {
            'weekly' => 52,
            'bi_weekly' => 26,
            default => 12,
        };

        $installments = [];
        $totalInterest = 0.00;
        $currentDate = $startDate->copy();
        $openingPrincipal = round($principal, 0);

        if ($interestType === 'flat') {
            // Total Interest calculation (whole rupees)
            $totalInterest = round($principal * ($annualInterestRate / 100) * ($tenureMonths / 12), 0);
            $baseInterest = round($totalInterest / $numPeriods, 0);
            $basePrincipal = round($principal / $numPeriods, 0);

            $accumulatedPrincipal = 0.00;
            $accumulatedInterest = 0.00;

            for ($i = 1; $i <= $numPeriods; $i++) {
                $currentDate = $this->getNextDueDate($currentDate, $frequency);

                if ($i === $numPeriods) {
                    $instPrincipal = round(max(0, $principal - $accumulatedPrincipal), 0);
                    $instInterest = round(max(0, $totalInterest - $accumulatedInterest), 0);
                } else {
                    $instPrincipal = min($openingPrincipal, $basePrincipal);
                    $instInterest = $baseInterest;
                }

                $accumulatedPrincipal += $instPrincipal;
                $accumulatedInterest += $instInterest;

                $closingPrincipal = max(0, round($openingPrincipal - $instPrincipal, 0));
                $instAmount = round($instPrincipal + $instInterest, 0);

                $installments[] = [
                    'installment_number' => $i,
                    'due_date' => $currentDate->toDateString(),
                    'opening_principal' => $openingPrincipal,
                    'principal_amount' => $instPrincipal,
                    'interest_amount' => $instInterest,
                    'fee_amount' => 0.00,
                    'penalty_amount' => 0.00,
                    'installment_amount' => $instAmount,
                    'closing_principal' => $closingPrincipal,
                    'status' => 'pending',
                ];

                $openingPrincipal = $closingPrincipal;
            }
        } else {
            // Reducing Balance EMI Formula
            $periodRate = ($annualInterestRate / 100) / $periodsPerYear;
            if ($periodRate > 0) {
                $emi = round(($principal * $periodRate * pow(1 + $periodRate, $numPeriods)) / (pow(1 + $periodRate, $numPeriods) - 1), 0);
            } else {
                $emi = round($principal / $numPeriods, 0);
            }

            $accumulatedPrincipal = 0.00;
            $accumulatedInterest = 0.00;

            for ($i = 1; $i <= $numPeriods; $i++) {
                $currentDate = $this->getNextDueDate($currentDate, $frequency);
                $rawInterest = round($openingPrincipal * $periodRate, 0);

                if ($i === $numPeriods) {
                    $instPrincipal = round($openingPrincipal, 0);
                    $instInterest = $rawInterest;
                } else {
                    $instInterest = $rawInterest;
                    $instPrincipal = round(min($openingPrincipal, max(0, $emi - $instInterest)), 0);
                }

                $accumulatedPrincipal += $instPrincipal;
                $accumulatedInterest += $instInterest;

                $closingPrincipal = max(0, round($openingPrincipal - $instPrincipal, 0));
                $instAmount = round($instPrincipal + $instInterest, 0);

                $installments[] = [
                    'installment_number' => $i,
                    'due_date' => $currentDate->toDateString(),
                    'opening_principal' => $openingPrincipal,
                    'principal_amount' => $instPrincipal,
                    'interest_amount' => $instInterest,
                    'fee_amount' => 0.00,
                    'penalty_amount' => 0.00,
                    'installment_amount' => $instAmount,
                    'closing_principal' => $closingPrincipal,
                    'status' => 'pending',
                ];

                $openingPrincipal = $closingPrincipal;
            }

            $totalInterest = $accumulatedInterest;
        }

        $totalInterest = round($totalInterest, 0);
        $totalRepayment = round($principal + $totalInterest, 0);
        $maturityDate = $currentDate->toDateString();

        return [
            'total_interest' => $totalInterest,
            'total_repayment' => $totalRepayment,
            'maturity_date' => $maturityDate,
            'installments' => $installments,
        ];
    }

    protected function getNextDueDate(Carbon $date, string $frequency): Carbon
    {
        $d = $date->copy();
        return match($frequency) {
            'weekly' => $d->addWeek(),
            'bi_weekly' => $d->addWeeks(2),
            default => $d->addMonth(),
        };
    }

    /**
     * Record Loan Repayment / EMI Collection & Recalculate Schedule
     * Waterfall Allocation: 1. Penalty -> 2. Fees -> 3. Interest -> 4. Principal
     */
    public function recordRepayment(
        LoanAccount $loanAccount,
        float $amount,
        string $paymentMethod = 'cash',
        ?string $refNo = null,
        string $adjustmentMode = 'reduce_tenure',
        ?string $remarks = null,
        ?string $paymentDate = null
    ): LoanAccount {
        if ($amount <= 0) {
            throw ValidationException::withMessages(['amount' => 'Repayment amount must be greater than zero.']);
        }

        if (in_array($loanAccount->status, ['closed', 'cancelled'])) {
            throw ValidationException::withMessages(['status' => "Loan account is already {$loanAccount->status}."]);
        }

        if ($amount > (float) $loanAccount->total_outstanding + 0.01) {
            $maxPay = number_format($loanAccount->total_outstanding, 2);
            throw ValidationException::withMessages(['amount' => "Repayment amount (₹{$amount}) cannot exceed total outstanding loan balance (₹{$maxPay})."]);
        }

        if ($refNo && \App\Models\LoanRepayment::where('loan_account_id', $loanAccount->id)->where('reference_number', $refNo)->exists()) {
            throw ValidationException::withMessages(['reference_number' => "A repayment transaction with reference number '{$refNo}' already exists for this loan account."]);
        }

        return DB::transaction(function () use ($loanAccount, $amount, $paymentMethod, $refNo, $adjustmentMode, $remarks, $paymentDate) {
            $pDate = $paymentDate ? Carbon::parse($paymentDate) : now();

            // 1. Waterfall Allocation
            $rem = $amount;

            // A. Penalty
            $penaltyPaid = min($rem, (float) $loanAccount->penalty_outstanding);
            $rem -= $penaltyPaid;

            // B. Fees
            $feePaid = min($rem, (float) $loanAccount->fee_outstanding);
            $rem -= $feePaid;

            // C. Interest
            $interestPaid = min($rem, (float) $loanAccount->interest_outstanding);
            $rem -= $interestPaid;

            // D. Principal
            $principalPaid = min($rem, (float) $loanAccount->principal_outstanding);
            $rem -= $principalPaid;

            // Allocate across installments
            $allocPenalty = $penaltyPaid;
            $allocFee = $feePaid;
            $allocInterest = $interestPaid;
            $allocPrincipal = $principalPaid;

            $installments = $loanAccount->installments()->orderBy('installment_number', 'asc')->get();
            foreach ($installments as $inst) {
                if ($inst->status === 'paid') continue;

                $instPenDue = max(0, $inst->penalty_amount - $inst->penalty_paid);
                $pPenAlloc = min($allocPenalty, $instPenDue);
                $inst->penalty_paid += $pPenAlloc;
                $allocPenalty -= $pPenAlloc;

                $instFeeDue = max(0, $inst->fee_amount - $inst->fee_paid);
                $pFeeAlloc = min($allocFee, $instFeeDue);
                $inst->fee_paid += $pFeeAlloc;
                $allocFee -= $pFeeAlloc;

                $instIntDue = max(0, $inst->interest_amount - $inst->interest_paid);
                $pIntAlloc = min($allocInterest, $instIntDue);
                $inst->interest_paid += $pIntAlloc;
                $allocInterest -= $pIntAlloc;

                $instPrinDue = max(0, $inst->principal_amount - $inst->principal_paid);
                $pPrinAlloc = min($allocPrincipal, $instPrinDue);
                $inst->principal_paid += $pPrinAlloc;
                $allocPrincipal -= $pPrinAlloc;

                $inst->total_paid = round($inst->penalty_paid + $inst->fee_paid + $inst->interest_paid + $inst->principal_paid, 2);

                if ($inst->total_paid >= $inst->installment_amount - 0.01) {
                    $inst->status = 'paid';
                    $inst->paid_at = $pDate;
                } else if ($inst->total_paid > 0) {
                    $inst->status = 'partial';
                }
                $inst->save();
            }

            // Create immutable Repayment Receipt record
            $rcptNo = $this->accountRepository->generateReceiptNumber($loanAccount->branch_id);
            $repayment = \App\Models\LoanRepayment::create([
                'receipt_number' => $rcptNo,
                'loan_account_id' => $loanAccount->id,
                'customer_id' => $loanAccount->customer_id,
                'payment_date' => $pDate->toDateString(),
                'amount' => $amount,
                'payment_method' => $paymentMethod,
                'reference_number' => $refNo,
                'penalty_paid' => $penaltyPaid,
                'fee_paid' => $feePaid,
                'interest_paid' => $interestPaid,
                'principal_paid' => $principalPaid,
                'adjustment_mode' => $adjustmentMode,
                'received_by' => Auth::id(),
                'remarks' => $remarks,
            ]);

            // Update Loan Account Outstanding Balances
            $newPrincipalOut = max(0, round((float) $loanAccount->principal_outstanding - $principalPaid, 2));
            $newInterestOut = max(0, round((float) $loanAccount->interest_outstanding - $interestPaid, 2));
            $newFeeOut = max(0, round((float) $loanAccount->fee_outstanding - $feePaid, 2));
            $newPenaltyOut = max(0, round((float) $loanAccount->penalty_outstanding - $penaltyPaid, 2));
            $newTotalOut = round($newPrincipalOut + $newInterestOut + $newFeeOut + $newPenaltyOut, 2);

            $newStatus = $loanAccount->status;
            if ($newTotalOut <= 0.01) {
                $newStatus = 'closed';
            } else if ($loanAccount->status === 'sanctioned' || $loanAccount->status === 'ready_for_disbursement') {
                $newStatus = 'active';
            }

            $loanAccount->update([
                'principal_outstanding' => $newPrincipalOut,
                'interest_outstanding' => $newInterestOut,
                'fee_outstanding' => $newFeeOut,
                'penalty_outstanding' => $newPenaltyOut,
                'total_outstanding' => $newTotalOut,
                'status' => $newStatus,
            ]);

            // Recalculate Future Schedule if extra principal was paid and loan is still active
            if ($newStatus !== 'closed' && $principalPaid > 0) {
                $this->recalculateFutureSchedule($loanAccount->fresh(), $adjustmentMode);
            }

            $this->activityLogService->log('loan_repayment_received', $loanAccount);
            return $loanAccount->fresh(['repayments', 'installments']);
        });
    }

    /**
     * Recalculate remaining schedule for active loan
     */
    protected function recalculateFutureSchedule(LoanAccount $loanAccount, string $adjustmentMode): void
    {
        $pendingInstallments = $loanAccount->installments()
            ->where('status', '!=', 'paid')
            ->orderBy('installment_number', 'asc')
            ->get();

        if ($pendingInstallments->isEmpty()) return;

        $remPrincipal = (float) $loanAccount->principal_outstanding;

        if ($adjustmentMode === 'reduce_tenure') {
            // Keep standard EMI amount, reduce number of future installments
            $stdEmi = (float) $pendingInstallments->first()->installment_amount;
            if ($stdEmi <= 0) return;

            $opening = $remPrincipal;
            $keptIds = [];

            foreach ($pendingInstallments as $inst) {
                if ($opening <= 0.01) {
                    $inst->delete();
                    continue;
                }

                $inst->opening_principal = round($opening, 2);
                $pComp = min($opening, (float) $inst->principal_amount);
                if ($pComp <= 0) $pComp = min($opening, $stdEmi);

                $inst->principal_amount = round($pComp, 2);
                $inst->closing_principal = max(0, round($opening - $pComp, 2));
                $inst->installment_amount = round($inst->principal_amount + $inst->interest_amount, 2);
                $inst->save();

                $keptIds[] = $inst->id;
                $opening = $inst->closing_principal;
            }

            // Update maturity date to the due date of the last kept installment
            $lastInst = $loanAccount->installments()->whereIn('id', $keptIds)->orderBy('installment_number', 'desc')->first();
            if ($lastInst) {
                $loanAccount->update(['maturity_date' => $lastInst->due_date]);
            }
        } else if ($adjustmentMode === 'reduce_emi') {
            // Keep remaining tenure, recalculate future EMI amounts
            $count = $pendingInstallments->count();
            if ($count === 0) return;

            $newEmiPrincipal = round($remPrincipal / $count, 2);
            $opening = $remPrincipal;

            foreach ($pendingInstallments as $idx => $inst) {
                $inst->opening_principal = round($opening, 2);
                if ($idx === $count - 1) {
                    $pComp = round($opening, 2);
                } else {
                    $pComp = round($newEmiPrincipal, 2);
                }
                $inst->principal_amount = $pComp;
                $inst->closing_principal = max(0, round($opening - $pComp, 2));
                $inst->installment_amount = round($inst->principal_amount + $inst->interest_amount, 2);
                $inst->save();

                $opening = $inst->closing_principal;
            }
        }
    }
}
