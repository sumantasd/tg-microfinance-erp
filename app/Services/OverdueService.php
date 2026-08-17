<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\LoanAccount;
use App\Models\LoanInstallment;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OverdueService
{
    public const TIMEZONE = 'Asia/Kolkata';

    /**
     * Normalize As-Of Date to Asia/Kolkata Start of Day
     */
    public function getNormalizedDate(?string $asOfDate = null): Carbon
    {
        $dateStr = $asOfDate ? substr($asOfDate, 0, 10) : Carbon::now(self::TIMEZONE)->format('Y-m-d');
        return Carbon::createFromFormat('Y-m-d', $dateStr, self::TIMEZONE)->startOfDay();
    }

    /**
     * Calculate DPD and Status for a Single Loan Installment.
     */
    public function getInstallmentDpd(LoanInstallment $installment, ?string $asOfDate = null): array
    {
        $asOf = $this->getNormalizedDate($asOfDate);
        $dueDateRaw = $installment->getRawOriginal('due_date') ?? (is_string($installment->due_date) ? $installment->due_date : $installment->due_date->format('Y-m-d'));
        $dueDateStr = substr($dueDateRaw, 0, 10);
        $dueDate = Carbon::createFromFormat('Y-m-d', $dueDateStr, self::TIMEZONE)->startOfDay();

        $dueAmount = round((float) $installment->installment_amount, 2);
        $principalPaid = round((float) $installment->principal_paid, 2);
        $interestPaid = round((float) $installment->interest_paid, 2);
        $feePaid = round((float) $installment->fee_paid, 2);
        $penaltyPaid = round((float) $installment->penalty_paid, 2);

        $paidAmount = round($principalPaid + $interestPaid + $feePaid + $penaltyPaid, 2);
        $outstandingAmount = max(0, round($dueAmount - $paidAmount, 2));

        if ($outstandingAmount <= 0.01) {
            return [
                'dpd' => 0,
                'due_amount' => $dueAmount,
                'paid_amount' => $paidAmount,
                'outstanding_amount' => 0.00,
                'status' => 'paid',
                'display_status' => 'Paid',
                'is_overdue' => false,
                'is_due_today' => false,
                'is_upcoming' => false,
                'due_date' => $dueDate->toDateString(),
            ];
        }

        if ($dueDate->isAfter($asOf)) {
            return [
                'dpd' => 0,
                'due_amount' => $dueAmount,
                'paid_amount' => $paidAmount,
                'outstanding_amount' => $outstandingAmount,
                'status' => ($paidAmount > 0 ? 'partial' : 'pending'),
                'display_status' => ($paidAmount > 0 ? 'Partially Paid' : 'Upcoming'),
                'is_overdue' => false,
                'is_due_today' => false,
                'is_upcoming' => true,
                'due_date' => $dueDate->toDateString(),
            ];
        }

        if ($dueDate->equalTo($asOf)) {
            return [
                'dpd' => 0,
                'due_amount' => $dueAmount,
                'paid_amount' => $paidAmount,
                'outstanding_amount' => $outstandingAmount,
                'status' => ($paidAmount > 0 ? 'partial' : 'pending'),
                'display_status' => ($paidAmount > 0 ? 'Partially Paid (Due Today)' : 'Due Today'),
                'is_overdue' => false,
                'is_due_today' => true,
                'is_upcoming' => false,
                'due_date' => $dueDate->toDateString(),
            ];
        }

        // Past Due (dueDate < asOf)
        $dpd = (int) $dueDate->diffInDays($asOf);

        return [
            'dpd' => $dpd,
            'due_amount' => $dueAmount,
            'paid_amount' => $paidAmount,
            'outstanding_amount' => $outstandingAmount,
            'status' => 'overdue',
            'display_status' => ($paidAmount > 0 ? 'Partially Paid (Overdue)' : 'Overdue'),
            'is_overdue' => true,
            'is_due_today' => false,
            'is_upcoming' => false,
            'due_date' => $dueDate->toDateString(),
        ];
    }

    /**
     * Calculate Overdue Details and Aging for a Loan Account.
     */
    public function getLoanOverdueDetails(LoanAccount $loanAccount, ?string $asOfDate = null): array
    {
        $asOf = $this->getNormalizedDate($asOfDate);

        $installments = $loanAccount->relationLoaded('installments')
            ? $loanAccount->installments
            : $loanAccount->installments()->orderBy('installment_number', 'asc')->get();

        $totalOverdueAmount = 0.00;
        $maxDpd = 0;
        $overdueCount = 0;
        $oldestOverdueDate = null;
        $nextDueDate = null;

        foreach ($installments as $inst) {
            $details = $this->getInstallmentDpd($inst, $asOfDate);

            if ($details['is_overdue']) {
                $totalOverdueAmount += $details['outstanding_amount'];
                $overdueCount++;
                if ($details['dpd'] > $maxDpd) {
                    $maxDpd = $details['dpd'];
                }
                if ($oldestOverdueDate === null || $details['due_date'] < $oldestOverdueDate) {
                    $oldestOverdueDate = $details['due_date'];
                }
            } elseif (($details['is_due_today'] || $details['is_upcoming']) && $nextDueDate === null) {
                $nextDueDate = $details['due_date'];
            }
        }

        $totalOverdueAmount = round($totalOverdueAmount, 2);
        $bucket = $this->classifyAgingBucket($maxDpd);

        return [
            'loan_id' => $loanAccount->id,
            'loan_number' => $loanAccount->loan_number,
            'dpd' => $maxDpd,
            'overdue_amount' => $totalOverdueAmount,
            'aging_bucket' => $bucket['label'],
            'aging_bucket_key' => $bucket['key'],
            'overdue_installments_count' => $overdueCount,
            'oldest_overdue_date' => $oldestOverdueDate,
            'next_due_date' => $nextDueDate,
            'is_delinquent' => ($maxDpd > 0),
            'principal_outstanding' => (float) $loanAccount->principal_outstanding,
            'total_outstanding' => (float) $loanAccount->total_outstanding,
            'status' => $loanAccount->status,
        ];
    }

    /**
     * Classify DPD into Standard Microfinance Aging Buckets.
     */
    public function classifyAgingBucket(int $dpd): array
    {
        if ($dpd === 0) {
            return ['key' => 'current', 'label' => 'Current'];
        }
        if ($dpd <= 30) {
            return ['key' => '1_30', 'label' => '1–30 Days'];
        }
        if ($dpd <= 60) {
            return ['key' => '31_60', 'label' => '31–60 Days'];
        }
        if ($dpd <= 90) {
            return ['key' => '61_90', 'label' => '61–90 Days'];
        }
        return ['key' => '90_plus', 'label' => '90+ Days'];
    }

    /**
     * Get Consolidated Overdue Summary for a Customer across all active loans.
     */
    public function getCustomerOverdueSummary(Customer $customer, ?string $asOfDate = null): array
    {
        $loans = $customer->loanAccounts()
            ->whereIn('status', ['active', 'defaulted'])
            ->with(['loanScheme', 'installments'])
            ->get();

        $activeLoansCount = $loans->count();
        $delinquentLoansCount = 0;
        $totalOverdueAmount = 0.00;
        $totalPrincipalOutstanding = 0.00;
        $totalOutstanding = 0.00;
        $maxDpd = 0;
        $oldestOverdueDate = null;
        $totalOverdueInstallments = 0;

        $loansDetails = [];

        foreach ($loans as $loan) {
            $loanDetails = $this->getLoanOverdueDetails($loan, $asOfDate);
            $totalPrincipalOutstanding += $loanDetails['principal_outstanding'];
            $totalOutstanding += $loanDetails['total_outstanding'];

            if ($loanDetails['is_delinquent']) {
                $delinquentLoansCount++;
                $totalOverdueAmount += $loanDetails['overdue_amount'];
                $totalOverdueInstallments += $loanDetails['overdue_installments_count'];

                if ($loanDetails['dpd'] > $maxDpd) {
                    $maxDpd = $loanDetails['dpd'];
                }
                if ($oldestOverdueDate === null || ($loanDetails['oldest_overdue_date'] && $loanDetails['oldest_overdue_date'] < $oldestOverdueDate)) {
                    $oldestOverdueDate = $loanDetails['oldest_overdue_date'];
                }
            }

            $loansDetails[] = array_merge($loanDetails, [
                'scheme_name' => $loan->loanScheme->name ?? 'Loan Scheme',
                'loan_type' => $loan->loan_type,
            ]);
        }

        return [
            'customer_id' => $customer->id,
            'customer_name' => $customer->full_name,
            'customer_code' => $customer->customer_code,
            'branch_id' => $customer->branch_id,
            'branch_name' => $customer->branch->name ?? 'N/A',
            'mobile_number' => $customer->mobile_number,
            'active_loans_count' => $activeLoansCount,
            'delinquent_loans_count' => $delinquentLoansCount,
            'total_overdue_amount' => round($totalOverdueAmount, 2),
            'total_principal_outstanding' => round($totalPrincipalOutstanding, 2),
            'total_outstanding' => round($totalOutstanding, 2),
            'max_dpd' => $maxDpd,
            'aging_bucket' => $this->classifyAgingBucket($maxDpd)['label'],
            'oldest_overdue_date' => $oldestOverdueDate,
            'total_overdue_installments_count' => $totalOverdueInstallments,
            'loans' => $loansDetails,
        ];
    }

    /**
     * Calculate Portfolio at Risk (PAR) and Overdue Metrics for a Company / Branch.
     */
    public function getBranchParMetrics(int $companyId, ?int $branchId = null, ?string $asOfDate = null): array
    {
        $asOf = $this->getNormalizedDate($asOfDate);

        $loansQuery = LoanAccount::where('company_id', $companyId)
            ->whereIn('status', ['active', 'defaulted'])
            ->with(['installments']);

        if ($branchId) {
            $loansQuery->where('branch_id', $branchId);
        }

        $loans = $loansQuery->get();

        $totalActiveLoans = $loans->count();
        $totalActivePortfolio = 0.00;
        $totalOverdueAmount = 0.00;
        $delinquentLoansCount = 0;
        $maxDpd = 0;

        $par30Principal = 0.00;
        $par60Principal = 0.00;
        $par90Principal = 0.00;

        $agingBreakdown = [
            'current' => ['count' => 0, 'principal' => 0.00, 'overdue' => 0.00],
            '1_30' => ['count' => 0, 'principal' => 0.00, 'overdue' => 0.00],
            '31_60' => ['count' => 0, 'principal' => 0.00, 'overdue' => 0.00],
            '61_90' => ['count' => 0, 'principal' => 0.00, 'overdue' => 0.00],
            '90_plus' => ['count' => 0, 'principal' => 0.00, 'overdue' => 0.00],
        ];

        foreach ($loans as $loan) {
            $details = $this->getLoanOverdueDetails($loan, $asOfDate);
            $prinOut = $details['principal_outstanding'];
            $totalActivePortfolio += $prinOut;

            $dpd = $details['dpd'];
            if ($dpd > $maxDpd) {
                $maxDpd = $dpd;
            }

            if ($details['is_delinquent']) {
                $delinquentLoansCount++;
                $totalOverdueAmount += $details['overdue_amount'];
            }

            if ($dpd > 30) {
                $par30Principal += $prinOut;
            }
            if ($dpd > 60) {
                $par60Principal += $prinOut;
            }
            if ($dpd > 90) {
                $par90Principal += $prinOut;
            }

            $bucketKey = $details['aging_bucket_key'];
            $agingBreakdown[$bucketKey]['count']++;
            $agingBreakdown[$bucketKey]['principal'] += $prinOut;
            $agingBreakdown[$bucketKey]['overdue'] += $details['overdue_amount'];
        }

        $totalActivePortfolio = round($totalActivePortfolio, 2);
        $totalOverdueAmount = round($totalOverdueAmount, 2);
        $par30Principal = round($par30Principal, 2);
        $par60Principal = round($par60Principal, 2);
        $par90Principal = round($par90Principal, 2);

        $par30Pct = $totalActivePortfolio > 0 ? round(($par30Principal / $totalActivePortfolio) * 100, 2) : 0.00;
        $par60Pct = $totalActivePortfolio > 0 ? round(($par60Principal / $totalActivePortfolio) * 100, 2) : 0.00;
        $par90Pct = $totalActivePortfolio > 0 ? round(($par90Principal / $totalActivePortfolio) * 100, 2) : 0.00;
        $overdueRatePct = $totalActivePortfolio > 0 ? round(($totalOverdueAmount / $totalActivePortfolio) * 100, 2) : 0.00;

        return [
            'as_of_date' => $asOf->toDateString(),
            'total_active_loans' => $totalActiveLoans,
            'total_active_portfolio' => $totalActivePortfolio,
            'total_overdue_amount' => $totalOverdueAmount,
            'delinquent_loans_count' => $delinquentLoansCount,
            'max_dpd' => $maxDpd,
            'overdue_rate_pct' => $overdueRatePct,
            'par_30_amount' => $par30Principal,
            'par_30_pct' => $par30Pct,
            'par_60_amount' => $par60Principal,
            'par_60_pct' => $par60Pct,
            'par_90_amount' => $par90Principal,
            'par_90_pct' => $par90Pct,
            'aging_breakdown' => $agingBreakdown,
        ];
    }

    /**
     * Get Comparison Report across all Branches of a Company.
     */
    public function getCompanyBranchesComparison(int $companyId, ?string $asOfDate = null): Collection
    {
        $branches = Branch::where('company_id', $companyId)->where('is_active', true)->get();
        $comparison = collect();

        foreach ($branches as $branch) {
            $metrics = $this->getBranchParMetrics($companyId, $branch->id, $asOfDate);
            $comparison->push(array_merge([
                'branch_id' => $branch->id,
                'branch_name' => $branch->name,
                'branch_code' => $branch->code,
            ], $metrics));
        }

        return $comparison;
    }

    /**
     * Query / List of Overdue Loan Accounts with Filters & Pagination Support.
     */
    public function getOverdueLoans(int $companyId, array $filters = [], ?string $asOfDate = null): Collection
    {
        $asOf = $this->getNormalizedDate($asOfDate);
        $todayStr = $asOf->toDateString();

        $query = LoanAccount::where('company_id', $companyId)
            ->whereIn('status', ['active', 'defaulted'])
            ->whereHas('installments', function ($q) use ($todayStr) {
                $q->where('due_date', '<', $todayStr)
                  ->whereRaw('(installment_amount - (principal_paid + interest_paid + fee_paid + penalty_paid)) > 0.01');
            })
            ->with(['customer', 'branch', 'loanScheme', 'installments']);

        if (!empty($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }
        if (!empty($filters['loan_scheme_id'])) {
            $query->where('loan_scheme_id', $filters['loan_scheme_id']);
        }
        if (!empty($filters['loan_type'])) {
            $query->where('loan_type', $filters['loan_type']);
        }
        if (!empty($filters['search'])) {
            $term = trim($filters['search']);
            $query->where(function ($q) use ($term) {
                $q->where('loan_number', 'like', "%{$term}%")
                  ->orWhereHas('customer', function ($cq) use ($term) {
                      $cq->where('first_name', 'like', "%{$term}%")
                         ->orWhere('last_name', 'like', "%{$term}%")
                         ->orWhere('customer_code', 'like', "%{$term}%")
                         ->orWhere('mobile_number', 'like', "%{$term}%");
                  });
            });
        }

        $loans = $query->get();
        $results = collect();

        foreach ($loans as $loan) {
            $details = $this->getLoanOverdueDetails($loan, $asOfDate);

            if (!empty($filters['dpd_bucket'])) {
                if ($details['aging_bucket_key'] !== $filters['dpd_bucket']) {
                    continue;
                }
            }

            $results->push([
                'loan' => $loan,
                'details' => $details,
            ]);
        }

        // Sort by DPD descending by default
        return $results->sortByDesc(fn($item) => $item['details']['dpd'])->values();
    }

    /**
     * Query / List of Overdue Installments with Filters.
     */
    public function getOverdueInstallments(int $companyId, array $filters = [], ?string $asOfDate = null): Collection
    {
        $asOf = $this->getNormalizedDate($asOfDate);
        $todayStr = $asOf->toDateString();

        $query = LoanInstallment::whereHas('loanAccount', function ($q) use ($companyId, $filters) {
            $q->where('company_id', $companyId)
              ->whereIn('status', ['active', 'defaulted']);

            if (!empty($filters['branch_id'])) {
                $q->where('branch_id', $filters['branch_id']);
            }
            if (!empty($filters['loan_scheme_id'])) {
                $q->where('loan_scheme_id', $filters['loan_scheme_id']);
            }
        })
        ->where('due_date', '<', $todayStr)
        ->whereRaw('(installment_amount - (principal_paid + interest_paid + fee_paid + penalty_paid)) > 0.01')
        ->with(['loanAccount.customer', 'loanAccount.branch']);

        if (!empty($filters['search'])) {
            $term = trim($filters['search']);
            $query->whereHas('loanAccount', function ($q) use ($term) {
                $q->where('loan_number', 'like', "%{$term}%")
                  ->orWhereHas('customer', function ($cq) use ($term) {
                      $cq->where('first_name', 'like', "%{$term}%")
                         ->orWhere('last_name', 'like', "%{$term}%")
                         ->orWhere('customer_code', 'like', "%{$term}%")
                         ->orWhere('mobile_number', 'like', "%{$term}%");
                  });
            });
        }

        $installments = $query->orderBy('due_date', 'asc')->get();
        $results = collect();

        foreach ($installments as $inst) {
            $dpdInfo = $this->getInstallmentDpd($inst, $asOfDate);

            $results->push([
                'installment' => $inst,
                'dpd_info' => $dpdInfo,
            ]);
        }

        return $results;
    }

    /**
     * Calculate Total Actual Past-Due Overdue Amount for an entire Branch or Company.
     * (Strictly past-due installments only, excluding future installments).
     */
    public function calculateTotalOverdueAmount(int $companyId, ?int $branchId = null, ?string $asOfDate = null): float
    {
        $asOf = $this->getNormalizedDate($asOfDate);
        $todayStr = $asOf->toDateString();

        $query = LoanInstallment::whereHas('loanAccount', function ($q) use ($companyId, $branchId) {
            $q->where('company_id', $companyId)->whereIn('status', ['active', 'defaulted']);
            if ($branchId) {
                $q->where('branch_id', $branchId);
            }
        })
        ->where('due_date', '<', $todayStr)
        ->whereRaw('(installment_amount - (principal_paid + interest_paid + fee_paid + penalty_paid)) > 0.01');

        $overdueSum = $query->sum(DB::raw('installment_amount - (principal_paid + interest_paid + fee_paid + penalty_paid)'));
        return round((float) $overdueSum, 2);
    }

    /**
     * Sync Database Statuses for Past-Due Installments (for daily command).
     * Updates database status to 'overdue' for pending/partial installments whose due date has passed.
     */
    public function syncOverdueDatabaseStatuses(?int $companyId = null, ?string $asOfDate = null): int
    {
        $asOf = $this->getNormalizedDate($asOfDate);
        $todayStr = $asOf->toDateString();

        $query = LoanInstallment::whereHas('loanAccount', function ($q) use ($companyId) {
            $q->whereIn('status', ['active', 'defaulted']);
            if ($companyId) {
                $q->where('company_id', $companyId);
            }
        })
        ->where('due_date', '<', $todayStr)
        ->whereIn('status', ['pending', 'partial'])
        ->whereRaw('(installment_amount - (principal_paid + interest_paid + fee_paid + penalty_paid)) > 0.01');

        return $query->update(['status' => 'overdue']);
    }
}
