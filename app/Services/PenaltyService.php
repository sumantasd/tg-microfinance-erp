<?php

namespace App\Services;

use App\Models\LoanAccount;
use App\Models\LoanInstallment;
use App\Models\LoanPenaltyCharge;
use App\Models\LoanPenaltyWaiver;
use App\Models\LoanScheme;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PenaltyService
{
    public const TIMEZONE = 'Asia/Kolkata';

    protected OverdueService $overdueService;
    protected ActivityLogService $activityLogService;

    public function __construct(OverdueService $overdueService, ActivityLogService $activityLogService)
    {
        $this->overdueService = $overdueService;
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
     * Calculate Cumulative and Incremental Penalty for a Single Loan Installment.
     *
     * PENALTY BASE RULE:
     * Penalty Base = Installment amount outstanding excluding any already assessed penalty.
     * Non-Penalty Due = (principal_amount + interest_amount + fee_amount)
     * Non-Penalty Paid = (principal_paid + interest_paid + fee_paid)
     * Penalty Base = max(0, Non-Penalty Due - Non-Penalty Paid)
     *
     * Strict Non-Compounding: Penalty is NEVER calculated on existing penalty_amount or penalty_paid.
     */
    public function calculateInstallmentPenalty(LoanInstallment $installment, ?Carbon $asOfDate = null): array
    {
        $asOf = $this->getNormalizedDate($asOfDate);
        $asOfStr = $asOf->format('Y-m-d');

        $loanAccount = $installment->loanAccount ?? LoanAccount::with('loanScheme')->find($installment->loan_account_id);
        $scheme = $loanAccount ? $loanAccount->loanScheme : null;

        if (!$scheme) {
            return [
                'is_eligible' => false,
                'penalty_type' => 'none',
                'dpd' => 0,
                'grace_period_days' => 0,
                'effective_penalty_days' => 0,
                'penalty_base' => 0.00,
                'cumulative_penalty' => 0.00,
                'already_assessed_penalty' => 0.00,
                'incremental_penalty' => 0.00,
                'applied_cap' => null,
            ];
        }

        $dpdInfo = $this->overdueService->getInstallmentDpd($installment, $asOfStr);
        $dpd = (int) $dpdInfo['dpd'];
        $graceDays = (int) ($scheme->grace_period_days ?? 0);
        $penaltyType = $scheme->penalty_type ?? 'percentage_one_time';

        // Non-penalty principal + interest + fee outstanding base
        $nonPenaltyDue = round((float) ($installment->principal_amount + $installment->interest_amount + $installment->fee_amount), 2);
        $nonPenaltyPaid = round((float) ($installment->principal_paid + $installment->interest_paid + $installment->fee_paid), 2);
        $penaltyBase = max(0.00, round($nonPenaltyDue - $nonPenaltyPaid, 2));

        $alreadyAssessed = round((float) $installment->penalty_amount, 2);

        // Eligibility check
        if (
            $penaltyType === 'none' ||
            $dpd <= $graceDays ||
            $penaltyBase <= 0.01 ||
            $installment->status === 'paid'
        ) {
            return [
                'is_eligible' => false,
                'penalty_type' => $penaltyType,
                'dpd' => $dpd,
                'grace_period_days' => $graceDays,
                'effective_penalty_days' => 0,
                'penalty_base' => $penaltyBase,
                'cumulative_penalty' => $alreadyAssessed,
                'already_assessed_penalty' => $alreadyAssessed,
                'incremental_penalty' => 0.00,
                'applied_cap' => null,
            ];
        }

        $effectiveDays = max(1, $dpd - $graceDays);
        $rawPenalty = 0.00;

        switch ($penaltyType) {
            case 'percentage_one_time':
                $pct = (float) ($scheme->late_fee_percentage ?? 0.00);
                $rawPenalty = round($penaltyBase * ($pct / 100), 2);
                break;

            case 'flat_one_time':
                $rawPenalty = round((float) ($scheme->flat_penalty_amount ?? 0.00), 2);
                break;

            case 'flat_per_day':
                $flatPerDay = (float) ($scheme->flat_penalty_amount ?? 0.00);
                $rawPenalty = round($flatPerDay * $effectiveDays, 2);
                break;

            case 'percentage_per_day':
                $pct = (float) ($scheme->late_fee_percentage ?? 0.00);
                $rawPenalty = round($penaltyBase * ($pct / 100) * $effectiveDays, 2);
                break;

            default:
                $rawPenalty = 0.00;
                break;
        }

        // Apply Caps
        $appliedCap = null;
        $maxCapAmount = null;

        if ($scheme->max_penalty_amount !== null && (float) $scheme->max_penalty_amount > 0) {
            $maxCapAmount = (float) $scheme->max_penalty_amount;
            $appliedCap = 'max_penalty_amount';
        }

        if ($scheme->max_penalty_percentage !== null && (float) $scheme->max_penalty_percentage > 0) {
            $pctCap = round($penaltyBase * ((float) $scheme->max_penalty_percentage / 100), 2);
            if ($maxCapAmount === null || $pctCap < $maxCapAmount) {
                $maxCapAmount = $pctCap;
                $appliedCap = 'max_penalty_percentage';
            }
        }

        if ($maxCapAmount !== null && $rawPenalty > $maxCapAmount) {
            $cumulativePenalty = round($maxCapAmount, 2);
        } else {
            $cumulativePenalty = round($rawPenalty, 2);
            if ($maxCapAmount === null || $rawPenalty <= $maxCapAmount) {
                $appliedCap = null;
            }
        }

        $incrementalPenalty = max(0.00, round($cumulativePenalty - $alreadyAssessed, 2));

        return [
            'is_eligible' => true,
            'penalty_type' => $penaltyType,
            'dpd' => $dpd,
            'grace_period_days' => $graceDays,
            'effective_penalty_days' => $effectiveDays,
            'penalty_base' => $penaltyBase,
            'cumulative_penalty' => $cumulativePenalty,
            'already_assessed_penalty' => $alreadyAssessed,
            'incremental_penalty' => $incrementalPenalty,
            'applied_cap' => $appliedCap,
        ];
    }

    /**
     * Apply Daily Penalties Across Active Loans in Database.
     *
     * IDEMPOTENCY GUARANTEE:
     * Uses unique constraint on (loan_installment_id, charge_date).
     * If a charge record already exists for the installment on the given date, it is skipped.
     * Charges only the incremental difference not already assessed.
     */
    public function applyDailyPenalties(?int $companyId = null, ?Carbon $asOfDate = null): array
    {
        $asOf = $this->getNormalizedDate($asOfDate);
        $asOfStr = $asOf->format('Y-m-d');

        $query = LoanAccount::with(['installments', 'loanScheme'])
            ->where('status', 'active');

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        $activeLoans = $query->get();

        $processedInstallments = 0;
        $penalizedInstallments = 0;
        $totalPenaltyApplied = 0.00;
        $skippedAlreadyCharged = 0;

        DB::transaction(function () use (
            $activeLoans,
            $asOf,
            $asOfStr,
            &$processedInstallments,
            &$penalizedInstallments,
            &$totalPenaltyApplied,
            &$skippedAlreadyCharged
        ) {
            foreach ($activeLoans as $loan) {
                $scheme = $loan->loanScheme;
                if (!$scheme || $scheme->penalty_type === 'none') {
                    continue;
                }

                foreach ($loan->installments as $installment) {
                    $processedInstallments++;

                    // Idempotency check: Already charged on this exact date?
                    $alreadyChargedToday = LoanPenaltyCharge::where('loan_installment_id', $installment->id)
                        ->where('charge_date', $asOfStr)
                        ->exists();

                    if ($alreadyChargedToday) {
                        $skippedAlreadyCharged++;
                        continue;
                    }

                    $calc = $this->calculateInstallmentPenalty($installment, $asOf);

                    if (!$calc['is_eligible'] || $calc['incremental_penalty'] <= 0.00) {
                        continue;
                    }

                    $incrementalAmount = $calc['incremental_penalty'];

                    // 1. Create Immutable Audit Charge Record
                    LoanPenaltyCharge::create([
                        'loan_account_id' => $loan->id,
                        'loan_installment_id' => $installment->id,
                        'charge_date' => $asOfStr,
                        'dpd_at_charge' => $calc['dpd'],
                        'charge_amount' => $incrementalAmount,
                        'calculation_type' => $calc['penalty_type'],
                        'remarks' => "Assessed {$calc['penalty_type']} penalty on DPD {$calc['dpd']} (Eff. Days: {$calc['effective_penalty_days']})" . ($calc['applied_cap'] ? " [Cap: {$calc['applied_cap']}]" : ""),
                    ]);

                    // 2. Update Installment Schedule
                    $installment->penalty_amount = round((float) $installment->penalty_amount + $incrementalAmount, 2);
                    $installment->installment_amount = round((float) $installment->installment_amount + $incrementalAmount, 2);
                    $installment->save();

                    // 3. Update Loan Account Dues
                    $loan->penalty_outstanding = round((float) $loan->penalty_outstanding + $incrementalAmount, 2);
                    $loan->total_outstanding = round((float) $loan->total_outstanding + $incrementalAmount, 2);
                    $loan->save();

                    $penalizedInstallments++;
                    $totalPenaltyApplied += $incrementalAmount;
                }
            }
        });

        return [
            'as_of_date' => $asOfStr,
            'processed_installments' => $processedInstallments,
            'penalized_installments' => $penalizedInstallments,
            'skipped_already_charged' => $skippedAlreadyCharged,
            'total_penalty_applied' => round($totalPenaltyApplied, 2),
        ];
    }

    /**
     * Waive Uncollected Penalty by an Authorized User.
     *
     * RULES:
     * - Amount must be > 0 and <= loan_account.penalty_outstanding.
     * - Decreases installment.penalty_amount and loan_account.penalty_outstanding.
     * - Does NOT create GL reversal because uncollected penalty was never posted to GL.
     * - Creates immutable loan_penalty_waivers record.
     */
    public function waivePenalty(
        LoanAccount $loan,
        float $amount,
        string $reason,
        int $userId,
        ?LoanInstallment $installment = null
    ): LoanPenaltyWaiver {
        $amount = round($amount, 2);
        $reason = trim($reason);

        if ($amount <= 0) {
            throw new InvalidArgumentException("Waiver amount must be greater than zero.");
        }

        if (empty($reason)) {
            throw new InvalidArgumentException("A valid waiver justification reason is mandatory.");
        }

        $currentPenaltyOutstanding = round((float) $loan->penalty_outstanding, 2);

        if ($amount > $currentPenaltyOutstanding) {
            throw new InvalidArgumentException("Waiver amount (₹{$amount}) cannot exceed current uncollected penalty outstanding (₹{$currentPenaltyOutstanding}).");
        }

        return DB::transaction(function () use ($loan, $amount, $reason, $userId, $installment) {
            // 1. Create Waiver Record
            $waiver = LoanPenaltyWaiver::create([
                'loan_account_id' => $loan->id,
                'loan_installment_id' => $installment ? $installment->id : null,
                'waived_amount' => $amount,
                'waiver_date' => Carbon::now(self::TIMEZONE)->toDateString(),
                'waiver_reason' => $reason,
                'authorized_by' => $userId,
            ]);

            // 2. Reduce Penalty from Installment Schedule
            if ($installment) {
                $uncollectedOnInst = max(0.00, round((float) $installment->penalty_amount - (float) $installment->penalty_paid, 2));
                $instWaive = min($amount, $uncollectedOnInst);

                $installment->penalty_amount = max(0.00, round((float) $installment->penalty_amount - $instWaive, 2));
                $installment->installment_amount = max(0.00, round((float) $installment->installment_amount - $instWaive, 2));
                $installment->save();
            } else {
                // FIFO across unpaid installments with uncollected penalty
                $remToWaive = $amount;
                $installments = $loan->installments()->orderBy('installment_number', 'asc')->get();

                foreach ($installments as $inst) {
                    if ($remToWaive <= 0.00) {
                        break;
                    }

                    $uncollected = max(0.00, round((float) $inst->penalty_amount - (float) $inst->penalty_paid, 2));
                    if ($uncollected > 0.00) {
                        $deduct = min($remToWaive, $uncollected);
                        $inst->penalty_amount = max(0.00, round((float) $inst->penalty_amount - $deduct, 2));
                        $inst->installment_amount = max(0.00, round((float) $inst->installment_amount - $deduct, 2));
                        $inst->save();

                        $remToWaive = round($remToWaive - $deduct, 2);
                    }
                }
            }

            // 3. Reduce Loan Account Penalty and Total Outstanding
            $loan->penalty_outstanding = max(0.00, round((float) $loan->penalty_outstanding - $amount, 2));
            $loan->total_outstanding = max(
                0.00,
                round(
                    (float) $loan->principal_outstanding +
                    (float) $loan->interest_outstanding +
                    (float) $loan->fee_outstanding +
                    (float) $loan->penalty_outstanding,
                    2
                )
            );
            $loan->save();

            // 4. Log Activity
            $this->activityLogService->log(
                'penalty_waived',
                $loan,
                null,
                ['waived_amount' => $amount, 'reason' => $reason, 'authorized_by' => $userId]
            );

            return $waiver;
        });
    }
}
