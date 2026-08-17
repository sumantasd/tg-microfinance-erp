<?php

namespace App\Services;

use App\Models\LoanAccount;
use App\Models\LoanInstallment;
use App\Models\LoanRepayment;
use App\Models\LoanSettlementRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LoanSettlementService
{
    public const TIMEZONE = 'Asia/Kolkata';

    protected OverdueService $overdueService;
    protected PenaltyService $penaltyService;
    protected AccountingService $accountingService;
    protected ActivityLogService $activityLogService;

    public function __construct(
        OverdueService $overdueService,
        PenaltyService $penaltyService,
        AccountingService $accountingService,
        ActivityLogService $activityLogService
    ) {
        $this->overdueService = $overdueService;
        $this->penaltyService = $penaltyService;
        $this->accountingService = $accountingService;
        $this->activityLogService = $activityLogService;
    }

    /**
     * Normalize As-Of Date in Asia/Kolkata start of day.
     */
    public function getNormalizedDate(?Carbon $asOfDate = null, ?string $asOfDateStr = null): Carbon
    {
        if ($asOfDate) {
            $str = $asOfDate->format('Y-m-d');
            return Carbon::createFromFormat('Y-m-d', $str, self::TIMEZONE)->startOfDay();
        }
        if ($asOfDateStr) {
            $str = substr($asOfDateStr, 0, 10);
            return Carbon::createFromFormat('Y-m-d', $str, self::TIMEZONE)->startOfDay();
        }
        return Carbon::now(self::TIMEZONE)->startOfDay();
    }

    /**
     * Validate Minimum Lock-In Tenure before foreclosure.
     */
    public function validateLockInPeriod(LoanAccount $loan, Carbon $asOfDate): array
    {
        $scheme = $loan->loanScheme;
        $minMonths = (int) ($scheme?->min_months_before_foreclosure ?? 0);

        if ($minMonths <= 0) {
            return [
                'is_allowed' => true,
                'min_months' => 0,
                'elapsed_months' => 0,
                'message' => 'No minimum lock-in period required.',
            ];
        }

        $disbursementDate = $loan->disbursement_date
            ? Carbon::parse($loan->disbursement_date, self::TIMEZONE)->startOfDay()
            : null;

        if (!$disbursementDate) {
            return [
                'is_allowed' => true,
                'min_months' => $minMonths,
                'elapsed_months' => 0,
                'message' => 'Disbursement date not set; lock-in check bypassed.',
            ];
        }

        $elapsedMonths = $disbursementDate->diffInMonths($asOfDate);

        if ($elapsedMonths < $minMonths) {
            return [
                'is_allowed' => false,
                'min_months' => $minMonths,
                'elapsed_months' => $elapsedMonths,
                'message' => "Foreclosure not allowed before {$minMonths} months. Only {$elapsedMonths} month(s) elapsed since disbursement.",
            ];
        }

        return [
            'is_allowed' => true,
            'min_months' => $minMonths,
            'elapsed_months' => $elapsedMonths,
            'message' => "Lock-in requirement satisfied ({$elapsedMonths} of {$minMonths} months elapsed).",
        ];
    }

    /**
     * Calculate Complete Pro-Rata Foreclosure Payoff Quote.
     */
    public function calculateForeclosure(LoanAccount $loan, ?Carbon $asOfDate = null): array
    {
        $asOf = $this->getNormalizedDate($asOfDate);
        $asOfStr = $asOf->format('Y-m-d');

        $principalOutstanding = round((float) $loan->principal_outstanding, 2);
        $feeOutstanding = round((float) $loan->fee_outstanding, 2);
        $penaltyOutstanding = round((float) $loan->penalty_outstanding, 2);

        // Partition installments into Elapsed (Earned) vs Future (Unearned)
        $installments = $loan->installments()->orderBy('installment_number', 'asc')->get();

        $accruedInterest = 0.00;
        $unearnedInterestRebate = 0.00;
        $elapsedInstallmentsCount = 0;
        $futureInstallmentsCount = 0;

        foreach ($installments as $inst) {
            $dueDateStr = $inst->due_date ? Carbon::parse($inst->due_date)->format('Y-m-d') : null;
            $unpaidInterest = max(0.00, round((float) ($inst->interest_amount - $inst->interest_paid), 2));

            if ($dueDateStr && $dueDateStr <= $asOfStr) {
                // Elapsed installment: interest is earned and due if unpaid
                $accruedInterest = round($accruedInterest + $unpaidInterest, 2);
                $elapsedInstallmentsCount++;
            } else {
                // Future installment: interest is unearned and should be rebated
                $unearnedInterestRebate = round($unearnedInterestRebate + $unpaidInterest, 2);
                $futureInstallmentsCount++;
            }
        }

        // Scheme Foreclosure Fee calculation
        $scheme = $loan->loanScheme;
        $foreclosureFee = 0.00;
        $feeType = $scheme?->foreclosure_fee_type ?? 'none';
        $feePct = (float) ($scheme?->foreclosure_fee_percentage ?? 0.00);
        $flatFee = (float) ($scheme?->foreclosure_flat_fee ?? 0.00);

        if ($scheme && ($scheme->allow_foreclosure ?? true)) {
            if ($feeType === 'percentage' && $feePct > 0) {
                $foreclosureFee = round($principalOutstanding * ($feePct / 100), 2);
            } elseif ($feeType === 'flat' && $flatFee > 0) {
                $foreclosureFee = round($flatFee, 2);
            }
        }

        $lockIn = $this->validateLockInPeriod($loan, $asOf);

        $grossPayoff = round(
            $principalOutstanding +
            $accruedInterest +
            $unearnedInterestRebate +
            $feeOutstanding +
            $penaltyOutstanding +
            $foreclosureFee,
            2
        );

        $finalSettlementAmount = round(
            $principalOutstanding +
            $accruedInterest +
            $feeOutstanding +
            $penaltyOutstanding +
            $foreclosureFee,
            2
        );

        return [
            'loan_account_id' => $loan->id,
            'loan_number' => $loan->loan_number,
            'as_of_date' => $asOfStr,
            'principal_outstanding' => $principalOutstanding,
            'accrued_interest' => $accruedInterest,
            'unearned_interest_rebate' => $unearnedInterestRebate,
            'fee_outstanding' => $feeOutstanding,
            'penalty_outstanding' => $penaltyOutstanding,
            'foreclosure_fee_type' => $feeType,
            'foreclosure_fee_rate' => $feeType === 'percentage' ? $feePct : $flatFee,
            'foreclosure_fee' => $foreclosureFee,
            'discount_concession_amount' => 0.00,
            'gross_payoff' => $grossPayoff,
            'final_settlement_amount' => $finalSettlementAmount,
            'elapsed_installments_count' => $elapsedInstallmentsCount,
            'future_installments_count' => $futureInstallmentsCount,
            'lock_in' => $lockIn,
        ];
    }

    /**
     * Calculate One-Time Settlement (OTS / Compromise) Allocation & Concession.
     */
    public function calculateSettlementOts(
        LoanAccount $loan,
        float $proposedSettlementAmount,
        ?Carbon $asOfDate = null
    ): array {
        $asOf = $this->getNormalizedDate($asOfDate);
        $asOfStr = $asOf->format('Y-m-d');

        $principalOutstanding = round((float) $loan->principal_outstanding, 2);
        $interestOutstanding = round((float) $loan->interest_outstanding, 2);
        $feeOutstanding = round((float) $loan->fee_outstanding, 2);
        $penaltyOutstanding = round((float) $loan->penalty_outstanding, 2);

        $totalContractualDemand = round(
            $principalOutstanding + $interestOutstanding + $feeOutstanding + $penaltyOutstanding,
            2
        );

        $settlementCash = round(max(0.00, $proposedSettlementAmount), 2);
        $totalConcession = round(max(0.00, $totalContractualDemand - $settlementCash), 2);

        // Actual Allocation Waterfall for collected cash
        $rem = $settlementCash;

        $penaltyRecovered = min($rem, $penaltyOutstanding);
        $rem = round($rem - $penaltyRecovered, 2);

        $feeRecovered = min($rem, $feeOutstanding);
        $rem = round($rem - $feeRecovered, 2);

        $interestRecovered = min($rem, $interestOutstanding);
        $rem = round($rem - $interestRecovered, 2);

        $principalRecovered = min($rem, $principalOutstanding);
        $rem = round($rem - $principalRecovered, 2);

        $principalLoss = round(max(0.00, $principalOutstanding - $principalRecovered), 2);
        $interestWaived = round(max(0.00, $interestOutstanding - $interestRecovered), 2);
        $feeWaived = round(max(0.00, $feeOutstanding - $feeRecovered), 2);
        $penaltyWaived = round(max(0.00, $penaltyOutstanding - $penaltyRecovered), 2);

        // Determine Required Approval Role based on Concession Amount
        $requiredRole = 'Branch Manager';
        if ($totalConcession > 25000) {
            $requiredRole = 'Super Admin';
        } elseif ($totalConcession > 5000) {
            $requiredRole = 'Company Admin';
        }

        return [
            'loan_account_id' => $loan->id,
            'loan_number' => $loan->loan_number,
            'as_of_date' => $asOfStr,
            'principal_outstanding' => $principalOutstanding,
            'interest_outstanding' => $interestOutstanding,
            'fee_outstanding' => $feeOutstanding,
            'penalty_outstanding' => $penaltyOutstanding,
            'total_contractual_demand' => $totalContractualDemand,
            'final_settlement_amount' => $settlementCash,
            'discount_concession_amount' => $totalConcession,
            'allocation' => [
                'penalty_recovered' => $penaltyRecovered,
                'fee_recovered' => $feeRecovered,
                'interest_recovered' => $interestRecovered,
                'principal_recovered' => $principalRecovered,
                'principal_loss' => $principalLoss,
                'penalty_waived' => $penaltyWaived,
                'fee_waived' => $feeWaived,
                'interest_waived' => $interestWaived,
            ],
            'required_approval_role' => $requiredRole,
        ];
    }

    /**
     * Calculate Bad Debt Write-Off Metrics.
     */
    public function calculateWriteOff(LoanAccount $loan, ?Carbon $asOfDate = null): array
    {
        $asOf = $this->getNormalizedDate($asOfDate);
        $asOfStr = $asOf->format('Y-m-d');

        $principalOutstanding = round((float) $loan->principal_outstanding, 2);
        $interestOutstanding = round((float) $loan->interest_outstanding, 2);
        $feeOutstanding = round((float) $loan->fee_outstanding, 2);
        $penaltyOutstanding = round((float) $loan->penalty_outstanding, 2);

        $totalWrittenOff = round(
            $principalOutstanding + $interestOutstanding + $feeOutstanding + $penaltyOutstanding,
            2
        );

        return [
            'loan_account_id' => $loan->id,
            'loan_number' => $loan->loan_number,
            'as_of_date' => $asOfStr,
            'principal_outstanding' => $principalOutstanding,
            'interest_outstanding' => $interestOutstanding,
            'fee_outstanding' => $feeOutstanding,
            'penalty_outstanding' => $penaltyOutstanding,
            'total_write_off_amount' => $totalWrittenOff,
            'required_approval_role' => 'Super Admin',
        ];
    }

    /**
     * Validate whether a specific user can approve a settlement request.
     */
    public function canUserApprove(User $user, LoanSettlementRequest $request): bool
    {
        if ($user->hasRole('Super Admin') || ($user->role && $user->role->name === 'Super Admin')) {
            return true;
        }

        $concession = (float) $request->discount_concession_amount;
        $requestType = $request->request_type;

        if ($requestType === 'write_off') {
            return $user->hasRole('Super Admin');
        }

        if ($concession > 25000) {
            return $user->hasRole('Super Admin');
        }

        if ($concession > 5000) {
            return $user->hasRole('Company Admin') || $user->hasRole('Admin') || $user->hasRole('Super Admin');
        }

        // Concession <= 5000
        return $user->hasRole('Branch Manager') ||
            $user->hasRole('Company Admin') ||
            $user->hasRole('Admin') ||
            $user->hasRole('Super Admin');
    }

    /**
     * Create a new Loan Settlement / OTS / Write-Off Request.
     */
    public function createSettlementRequest(LoanAccount $loan, array $data, User $user): LoanSettlementRequest
    {
        $asOf = $this->getNormalizedDate(null, $data['as_of_date'] ?? null);
        $requestType = $data['request_type'] ?? 'settlement_ots';

        if ($loan->status === 'closed') {
            throw ValidationException::withMessages([
                'loan_account' => 'Cannot create settlement request for an already closed loan.',
            ]);
        }

        if ($requestType === 'foreclosure') {
            $calc = $this->calculateForeclosure($loan, $asOf);
            $finalAmount = $calc['final_settlement_amount'];
            $concession = 0.00;
            $foreclosureFee = $calc['foreclosure_fee'];
            $accruedInterest = $calc['accrued_interest'];
            $unearnedRebate = $calc['unearned_interest_rebate'];
        } elseif ($requestType === 'write_off') {
            $calc = $this->calculateWriteOff($loan, $asOf);
            $finalAmount = 0.00;
            $concession = $calc['principal_outstanding'];
            $foreclosureFee = 0.00;
            $accruedInterest = $calc['interest_outstanding'];
            $unearnedRebate = 0.00;
        } else {
            // OTS
            $proposedAmount = (float) ($data['proposed_settlement_amount'] ?? 0.00);
            $calc = $this->calculateSettlementOts($loan, $proposedAmount, $asOf);
            $finalAmount = $calc['final_settlement_amount'];
            $concession = $calc['discount_concession_amount'];
            $foreclosureFee = 0.00;
            $accruedInterest = $calc['interest_outstanding'];
            $unearnedRebate = 0.00;
        }

        $validUntil = !empty($data['valid_until_date'])
            ? Carbon::parse($data['valid_until_date'])->toDateString()
            : $asOf->copy()->addDays(7)->toDateString();

        $request = LoanSettlementRequest::create([
            'company_id' => $loan->company_id,
            'branch_id' => $loan->branch_id,
            'loan_account_id' => $loan->id,
            'request_type' => $requestType,
            'status' => 'pending_approval',
            'as_of_date' => $asOf->toDateString(),
            'principal_outstanding' => $loan->principal_outstanding,
            'accrued_interest' => $accruedInterest,
            'unearned_interest_rebate' => $unearnedRebate,
            'fee_outstanding' => $loan->fee_outstanding,
            'penalty_outstanding' => $loan->penalty_outstanding,
            'foreclosure_fee' => $foreclosureFee,
            'discount_concession_amount' => $concession,
            'final_settlement_amount' => $finalAmount,
            'valid_until_date' => $validUntil,
            'requested_by' => $user->id,
            'requested_at' => now(),
            'approval_remarks' => $data['remarks'] ?? null,
        ]);

        $this->activityLogService->log(
            'loan_settlement_requested',
            $request,
            null,
            ['remarks' => $data['remarks'] ?? null, 'concession' => $concession, 'final_amount' => $finalAmount]
        );

        return $request;
    }

    /**
     * Approve Pending Settlement Request with Threshold Validation.
     */
    public function approveSettlementRequest(
        LoanSettlementRequest $request,
        User $user,
        ?string $remarks = null
    ): LoanSettlementRequest {
        if ($request->status !== 'pending_approval') {
            throw ValidationException::withMessages([
                'status' => "Request #{$request->id} is already in '{$request->status}' status.",
            ]);
        }

        if (!$this->canUserApprove($user, $request)) {
            $concession = number_format($request->discount_concession_amount, 2);
            throw ValidationException::withMessages([
                'user' => "User does not have sufficient authority to approve concession of ₹{$concession}.",
            ]);
        }

        $request->update([
            'status' => 'approved',
            'approved_by' => $user->id,
            'approved_at' => now(),
            'approval_remarks' => $remarks ?? $request->approval_remarks,
        ]);

        $this->activityLogService->log(
            'loan_settlement_approved',
            $request,
            ['status' => 'pending_approval'],
            ['status' => 'approved', 'approved_by' => $user->id]
        );

        return $request;
    }

    /**
     * Reject Pending Settlement Request.
     */
    public function rejectSettlementRequest(
        LoanSettlementRequest $request,
        User $user,
        string $reason
    ): LoanSettlementRequest {
        if ($request->status !== 'pending_approval') {
            throw ValidationException::withMessages([
                'status' => "Request #{$request->id} is already in '{$request->status}' status.",
            ]);
        }

        $request->update([
            'status' => 'rejected',
            'approved_by' => $user->id,
            'approved_at' => now(),
            'rejection_reason' => $reason,
        ]);

        $this->activityLogService->log(
            'loan_settlement_rejected',
            $request,
            ['status' => 'pending_approval'],
            ['status' => 'rejected', 'rejection_reason' => $reason]
        );

        return $request;
    }

    /**
     * Execute Standard Voluntary Early Loan Foreclosure.
     */
    public function executeForeclosure(LoanAccount $loan, array $paymentData, User $user): array
    {
        if ($loan->status === 'closed') {
            throw ValidationException::withMessages([
                'loan_account' => 'Loan is already closed.',
            ]);
        }

        $asOf = $this->getNormalizedDate(null, $paymentData['payment_date'] ?? null);
        $calc = $this->calculateForeclosure($loan, $asOf);

        if (!($calc['lock_in']['is_allowed'] ?? true)) {
            throw ValidationException::withMessages([
                'lock_in' => $calc['lock_in']['message'],
            ]);
        }

        $payoffAmount = $calc['final_settlement_amount'];
        $paymentMethod = $paymentData['payment_method'] ?? 'cash';
        $paymentDate = $asOf->toDateString();

        return DB::transaction(function () use ($loan, $calc, $payoffAmount, $paymentMethod, $paymentDate, $user, $paymentData) {
            // 1. Create Immutable Repayment Record
            $receiptNumber = 'RCP-FC-' . strtoupper(uniqid());

            $repayment = LoanRepayment::create([
                'company_id' => $loan->company_id,
                'branch_id' => $loan->branch_id,
                'loan_account_id' => $loan->id,
                'receipt_number' => $receiptNumber,
                'repayment_type' => 'settlement',
                'payment_method' => $paymentMethod,
                'amount' => $payoffAmount,
                'principal_paid' => $calc['principal_outstanding'],
                'interest_paid' => $calc['accrued_interest'],
                'fee_paid' => $calc['fee_outstanding'],
                'penalty_paid' => $calc['penalty_outstanding'],
                'payment_date' => $paymentDate,
                'remarks' => $paymentData['remarks'] ?? 'Early Loan Foreclosure Payoff',
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            // 2. Mark installments: Elapsed -> paid, Future -> waived
            $asOfStr = Carbon::parse($paymentDate)->format('Y-m-d');
            $installments = $loan->installments()->get();

            foreach ($installments as $inst) {
                $dueDateStr = $inst->due_date ? Carbon::parse($inst->due_date)->format('Y-m-d') : null;
                if ($dueDateStr && $dueDateStr <= $asOfStr) {
                    $inst->update([
                        'status' => 'paid',
                        'paid_at' => $paymentDate,
                        'principal_paid' => $inst->principal_amount,
                        'interest_paid' => $inst->interest_amount,
                        'fee_paid' => $inst->fee_amount,
                        'penalty_paid' => $inst->penalty_amount,
                        'total_paid' => round($inst->principal_amount + $inst->interest_amount + $inst->fee_amount + $inst->penalty_amount, 2),
                    ]);
                } else {
                    $inst->update([
                        'status' => 'waived',
                    ]);
                }
            }

            // 3. Update Loan Account State
            $loan->update([
                'status' => 'closed',
                'closed_at' => now(),
                'closure_type' => 'foreclosure',
                'closure_remarks' => $paymentData['remarks'] ?? 'Voluntary Early Foreclosure',
                'closure_approved_by' => $user->id,
                'closure_approved_at' => now(),
                'principal_outstanding' => 0.00,
                'interest_outstanding' => 0.00,
                'fee_outstanding' => 0.00,
                'penalty_outstanding' => 0.00,
                'total_outstanding' => 0.00,
                'updated_by' => $user->id,
            ]);

            // 4. Post Double-Entry General Ledger Voucher
            $voucher = $this->accountingService->postLoanForeclosure(
                $repayment,
                $loan,
                $calc['foreclosure_fee'],
                $calc['accrued_interest'],
                $calc['principal_outstanding'],
                $calc['fee_outstanding'],
                $calc['penalty_outstanding']
            );

            // 5. Create Settlement Request Record for Audit Trail
            $settlementRequest = LoanSettlementRequest::create([
                'company_id' => $loan->company_id,
                'branch_id' => $loan->branch_id,
                'loan_account_id' => $loan->id,
                'request_type' => 'foreclosure',
                'status' => 'completed',
                'as_of_date' => $paymentDate,
                'principal_outstanding' => $calc['principal_outstanding'],
                'accrued_interest' => $calc['accrued_interest'],
                'unearned_interest_rebate' => $calc['unearned_interest_rebate'],
                'fee_outstanding' => $calc['fee_outstanding'],
                'penalty_outstanding' => $calc['penalty_outstanding'],
                'foreclosure_fee' => $calc['foreclosure_fee'],
                'discount_concession_amount' => 0.00,
                'final_settlement_amount' => $payoffAmount,
                'valid_until_date' => $paymentDate,
                'requested_by' => $user->id,
                'requested_at' => now(),
                'approved_by' => $user->id,
                'approved_at' => now(),
                'approval_remarks' => 'Voluntary Foreclosure Executed',
                'repayment_id' => $repayment->id,
                'voucher_id' => $voucher?->id,
            ]);

            $this->activityLogService->log(
                'loan_foreclosure_executed',
                $loan,
                null,
                ['payoff_amount' => $payoffAmount, 'repayment_id' => $repayment->id]
            );

            return [
                'loan_account' => $loan->fresh(),
                'repayment' => $repayment,
                'voucher' => $voucher,
                'settlement_request' => $settlementRequest,
            ];
        });
    }

    /**
     * Execute Approved One-Time Settlement (OTS).
     */
    public function executeApprovedSettlement(
        LoanSettlementRequest $request,
        array $paymentData,
        User $user
    ): array {
        if ($request->status !== 'approved') {
            throw ValidationException::withMessages([
                'status' => "Request #{$request->id} is not in approved status (Current: {$request->status}).",
            ]);
        }

        $loan = $request->loanAccount;
        if ($loan->status === 'closed') {
            throw ValidationException::withMessages([
                'loan_account' => 'Loan is already closed.',
            ]);
        }

        $calc = $this->calculateSettlementOts($loan, (float) $request->final_settlement_amount);
        $alloc = $calc['allocation'];
        $settlementCash = $calc['final_settlement_amount'];
        $paymentMethod = $paymentData['payment_method'] ?? 'cash';
        $paymentDate = !empty($paymentData['payment_date'])
            ? Carbon::parse($paymentData['payment_date'])->toDateString()
            : now()->toDateString();

        return DB::transaction(function () use ($request, $loan, $calc, $alloc, $settlementCash, $paymentMethod, $paymentDate, $user, $paymentData) {
            // 1. Create Repayment Receipt for actual cash collected
            $receiptNumber = 'RCP-OTS-' . strtoupper(uniqid());

            $repayment = LoanRepayment::create([
                'company_id' => $loan->company_id,
                'branch_id' => $loan->branch_id,
                'loan_account_id' => $loan->id,
                'receipt_number' => $receiptNumber,
                'repayment_type' => 'settlement',
                'payment_method' => $paymentMethod,
                'amount' => $settlementCash,
                'principal_paid' => $alloc['principal_recovered'],
                'interest_paid' => $alloc['interest_recovered'],
                'fee_paid' => $alloc['fee_recovered'],
                'penalty_paid' => $alloc['penalty_recovered'],
                'payment_date' => $paymentDate,
                'remarks' => $paymentData['remarks'] ?? 'One-Time Compromise Settlement (OTS)',
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            // 2. Mark all unpaid installments as waived
            $loan->installments()->where('status', '!=', 'paid')->update([
                'status' => 'waived',
            ]);

            // 3. Update Loan Account State
            $loan->update([
                'status' => 'closed',
                'closed_at' => now(),
                'closure_type' => 'settlement',
                'closure_remarks' => $request->approval_remarks ?? 'Approved OTS Settlement',
                'closure_approved_by' => $request->approved_by,
                'closure_approved_at' => $request->approved_at,
                'principal_outstanding' => 0.00,
                'interest_outstanding' => 0.00,
                'fee_outstanding' => 0.00,
                'penalty_outstanding' => 0.00,
                'total_outstanding' => 0.00,
                'updated_by' => $user->id,
            ]);

            // 4. Post Double-Entry GL Voucher
            $voucher = $this->accountingService->postLoanSettlement($repayment, $loan, $request, $alloc);

            // 5. Update Settlement Request Status
            $request->update([
                'status' => 'completed',
                'repayment_id' => $repayment->id,
                'voucher_id' => $voucher?->id,
            ]);

            $this->activityLogService->log(
                'loan_settlement_executed',
                $loan,
                null,
                ['settlement_cash' => $settlementCash, 'principal_loss' => $alloc['principal_loss']]
            );

            return [
                'loan_account' => $loan->fresh(),
                'repayment' => $repayment,
                'voucher' => $voucher,
                'settlement_request' => $request->fresh(),
            ];
        });
    }

    /**
     * Execute Bad Debt Loan Write-Off.
     */
    public function executeWriteOff(LoanSettlementRequest $request, User $user): array
    {
        if (!$user->hasRole('Super Admin') && !($user->role && $user->role->name === 'Super Admin')) {
            throw ValidationException::withMessages([
                'user' => 'Only Super Admin can execute bad debt loan write-offs.',
            ]);
        }

        $loan = $request->loanAccount;
        if ($loan->status === 'closed') {
            throw ValidationException::withMessages([
                'loan_account' => 'Loan is already closed.',
            ]);
        }

        return DB::transaction(function () use ($request, $loan, $user) {
            // 1. Mark installments as waived
            $loan->installments()->where('status', '!=', 'paid')->update([
                'status' => 'waived',
            ]);

            // 2. Update Loan Account State
            $loan->update([
                'status' => 'closed',
                'closed_at' => now(),
                'closure_type' => 'write_off',
                'closure_remarks' => $request->approval_remarks ?? 'Bad Debt Principal Write-Off',
                'closure_approved_by' => $user->id,
                'closure_approved_at' => now(),
                'principal_outstanding' => 0.00,
                'interest_outstanding' => 0.00,
                'fee_outstanding' => 0.00,
                'penalty_outstanding' => 0.00,
                'total_outstanding' => 0.00,
                'updated_by' => $user->id,
            ]);

            // 3. Post Loss Voucher to GL
            $voucher = $this->accountingService->postLoanWriteOff($loan, $request);

            // 4. Update Settlement Request
            $request->update([
                'status' => 'completed',
                'approved_by' => $user->id,
                'approved_at' => now(),
                'voucher_id' => $voucher?->id,
            ]);

            $this->activityLogService->log(
                'loan_written_off',
                $loan,
                null,
                ['loss_amount' => $loan->sanctioned_amount]
            );

            return [
                'loan_account' => $loan->fresh(),
                'voucher' => $voucher,
                'settlement_request' => $request->fresh(),
            ];
        });
    }

    /**
     * Generate Comprehensive Data for No Objection Certificate (NOC) / Closure Slip.
     */
    public function generateNocData(LoanAccount $loan): array
    {
        $company = $loan->company;
        $branch = $loan->branch;
        $customer = $loan->customer;
        $scheme = $loan->loanScheme;
        $approver = $loan->closureApprover;

        $totalRepayments = (float) $loan->repayments()->sum('amount');

        return [
            'certificate_number' => 'NOC-' . strtoupper(substr(md5($loan->loan_number . ($loan->closed_at ?? now())), 0, 10)),
            'generation_date' => now(self::TIMEZONE)->format('d M Y, h:i A'),
            'company' => [
                'name' => $company->name ?? 'Grihalaxmi Finance',
                'registration_number' => $company->registration_number ?? 'N/A',
                'email' => $company->email ?? 'support@grihalaxmi.com',
                'phone' => $company->phone ?? 'N/A',
                'address' => $company->address ?? 'N/A',
            ],
            'branch' => [
                'name' => $branch->name ?? 'Main Branch',
                'code' => $branch->branch_code ?? 'BR-001',
                'address' => $branch->address ?? 'N/A',
            ],
            'borrower' => [
                'name' => $customer->full_name ?? 'Borrower',
                'code' => $customer->customer_code ?? 'CUST-000',
                'phone' => $customer->phone ?? 'N/A',
                'address' => $customer->address ?? 'N/A',
            ],
            'loan' => [
                'id' => $loan->id,
                'loan_number' => $loan->loan_number,
                'loan_type' => ucfirst($loan->loan_type),
                'scheme_name' => $scheme->name ?? 'Standard Scheme',
                'sanctioned_amount' => (float) $loan->sanctioned_amount,
                'disbursed_amount' => (float) $loan->disbursed_amount,
                'total_repaid' => $totalRepayments,
                'sanction_date' => $loan->sanction_date ? Carbon::parse($loan->sanction_date)->format('d M Y') : 'N/A',
                'disbursement_date' => $loan->disbursement_date ? Carbon::parse($loan->disbursement_date)->format('d M Y') : 'N/A',
                'closed_at' => $loan->closed_at ? Carbon::parse($loan->closed_at)->format('d M Y') : now()->format('d M Y'),
                'closure_type' => ucfirst(str_replace('_', ' ', $loan->closure_type ?? 'normal')),
                'closure_remarks' => $loan->closure_remarks ?? 'Account closed with zero outstanding liability.',
                'approved_by' => $approver->name ?? 'Authorized Signatory',
            ],
        ];
    }
}
