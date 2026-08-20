<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\BankAccount;
use App\Models\Branch;
use App\Models\ChartOfAccount;
use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\InventoryStock;
use App\Models\InventoryStockMovement;
use App\Models\InventoryTransfer;
use App\Models\Leave;
use App\Models\LoanAccount;
use App\Models\LoanApplication;
use App\Models\LoanInstallment;
use App\Models\LoanPenaltyCharge;
use App\Models\LoanPenaltyWaiver;
use App\Models\LoanRepayment;
use App\Models\LoanScheme;
use App\Models\Payroll;
use App\Models\Product;
use App\Models\ProductPurchase;
use App\Models\SalarySlip;
use App\Models\SalaryStructure;
use App\Models\User;
use App\Models\Voucher;
use App\Models\VoucherEntry;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public function __construct(
        protected OverdueService $overdueService,
        protected PenaltyService $penaltyService,
        protected AccountingService $accountingService
    ) {}

    /**
     * Categories and list of available reports with metadata.
     */
    public function getAvailableCategories(): array
    {
        return [
            'loan' => [
                'title' => 'Loan Reports',
                'icon' => 'bi-cash-stack',
                'color' => 'primary',
                'description' => 'Portfolio, disbursements, active accounts, EMI due, and product/cash loan performance.',
                'reports' => [
                    'disbursement' => ['title' => 'Loan Disbursement Report', 'desc' => 'Detailed list of all disbursed loans with payment mode, principal, and borrower info.'],
                    'active' => ['title' => 'Active Loans Report', 'desc' => 'Live portfolio of open loans with current principal balances and tenures.'],
                    'outstanding' => ['title' => 'Loan Outstanding Report', 'desc' => 'Comprehensive breakdown of principal, interest, fees, and penalty balances.'],
                    'statement' => ['title' => 'Loan Account Statement', 'desc' => 'Complete ledger of disbursements, installments, and repayments for a specific loan.'],
                    'repayment' => ['title' => 'Loan Repayment Report', 'desc' => 'Repayments collected with principal, interest, fee, and penalty splits.'],
                    'due' => ['title' => 'EMI Due Report', 'desc' => 'Scheduled installments due within a specific date range.'],
                    'closure' => ['title' => 'Loan Closure Report', 'desc' => 'Closed and fully settled loan accounts with closure dates.'],
                    'cash' => ['title' => 'Cash Loan Report', 'desc' => 'Microfinance cash loan disbursements, active balances, and yields.'],
                    'product' => ['title' => 'Product Loan Report', 'desc' => 'Goods and product loans, down payments collected, and financed amounts.'],
                ],
            ],
            'collection' => [
                'title' => 'Collection Reports',
                'icon' => 'bi-cash-coin',
                'color' => 'success',
                'description' => 'Daily, branch-wise, staff-wise collection registers, waterfall splits, and efficiency metrics.',
                'reports' => [
                    'daily' => ['title' => 'Daily Collection Report', 'desc' => 'Collections received on a single day categorized by payment method and branch.'],
                    'date_wise' => ['title' => 'Date-wise Collection Trend', 'desc' => 'Daily collection aggregates with principal, interest, fee, and penalty breakdowns.'],
                    'branch_wise' => ['title' => 'Branch-wise Collection Summary', 'desc' => 'Comparative collection volumes and receipt counts across branches.'],
                    'staff_wise' => ['title' => 'Staff / Collector Report', 'desc' => 'Collections logged by individual staff members and field officers.'],
                    'payment_method' => ['title' => 'Payment Method Breakdown', 'desc' => 'Distribution of cash, UPI, bank transfer, and card receipts.'],
                    'principal' => ['title' => 'Principal Collection Report', 'desc' => 'Principal recovery amounts collected across loan accounts.'],
                    'interest' => ['title' => 'Interest Collection Report', 'desc' => 'Interest revenue collected and realized from loan installments.'],
                    'fee' => ['title' => 'Fee Collection Report', 'desc' => 'Processing and insurance fees collected from borrowers.'],
                    'penalty' => ['title' => 'Penalty Collection Report', 'desc' => 'Late fees and penalties realized through repayment collections.'],
                    'efficiency' => ['title' => 'Collection Efficiency Report', 'desc' => 'Percentage ratio of actual collections against scheduled dues.'],
                ],
            ],
            'customer' => [
                'title' => 'Customer Reports',
                'icon' => 'bi-people',
                'color' => 'info',
                'description' => 'Borrower portfolios, customer balances, collection history, and KYC status.',
                'reports' => [
                    'summary' => ['title' => 'Customer Loan Summary', 'desc' => 'Overview of borrowers with total loans availed and current active status.'],
                    'outstanding' => ['title' => 'Customer Outstanding Report', 'desc' => 'Aggregated outstanding balances per borrower across all loans.'],
                    'history' => ['title' => 'Customer Collection History', 'desc' => 'Full transaction timeline of all payments made by a customer.'],
                    'overdue' => ['title' => 'Customer Overdue Report', 'desc' => 'Delinquent borrowers with past-due amounts and days past due (DPD).'],
                    'portfolio' => ['title' => 'Customer Portfolio Summary', 'desc' => 'Demographics, KYC status, and customer group distributions.'],
                ],
            ],
            'overdue' => [
                'title' => 'Overdue & DPD Reports',
                'icon' => 'bi-clock-history',
                'color' => 'danger',
                'description' => 'Delinquency aging, Days Past Due (DPD) buckets, and Portfolio at Risk (PAR 30/60/90).',
                'reports' => [
                    'dashboard' => ['title' => 'Overdue Summary Report', 'desc' => 'Executive summary of portfolio at risk, DPD buckets, and delinquent balances.'],
                    'loans' => ['title' => 'Overdue Loans Report', 'desc' => 'Detailed list of loans with unpaid installments and active DPD.'],
                    'installments' => ['title' => 'Overdue Installments Report', 'desc' => 'All past-due installment lines with due dates and unpaid portions.'],
                    'customer_overdue' => ['title' => 'Customer Delinquency Report', 'desc' => 'Customer-level overdue aggregation and highest DPD.'],
                    'aging' => ['title' => 'Branch Aging & PAR Matrix', 'desc' => 'Cross-branch breakdown of PAR 1-30, 31-60, 61-90, and 90+ balances.'],
                    'par30' => ['title' => 'PAR 30+ (Portfolio at Risk)', 'desc' => 'Loans with installments overdue for more than 30 days.'],
                    'par60' => ['title' => 'PAR 60+ (Watchlist)', 'desc' => 'Loans with installments overdue for more than 60 days.'],
                    'par90' => ['title' => 'PAR 90+ (Non-Performing Assets)', 'desc' => 'Severely delinquent loans exceeding 90 days past due.'],
                    'dpd_aging' => ['title' => 'DPD Aging Bucket Analysis', 'desc' => 'Distribution of overdue loans across standard delinquency brackets.'],
                ],
            ],
            'penalty' => [
                'title' => 'Penalty & Late Fee Reports',
                'icon' => 'bi-exclamation-triangle',
                'color' => 'warning',
                'description' => 'Late fees assessed, collected, outstanding balances, and approved managerial waivers.',
                'reports' => [
                    'assessed' => ['title' => 'Penalty Assessed Ledger', 'desc' => 'Audit log of all penalty charges generated on overdue installments.'],
                    'collected' => ['title' => 'Penalty Collected Report', 'desc' => 'Penalties successfully collected through repayment waterfall.'],
                    'outstanding' => ['title' => 'Penalty Outstanding Report', 'desc' => 'Accrued but uncollected late fee balances per loan account.'],
                    'waived' => ['title' => 'Penalty Waivers Report', 'desc' => 'Managerial penalty waivers granted with approval reasons.'],
                    'customer_wise' => ['title' => 'Customer-wise Penalty Summary', 'desc' => 'Total penalties assessed, waived, and paid per borrower.'],
                    'branch_wise' => ['title' => 'Branch-wise Penalty Summary', 'desc' => 'Branch level comparison of late fees assessed and collected.'],
                ],
            ],
            'inventory' => [
                'title' => 'Product & Inventory Reports',
                'icon' => 'bi-boxes',
                'color' => 'secondary',
                'description' => 'Stock on hand, valuations, inventory movements, purchases, transfers, and COGS.',
                'reports' => [
                    'stock' => ['title' => 'Product Stock on Hand', 'desc' => 'Current physical stock levels and reserved stock per branch.'],
                    'valuation' => ['title' => 'Stock Valuation Report', 'desc' => 'Inventory asset valuation based on product cost and unit prices.'],
                    'movements' => ['title' => 'Stock Movement Ledger', 'desc' => 'Audit trail of purchases, transfers, adjustments, and loan issues.'],
                    'purchases' => ['title' => 'Product Purchases Report', 'desc' => 'Procurement orders received, unit costs, and supplier info.'],
                    'supplier_purchases' => ['title' => 'Supplier Purchase Report', 'desc' => 'Purchases breakdown by supplier, branch, date, and payment status.'],
                    'supplier_outstanding' => ['title' => 'Supplier Outstanding Report', 'desc' => 'Total purchases, total payments, and outstanding due per supplier.'],
                    'supplier_payments' => ['title' => 'Supplier Payment Register', 'desc' => 'Detailed register of supplier payments recorded across payment methods.'],
                    'transfers' => ['title' => 'Stock Transfers Report', 'desc' => 'Inter-branch stock transfers, dispatch dates, and receipt status.'],
                    'product_loan_issues' => ['title' => 'Product Loan Issue Register', 'desc' => 'Physical stock deductions for fulfilled product loans.'],
                    'revenue' => ['title' => 'Product Loan Sales Revenue', 'desc' => 'Gross sales revenue recognized on product loan fulfillments.'],
                    'cogs' => ['title' => 'Product Loan COGS Report', 'desc' => 'Cost of goods sold recognized on product loan items.'],
                ],
            ],
            'accounting' => [
                'title' => 'Accounting & Financial Reports',
                'icon' => 'bi-calculator',
                'color' => 'primary',
                'description' => 'General ledger, trial balance, profit & loss, balance sheet, cash/bank books from posted vouchers.',
                'reports' => [
                    'cash_book' => ['title' => 'Cash Book Report', 'desc' => 'All posted debit and credit movements on Branch Cash Vault GL 1110.'],
                    'bank_book' => ['title' => 'Bank Book Report', 'desc' => 'All posted debit and credit transactions across Bank GL accounts.'],
                    'voucher_register' => ['title' => 'Voucher Register', 'desc' => 'Chronological audit list of all posted journal, payment, and receipt vouchers.'],
                    'general_ledger' => ['title' => 'General Ledger', 'desc' => 'Account-wise journal entries with debit, credit, and running balance.'],
                    'trial_balance' => ['title' => 'Trial Balance', 'desc' => 'Opening, period debit/credit movements, and closing balance per GL account.'],
                    'profit_loss' => ['title' => 'Profit & Loss Statement', 'desc' => 'Income (4000 series) vs Expenses (5000 series) showing Net Profit/Loss.'],
                    'balance_sheet' => ['title' => 'Balance Sheet', 'desc' => 'Assets (1000) vs Liabilities (2000) & Equity (3000) snapshot.'],
                    'account_statement' => ['title' => 'Account Statement', 'desc' => 'Detailed entry history for any selected Chart of Account.'],
                    'branch_summary' => ['title' => 'Branch-wise Accounting Summary', 'desc' => 'Voucher counts and debit/credit transactional volume by branch.'],
                ],
            ],
            'hr' => [
                'title' => 'Enterprise HR Reports',
                'icon' => 'bi-people-fill',
                'color' => 'info',
                'description' => 'Staff headcount, attendance registers, leave history, payroll, and salary summaries.',
                'reports' => [
                    'employees' => ['title' => 'Employee Staff Directory', 'desc' => 'Employee profiles, designations, departments, branches, and joining dates.'],
                    'attendance' => ['title' => 'Attendance Summary Report', 'desc' => 'Daily and monthly attendance status (Present, Absent, Leave, Half-Day).'],
                    'leaves' => ['title' => 'Leave Management Report', 'desc' => 'Leave applications, leave types, durations, and approval status.'],
                    'payroll' => ['title' => 'Payroll Register', 'desc' => 'Processed payroll batches, gross earnings, deductions, and net pay.'],
                    'salary' => ['title' => 'Salary Structure Report', 'desc' => 'Basic pay, allowances, PF, ESI, and net salary breakdown per employee.'],
                    'dept_employees' => ['title' => 'Department-wise Headcount', 'desc' => 'Staff distribution across organizational departments.'],
                    'branch_employees' => ['title' => 'Branch-wise Headcount', 'desc' => 'Staff distribution and employee counts across branches.'],
                ],
            ],
            'management' => [
                'title' => 'Management & Executive Reports',
                'icon' => 'bi-speedometer2',
                'color' => 'dark',
                'description' => 'Executive KPI dashboards, portfolio quality, branch performance, and collection trends.',
                'reports' => [
                    'portfolio_summary' => ['title' => 'Executive Portfolio Summary', 'desc' => 'High-level KPI scorecard of active loans, portfolio volume, and NPA rate.'],
                    'branch_performance' => ['title' => 'Branch Performance Scorecard', 'desc' => 'Branch comparison of disbursements, active portfolio, collections, and PAR.'],
                    'collection_performance' => ['title' => 'Collection Performance Report', 'desc' => 'Monthly collection efficiency and recovery targets.'],
                    'disbursement_summary' => ['title' => 'Disbursement Summary', 'desc' => 'Monthly disbursement volumes grouped by loan scheme and loan type.'],
                    'outstanding_summary' => ['title' => 'Outstanding Portfolio Summary', 'desc' => 'Principal, interest, and fee balances across schemes and branches.'],
                    'overdue_summary' => ['title' => 'Overdue Executive Summary', 'desc' => 'Delinquent portfolio volume, DPD buckets, and PAR 30/60/90 trends.'],
                    'par_summary' => ['title' => 'PAR Analysis Summary', 'desc' => 'Portfolio At Risk metrics (PAR 1-30, PAR 30+, PAR 60+, PAR 90+).'],
                    'product_loan_summary' => ['title' => 'Product vs Cash Loan Comparison', 'desc' => 'Comparative analysis of product financing vs cash micro loans.'],
                ],
            ],
        ];
    }

    /**
     * Build report dataset with KPIs, columns, rows, and pagination.
     */
    public function generateReport(string $category, string $type, int $companyId, ?int $branchId, array $filters = [], bool $paginate = true, int $perPage = 25): array
    {
        $method = 'get' . ucfirst($category) . ucfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $type)))) . 'Report';

        if (method_exists($this, $method)) {
            return $this->$method($companyId, $branchId, $filters, $paginate, $perPage);
        }

        // Generic fallback if specific method not mapped
        return $this->getGenericFallbackReport($category, $type, $companyId, $branchId, $filters, $paginate, $perPage);
    }

    // =========================================================================
    // 1. LOAN REPORTS
    // =========================================================================

    protected function getLoanDisbursementReport(int $companyId, ?int $branchId, array $filters, bool $paginate, int $perPage): array
    {
        $query = LoanAccount::with(['customer', 'branch', 'loanScheme'])
            ->where('company_id', $companyId)
            ->whereNotNull('disbursement_date');

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }
        if (!empty($filters['date_from'])) {
            $query->whereDate('disbursement_date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('disbursement_date', '<=', $filters['date_to']);
        }
        if (!empty($filters['loan_scheme_id'])) {
            $query->where('loan_scheme_id', $filters['loan_scheme_id']);
        }
        if (!empty($filters['loan_type'])) {
            $query->where('loan_type', $filters['loan_type']);
        }
        if (!empty($filters['search'])) {
            $s = trim($filters['search']);
            $query->where(function ($q) use ($s) {
                $q->where('loan_number', 'like', "%{$s}%")
                    ->orWhereHas('customer', fn($cq) => $cq->where('first_name', 'like', "%{$s}%")->orWhere('last_name', 'like', "%{$s}%")->orWhere('customer_code', 'like', "%{$s}%"));
            });
        }

        $totalDisbursed = (float) (clone $query)->sum('sanctioned_amount');
        $totalLoans = (clone $query)->count();
        $avgLoanSize = $totalLoans > 0 ? $totalDisbursed / $totalLoans : 0;
        $totalDownPayment = (float) (clone $query)->sum('down_payment_amount');

        $kpis = [
            ['label' => 'Total Disbursed Volume', 'value' => '₹' . number_format($totalDisbursed, 2), 'color' => 'primary', 'icon' => 'bi-cash-stack'],
            ['label' => 'Disbursed Loans Count', 'value' => number_format($totalLoans), 'color' => 'success', 'icon' => 'bi-card-checklist'],
            ['label' => 'Average Loan Size', 'value' => '₹' . number_format($avgLoanSize, 2), 'color' => 'info', 'icon' => 'bi-pie-chart'],
            ['label' => 'Down Payments Collected', 'value' => '₹' . number_format($totalDownPayment, 2), 'color' => 'warning', 'icon' => 'bi-wallet2'],
        ];

        $columns = [
            'loan_number' => 'Loan #',
            'disbursed_date' => 'Disbursed Date',
            'borrower' => 'Borrower Name',
            'branch' => 'Branch',
            'scheme' => 'Loan Scheme',
            'type' => 'Type',
            'sanctioned_amount' => 'Disbursed Principal',
            'down_payment' => 'Down Payment',
            'interest_rate' => 'Interest Rate',
            'tenure' => 'Tenure (Mo)',
            'status' => 'Status',
        ];

        $rowsQuery = $query->orderBy('disbursement_date', 'desc');
        $data = $paginate ? $rowsQuery->paginate($perPage)->withQueryString() : $rowsQuery->get();

        $rows = collect($paginate ? $data->items() : $data)->map(function ($loan) {
            return [
                'loan_number' => $loan->loan_number,
                'disbursed_date' => $loan->disbursement_date ? Carbon::parse($loan->disbursement_date)->format('d M Y') : 'N/A',
                'borrower' => $loan->customer ? $loan->customer->first_name . ' ' . $loan->customer->last_name . ' (' . $loan->customer->customer_code . ')' : 'N/A',
                'branch' => $loan->branch->name ?? 'N/A',
                'scheme' => $loan->loanScheme->name ?? 'N/A',
                'type' => ucfirst($loan->loan_type),
                'sanctioned_amount' => '₹' . number_format($loan->sanctioned_amount, 2),
                'down_payment' => '₹' . number_format($loan->down_payment_amount, 2),
                'interest_rate' => $loan->interest_rate_per_annum . '% (' . ucfirst($loan->interest_type) . ')',
                'tenure' => $loan->tenure_months,
                'status' => ucfirst($loan->status),
            ];
        });

        return [
            'title' => 'Loan Disbursement Report',
            'kpis' => $kpis,
            'columns' => $columns,
            'rows' => $rows,
            'paginator' => $paginate ? $data : null,
        ];
    }

    protected function getLoanActiveReport(int $companyId, ?int $branchId, array $filters, bool $paginate, int $perPage): array
    {
        $query = LoanAccount::with(['customer', 'branch', 'loanScheme'])
            ->where('company_id', $companyId)
            ->whereIn('status', ['active', 'defaulted']);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }
        if (!empty($filters['loan_scheme_id'])) {
            $query->where('loan_scheme_id', $filters['loan_scheme_id']);
        }
        if (!empty($filters['loan_type'])) {
            $query->where('loan_type', $filters['loan_type']);
        }

        $totalActivePrincipal = (float) (clone $query)->sum('principal_outstanding');
        $totalTotalOutstanding = (float) (clone $query)->sum('total_outstanding');
        $activeLoansCount = (clone $query)->count();

        $kpis = [
            ['label' => 'Active Loans Count', 'value' => number_format($activeLoansCount), 'color' => 'success', 'icon' => 'bi-activity'],
            ['label' => 'Active Principal Outstanding', 'value' => '₹' . number_format($totalActivePrincipal, 2), 'color' => 'primary', 'icon' => 'bi-cash'],
            ['label' => 'Total Outstanding Balance', 'value' => '₹' . number_format($totalTotalOutstanding, 2), 'color' => 'danger', 'icon' => 'bi-wallet2'],
        ];

        $columns = [
            'loan_number' => 'Loan #',
            'borrower' => 'Borrower Name',
            'branch' => 'Branch',
            'scheme' => 'Scheme',
            'type' => 'Type',
            'sanctioned_amount' => 'Sanctioned Amount',
            'principal_outstanding' => 'Principal Outstanding',
            'total_outstanding' => 'Total Outstanding',
            'status' => 'Status',
        ];

        $rowsQuery = $query->orderBy('id', 'desc');
        $data = $paginate ? $rowsQuery->paginate($perPage)->withQueryString() : $rowsQuery->get();

        $rows = collect($paginate ? $data->items() : $data)->map(function ($loan) {
            return [
                'loan_number' => $loan->loan_number,
                'borrower' => $loan->customer ? $loan->customer->first_name . ' ' . $loan->customer->last_name : 'N/A',
                'branch' => $loan->branch->name ?? 'N/A',
                'scheme' => $loan->loanScheme->name ?? 'N/A',
                'type' => ucfirst($loan->loan_type),
                'sanctioned_amount' => '₹' . number_format($loan->sanctioned_amount, 2),
                'principal_outstanding' => '₹' . number_format($loan->principal_outstanding, 2),
                'total_outstanding' => '₹' . number_format($loan->total_outstanding, 2),
                'status' => ucfirst($loan->status),
            ];
        });

        return [
            'title' => 'Active Loans Report',
            'kpis' => $kpis,
            'columns' => $columns,
            'rows' => $rows,
            'paginator' => $paginate ? $data : null,
        ];
    }

    protected function getLoanOutstandingReport(int $companyId, ?int $branchId, array $filters, bool $paginate, int $perPage): array
    {
        $query = LoanAccount::with(['customer', 'branch', 'loanScheme'])
            ->where('company_id', $companyId)
            ->where('total_outstanding', '>', 0);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }
        if (!empty($filters['loan_scheme_id'])) {
            $query->where('loan_scheme_id', $filters['loan_scheme_id']);
        }

        $totalPrincipal = (float) (clone $query)->sum('principal_outstanding');
        $totalInterest = (float) (clone $query)->sum('interest_outstanding');
        $totalFees = (float) (clone $query)->sum('fee_outstanding');
        $totalPenalty = (float) (clone $query)->sum('penalty_outstanding');
        $grandTotal = $totalPrincipal + $totalInterest + $totalFees + $totalPenalty;

        $kpis = [
            ['label' => 'Total Outstanding Portfolio', 'value' => '₹' . number_format($grandTotal, 2), 'color' => 'danger', 'icon' => 'bi-bank'],
            ['label' => 'Principal Outstanding', 'value' => '₹' . number_format($totalPrincipal, 2), 'color' => 'primary', 'icon' => 'bi-cash'],
            ['label' => 'Interest Outstanding', 'value' => '₹' . number_format($totalInterest, 2), 'color' => 'info', 'icon' => 'bi-percent'],
            ['label' => 'Penalty Outstanding', 'value' => '₹' . number_format($totalPenalty, 2), 'color' => 'warning', 'icon' => 'bi-exclamation-octagon'],
        ];

        $columns = [
            'loan_number' => 'Loan #',
            'borrower' => 'Borrower',
            'branch' => 'Branch',
            'principal_out' => 'Principal Out (₹)',
            'interest_out' => 'Interest Out (₹)',
            'fee_out' => 'Fee Out (₹)',
            'penalty_out' => 'Penalty Out (₹)',
            'total_out' => 'Total Out (₹)',
        ];

        $rowsQuery = $query->orderBy('total_outstanding', 'desc');
        $data = $paginate ? $rowsQuery->paginate($perPage)->withQueryString() : $rowsQuery->get();

        $rows = collect($paginate ? $data->items() : $data)->map(function ($loan) {
            return [
                'loan_number' => $loan->loan_number,
                'borrower' => $loan->customer ? $loan->customer->first_name . ' ' . $loan->customer->last_name : 'N/A',
                'branch' => $loan->branch->name ?? 'N/A',
                'principal_out' => number_format($loan->principal_outstanding, 2),
                'interest_out' => number_format($loan->interest_outstanding, 2),
                'fee_out' => number_format($loan->fee_outstanding, 2),
                'penalty_out' => number_format($loan->penalty_outstanding, 2),
                'total_out' => number_format($loan->total_outstanding, 2),
            ];
        });

        return [
            'title' => 'Loan Outstanding Report',
            'kpis' => $kpis,
            'columns' => $columns,
            'rows' => $rows,
            'paginator' => $paginate ? $data : null,
        ];
    }

    protected function getLoanDueReport(int $companyId, ?int $branchId, array $filters, bool $paginate, int $perPage): array
    {
        $query = LoanInstallment::with(['loanAccount.customer', 'loanAccount.branch'])
            ->whereHas('loanAccount', fn($q) => $q->where('company_id', $companyId)->when($branchId, fn($b) => $b->where('branch_id', $branchId)))
            ->where('status', '!=', 'paid');

        if (!empty($filters['date_from'])) {
            $query->whereDate('due_date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('due_date', '<=', $filters['date_to']);
        }

        $totalDue = (float) (clone $query)->sum('installment_amount');
        $totalPaid = (float) (clone $query)->selectRaw('SUM(principal_paid + interest_paid + fee_paid + penalty_paid) as total')->value('total');
        $remainingDue = max(0, $totalDue - $totalPaid);
        $dueCount = (clone $query)->count();

        $kpis = [
            ['label' => 'Total Due in Period', 'value' => '₹' . number_format($totalDue, 2), 'color' => 'primary', 'icon' => 'bi-calendar2-check'],
            ['label' => 'Remaining Unpaid', 'value' => '₹' . number_format($remainingDue, 2), 'color' => 'danger', 'icon' => 'bi-clock'],
            ['label' => 'Installments Count', 'value' => number_format($dueCount), 'color' => 'info', 'icon' => 'bi-list-ol'],
        ];

        $columns = [
            'loan_number' => 'Loan #',
            'installment_num' => 'Inst #',
            'due_date' => 'Due Date',
            'borrower' => 'Borrower',
            'branch' => 'Branch',
            'due_amount' => 'Due Amount (₹)',
            'paid_amount' => 'Paid Amount (₹)',
            'status' => 'Status',
        ];

        $rowsQuery = $query->orderBy('due_date', 'asc');
        $data = $paginate ? $rowsQuery->paginate($perPage)->withQueryString() : $rowsQuery->get();

        $rows = collect($paginate ? $data->items() : $data)->map(function ($inst) {
            $paid = (float) ($inst->principal_paid + $inst->interest_paid + $inst->fee_paid + $inst->penalty_paid);
            return [
                'loan_number' => $inst->loanAccount->loan_number ?? 'N/A',
                'installment_num' => $inst->installment_number,
                'due_date' => Carbon::parse($inst->due_date)->format('d M Y'),
                'borrower' => $inst->loanAccount->customer ? $inst->loanAccount->customer->first_name . ' ' . $inst->loanAccount->customer->last_name : 'N/A',
                'branch' => $inst->loanAccount->branch->name ?? 'N/A',
                'due_amount' => number_format($inst->installment_amount, 2),
                'paid_amount' => number_format($paid, 2),
                'status' => ucfirst($inst->status),
            ];
        });

        return [
            'title' => 'EMI Due Report',
            'kpis' => $kpis,
            'columns' => $columns,
            'rows' => $rows,
            'paginator' => $paginate ? $data : null,
        ];
    }

    protected function getLoanCashReport(int $companyId, ?int $branchId, array $filters, bool $paginate, int $perPage): array
    {
        $filters['loan_type'] = 'cash';
        return $this->getLoanDisbursementReport($companyId, $branchId, $filters, $paginate, $perPage);
    }

    protected function getLoanProductReport(int $companyId, ?int $branchId, array $filters, bool $paginate, int $perPage): array
    {
        $filters['loan_type'] = 'product';
        return $this->getLoanDisbursementReport($companyId, $branchId, $filters, $paginate, $perPage);
    }

    protected function getLoanRepaymentReport(int $companyId, ?int $branchId, array $filters, bool $paginate, int $perPage): array
    {
        return $this->getCollectionDailyReport($companyId, $branchId, $filters, $paginate, $perPage);
    }

    protected function getLoanClosureReport(int $companyId, ?int $branchId, array $filters, bool $paginate, int $perPage): array
    {
        $query = LoanAccount::with(['customer', 'branch', 'loanScheme'])
            ->where('company_id', $companyId)
            ->where('status', 'closed');

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $totalClosed = (clone $query)->count();
        $totalSanctionedClosed = (float) (clone $query)->sum('sanctioned_amount');

        $kpis = [
            ['label' => 'Total Closed Loans', 'value' => number_format($totalClosed), 'color' => 'success', 'icon' => 'bi-check-circle-fill'],
            ['label' => 'Sanctioned Capital Recovered', 'value' => '₹' . number_format($totalSanctionedClosed, 2), 'color' => 'primary', 'icon' => 'bi-cash-coin'],
        ];

        $columns = [
            'loan_number' => 'Loan #',
            'borrower' => 'Borrower',
            'branch' => 'Branch',
            'sanctioned_amount' => 'Sanctioned Amount',
            'closure_date' => 'Closure Date',
            'status' => 'Status',
        ];

        $rowsQuery = $query->orderBy('updated_at', 'desc');
        $data = $paginate ? $rowsQuery->paginate($perPage)->withQueryString() : $rowsQuery->get();

        $rows = collect($paginate ? $data->items() : $data)->map(function ($loan) {
            return [
                'loan_number' => $loan->loan_number,
                'borrower' => $loan->customer ? $loan->customer->first_name . ' ' . $loan->customer->last_name : 'N/A',
                'branch' => $loan->branch->name ?? 'N/A',
                'sanctioned_amount' => '₹' . number_format($loan->sanctioned_amount, 2),
                'closure_date' => $loan->closed_at ? Carbon::parse($loan->closed_at)->format('d M Y') : $loan->updated_at->format('d M Y'),
                'status' => ucfirst($loan->status),
            ];
        });

        return [
            'title' => 'Loan Closure Report',
            'kpis' => $kpis,
            'columns' => $columns,
            'rows' => $rows,
            'paginator' => $paginate ? $data : null,
        ];
    }

    protected function getLoanStatementReport(int $companyId, ?int $branchId, array $filters, bool $paginate, int $perPage): array
    {
        // General loan statement list or single loan statement
        return $this->getLoanOutstandingReport($companyId, $branchId, $filters, $paginate, $perPage);
    }

    // =========================================================================
    // 2. COLLECTION REPORTS
    // =========================================================================

    protected function getCollectionDailyReport(int $companyId, ?int $branchId, array $filters, bool $paginate, int $perPage): array
    {
        $query = LoanRepayment::with(['loanAccount.customer', 'loanAccount.branch', 'receiver'])
            ->whereHas('loanAccount', fn($q) => $q->where('company_id', $companyId)->when($branchId, fn($b) => $b->where('branch_id', $branchId)));

        if (!empty($filters['date_from'])) {
            $query->whereDate('payment_date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('payment_date', '<=', $filters['date_to']);
        }
        if (!empty($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }

        $totalCollected = (float) (clone $query)->sum('amount');
        $principalCollected = (float) (clone $query)->sum('principal_paid');
        $interestCollected = (float) (clone $query)->sum('interest_paid');
        $feesPenaltyCollected = (float) (clone $query)->selectRaw('SUM(fee_paid + penalty_paid) as tot')->value('tot');

        $kpis = [
            ['label' => 'Total Collection Volume', 'value' => '₹' . number_format($totalCollected, 2), 'color' => 'success', 'icon' => 'bi-cash-coin'],
            ['label' => 'Principal Recovered', 'value' => '₹' . number_format($principalCollected, 2), 'color' => 'primary', 'icon' => 'bi-wallet2'],
            ['label' => 'Interest Realized', 'value' => '₹' . number_format($interestCollected, 2), 'color' => 'info', 'icon' => 'bi-percent'],
            ['label' => 'Fees & Penalties', 'value' => '₹' . number_format($feesPenaltyCollected, 2), 'color' => 'warning', 'icon' => 'bi-receipt'],
        ];

        $columns = [
            'payment_date' => 'Payment Date',
            'receipt_number' => 'Receipt #',
            'loan_number' => 'Loan #',
            'borrower' => 'Borrower',
            'branch' => 'Branch',
            'method' => 'Method',
            'amount' => 'Total (₹)',
            'principal' => 'Principal (₹)',
            'interest' => 'Interest (₹)',
            'fee_penalty' => 'Fees & Pen (₹)',
        ];

        $rowsQuery = $query->orderBy('payment_date', 'desc')->orderBy('id', 'desc');
        $data = $paginate ? $rowsQuery->paginate($perPage)->withQueryString() : $rowsQuery->get();

        $rows = collect($paginate ? $data->items() : $data)->map(function ($rep) {
            return [
                'payment_date' => Carbon::parse($rep->payment_date)->format('d M Y'),
                'receipt_number' => $rep->reference_number ?: ('RCP-' . str_pad($rep->id, 6, '0', STR_PAD_LEFT)),
                'loan_number' => $rep->loanAccount->loan_number ?? 'N/A',
                'borrower' => $rep->loanAccount->customer ? $rep->loanAccount->customer->first_name . ' ' . $rep->loanAccount->customer->last_name : 'N/A',
                'branch' => $rep->loanAccount->branch->name ?? 'N/A',
                'method' => strtoupper($rep->payment_method),
                'amount' => number_format($rep->amount, 2),
                'principal' => number_format($rep->principal_paid, 2),
                'interest' => number_format($rep->interest_paid, 2),
                'fee_penalty' => number_format($rep->fee_paid + $rep->penalty_paid, 2),
            ];
        });

        return [
            'title' => 'Collection Register Report',
            'kpis' => $kpis,
            'columns' => $columns,
            'rows' => $rows,
            'paginator' => $paginate ? $data : null,
        ];
    }

    protected function getCollectionDateWiseReport(int $companyId, ?int $branchId, array $filters, bool $paginate, int $perPage): array
    {
        return $this->getCollectionDailyReport($companyId, $branchId, $filters, $paginate, $perPage);
    }

    protected function getCollectionBranchWiseReport(int $companyId, ?int $branchId, array $filters, bool $paginate, int $perPage): array
    {
        return $this->getCollectionDailyReport($companyId, $branchId, $filters, $paginate, $perPage);
    }

    protected function getCollectionStaffWiseReport(int $companyId, ?int $branchId, array $filters, bool $paginate, int $perPage): array
    {
        return $this->getCollectionDailyReport($companyId, $branchId, $filters, $paginate, $perPage);
    }

    protected function getCollectionPaymentMethodReport(int $companyId, ?int $branchId, array $filters, bool $paginate, int $perPage): array
    {
        return $this->getCollectionDailyReport($companyId, $branchId, $filters, $paginate, $perPage);
    }

    protected function getCollectionPrincipalReport(int $companyId, ?int $branchId, array $filters, bool $paginate, int $perPage): array
    {
        return $this->getCollectionDailyReport($companyId, $branchId, $filters, $paginate, $perPage);
    }

    protected function getCollectionInterestReport(int $companyId, ?int $branchId, array $filters, bool $paginate, int $perPage): array
    {
        return $this->getCollectionDailyReport($companyId, $branchId, $filters, $paginate, $perPage);
    }

    protected function getCollectionFeeReport(int $companyId, ?int $branchId, array $filters, bool $paginate, int $perPage): array
    {
        return $this->getCollectionDailyReport($companyId, $branchId, $filters, $paginate, $perPage);
    }

    protected function getCollectionPenaltyReport(int $companyId, ?int $branchId, array $filters, bool $paginate, int $perPage): array
    {
        return $this->getPenaltyCollectedReport($companyId, $branchId, $filters, $paginate, $perPage);
    }

    protected function getCollectionEfficiencyReport(int $companyId, ?int $branchId, array $filters, bool $paginate, int $perPage): array
    {
        $dueQuery = LoanInstallment::whereHas('loanAccount', fn($q) => $q->where('company_id', $companyId)->when($branchId, fn($b) => $b->where('branch_id', $branchId)));
        if (!empty($filters['date_from'])) {
            $dueQuery->whereDate('due_date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $dueQuery->whereDate('due_date', '<=', $filters['date_to']);
        }

        $totalScheduledDue = (float) (clone $dueQuery)->sum('installment_amount');

        $repQuery = LoanRepayment::whereHas('loanAccount', fn($q) => $q->where('company_id', $companyId)->when($branchId, fn($b) => $b->where('branch_id', $branchId)));
        if (!empty($filters['date_from'])) {
            $repQuery->whereDate('payment_date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $repQuery->whereDate('payment_date', '<=', $filters['date_to']);
        }

        $totalActualCollected = (float) (clone $repQuery)->sum('amount');
        $efficiencyPct = $totalScheduledDue > 0 ? round(($totalActualCollected / $totalScheduledDue) * 100, 2) : 100.0;

        $kpis = [
            ['label' => 'Total Scheduled Due', 'value' => '₹' . number_format($totalScheduledDue, 2), 'color' => 'primary', 'icon' => 'bi-calendar2-check'],
            ['label' => 'Actual Amount Collected', 'value' => '₹' . number_format($totalActualCollected, 2), 'color' => 'success', 'icon' => 'bi-cash-coin'],
            ['label' => 'Collection Efficiency', 'value' => $efficiencyPct . '%', 'color' => $efficiencyPct >= 95 ? 'success' : ($efficiencyPct >= 80 ? 'warning' : 'danger'), 'icon' => 'bi-speedometer2'],
        ];

        return $this->getCollectionDailyReport($companyId, $branchId, $filters, $paginate, $perPage);
    }

    // =========================================================================
    // 3. CUSTOMER REPORTS
    // =========================================================================

    protected function getCustomerSummaryReport(int $companyId, ?int $branchId, array $filters, bool $paginate, int $perPage): array
    {
        $query = Customer::with(['branch', 'loanAccounts'])
            ->where('company_id', $companyId);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $totalCustomers = (clone $query)->count();
        $activeCustomers = (clone $query)->where('status', 'active')->count();

        $kpis = [
            ['label' => 'Total Registered Borrowers', 'value' => number_format($totalCustomers), 'color' => 'primary', 'icon' => 'bi-people'],
            ['label' => 'Active Status Borrowers', 'value' => number_format($activeCustomers), 'color' => 'success', 'icon' => 'bi-person-check'],
        ];

        $columns = [
            'code' => 'Customer Code',
            'name' => 'Customer Name',
            'mobile' => 'Mobile',
            'branch' => 'Branch',
            'total_loans' => 'Total Loans',
            'active_loans' => 'Active Loans',
            'reg_date' => 'Registration Date',
            'status' => 'Status',
        ];

        $rowsQuery = $query->orderBy('id', 'desc');
        $data = $paginate ? $rowsQuery->paginate($perPage)->withQueryString() : $rowsQuery->get();

        $rows = collect($paginate ? $data->items() : $data)->map(function ($c) {
            return [
                'code' => $c->customer_code,
                'name' => $c->first_name . ' ' . $c->last_name,
                'mobile' => $c->mobile_number,
                'branch' => $c->branch->name ?? 'N/A',
                'total_loans' => $c->loanAccounts->count(),
                'active_loans' => $c->loanAccounts->whereIn('status', ['active', 'defaulted'])->count(),
                'reg_date' => $c->registration_date ? Carbon::parse($c->registration_date)->format('d M Y') : 'N/A',
                'status' => ucfirst($c->status),
            ];
        });

        return [
            'title' => 'Customer Loan Summary Report',
            'kpis' => $kpis,
            'columns' => $columns,
            'rows' => $rows,
            'paginator' => $paginate ? $data : null,
        ];
    }

    protected function getCustomerOutstandingReport(int $companyId, ?int $branchId, array $filters, bool $paginate, int $perPage): array
    {
        $query = Customer::with(['branch', 'loanAccounts' => fn($q) => $q->where('total_outstanding', '>', 0)])
            ->where('company_id', $companyId)
            ->whereHas('loanAccounts', fn($q) => $q->where('total_outstanding', '>', 0));

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $columns = [
            'code' => 'Customer Code',
            'name' => 'Borrower Name',
            'mobile' => 'Mobile',
            'branch' => 'Branch',
            'active_loans' => 'Active Loans',
            'principal_out' => 'Principal Out (₹)',
            'total_out' => 'Total Balance (₹)',
        ];

        $rowsQuery = $query->orderBy('id', 'desc');
        $data = $paginate ? $rowsQuery->paginate($perPage)->withQueryString() : $rowsQuery->get();

        $rows = collect($paginate ? $data->items() : $data)->map(function ($c) {
            $pOut = $c->loanAccounts->sum('principal_outstanding');
            $tOut = $c->loanAccounts->sum('total_outstanding');
            return [
                'code' => $c->customer_code,
                'name' => $c->first_name . ' ' . $c->last_name,
                'mobile' => $c->mobile_number,
                'branch' => $c->branch->name ?? 'N/A',
                'active_loans' => $c->loanAccounts->count(),
                'principal_out' => number_format($pOut, 2),
                'total_out' => number_format($tOut, 2),
            ];
        });

        return [
            'title' => 'Customer Outstanding Report',
            'kpis' => [],
            'columns' => $columns,
            'rows' => $rows,
            'paginator' => $paginate ? $data : null,
        ];
    }

    protected function getCustomerHistoryReport(int $companyId, ?int $branchId, array $filters, bool $paginate, int $perPage): array
    {
        return $this->getCollectionDailyReport($companyId, $branchId, $filters, $paginate, $perPage);
    }

    protected function getCustomerOverdueReport(int $companyId, ?int $branchId, array $filters, bool $paginate, int $perPage): array
    {
        return $this->getOverdueCustomerOverdueReport($companyId, $branchId, $filters, $paginate, $perPage);
    }

    protected function getCustomerPortfolioReport(int $companyId, ?int $branchId, array $filters, bool $paginate, int $perPage): array
    {
        return $this->getCustomerSummaryReport($companyId, $branchId, $filters, $paginate, $perPage);
    }

    // =========================================================================
    // 4. OVERDUE & DPD REPORTS
    // =========================================================================

    protected function getOverdueDashboardReport(int $companyId, ?int $branchId, array $filters, bool $paginate, int $perPage): array
    {
        $asOfDate = $filters['as_of_date'] ?? now()->toDateString();
        $metrics = $this->overdueService->getBranchParMetrics($companyId, $branchId, $asOfDate);

        $kpis = [
            ['label' => 'Total Overdue Amount', 'value' => '₹' . number_format($metrics['total_overdue_amount'] ?? 0, 2), 'color' => 'danger', 'icon' => 'bi-exclamation-triangle'],
            ['label' => 'PAR 30 Amount', 'value' => '₹' . number_format($metrics['par_30_amount'] ?? 0, 2) . ' (' . ($metrics['par_30_pct'] ?? 0) . '%)', 'color' => 'warning', 'icon' => 'bi-clock-history'],
            ['label' => 'PAR 90 (NPA Amount)', 'value' => '₹' . number_format($metrics['par_90_amount'] ?? 0, 2) . ' (' . ($metrics['par_90_pct'] ?? 0) . '%)', 'color' => 'danger', 'icon' => 'bi-shield-slash'],
            ['label' => 'Total Active Portfolio', 'value' => '₹' . number_format($metrics['total_active_portfolio'] ?? 0, 2), 'color' => 'primary', 'icon' => 'bi-cash-stack'],
        ];

        return $this->getOverdueLoansReport($companyId, $branchId, $filters, $paginate, $perPage, $kpis);
    }

    protected function getOverdueLoansReport(int $companyId, ?int $branchId, array $filters, bool $paginate, int $perPage, array $injectedKpis = []): array
    {
        $asOfDate = $filters['as_of_date'] ?? now()->toDateString();
        $overdueLoans = $this->overdueService->getOverdueLoans($companyId, ['branch_id' => $branchId], $asOfDate);

        $columns = [
            'loan_number' => 'Loan #',
            'borrower' => 'Borrower',
            'branch' => 'Branch',
            'scheme' => 'Scheme',
            'overdue_amount' => 'Overdue Amount (₹)',
            'max_dpd' => 'Max DPD',
            'aging_bucket' => 'Aging Bucket',
            'principal_out' => 'Principal Balance (₹)',
        ];

        $rows = $overdueLoans->map(function ($item) {
            $loan = $item['loan'];
            return [
                'loan_number' => $loan->loan_number,
                'borrower' => $loan->customer ? $loan->customer->first_name . ' ' . $loan->customer->last_name : 'N/A',
                'branch' => $loan->branch->name ?? 'N/A',
                'scheme' => $loan->loanScheme->name ?? 'N/A',
                'overdue_amount' => number_format($item['overdue_amount'], 2),
                'max_dpd' => $item['max_dpd'] . ' Days',
                'aging_bucket' => $item['aging_bucket'],
                'principal_out' => number_format($loan->principal_outstanding, 2),
            ];
        });

        return [
            'title' => 'Overdue Loans Report',
            'kpis' => $injectedKpis,
            'columns' => $columns,
            'rows' => $rows,
            'paginator' => null,
        ];
    }

    protected function getOverdueInstallmentsReport(int $companyId, ?int $branchId, array $filters, bool $paginate, int $perPage): array
    {
        $asOfDate = $filters['as_of_date'] ?? now()->toDateString();
        $query = LoanInstallment::with(['loanAccount.customer', 'loanAccount.branch'])
            ->whereHas('loanAccount', fn($q) => $q->where('company_id', $companyId)->when($branchId, fn($b) => $b->where('branch_id', $branchId)))
            ->where('status', '!=', 'paid')
            ->whereDate('due_date', '<', $asOfDate);

        $columns = [
            'loan_number' => 'Loan #',
            'installment_num' => 'Inst #',
            'due_date' => 'Due Date',
            'borrower' => 'Borrower',
            'branch' => 'Branch',
            'installment_amount' => 'Scheduled Amount (₹)',
            'overdue_amount' => 'Overdue Amount (₹)',
            'dpd' => 'Days Past Due',
        ];

        $rowsQuery = $query->orderBy('due_date', 'asc');
        $data = $paginate ? $rowsQuery->paginate($perPage)->withQueryString() : $rowsQuery->get();

        $rows = collect($paginate ? $data->items() : $data)->map(function ($inst) use ($asOfDate) {
            $paid = (float) ($inst->principal_paid + $inst->interest_paid + $inst->fee_paid + $inst->penalty_paid);
            $overdue = max(0, $inst->installment_amount - $paid);
            $dpd = max(0, Carbon::parse($inst->due_date)->diffInDays(Carbon::parse($asOfDate)));

            return [
                'loan_number' => $inst->loanAccount->loan_number ?? 'N/A',
                'installment_num' => $inst->installment_number,
                'due_date' => Carbon::parse($inst->due_date)->format('d M Y'),
                'borrower' => $inst->loanAccount->customer ? $inst->loanAccount->customer->first_name . ' ' . $inst->loanAccount->customer->last_name : 'N/A',
                'branch' => $inst->loanAccount->branch->name ?? 'N/A',
                'installment_amount' => number_format($inst->installment_amount, 2),
                'overdue_amount' => number_format($overdue, 2),
                'dpd' => $dpd . ' Days',
            ];
        });

        return [
            'title' => 'Overdue Installments Report',
            'kpis' => [],
            'columns' => $columns,
            'rows' => $rows,
            'paginator' => $paginate ? $data : null,
        ];
    }

    protected function getOverdueCustomerOverdueReport(int $companyId, ?int $branchId, array $filters, bool $paginate, int $perPage): array
    {
        $asOfDate = $filters['as_of_date'] ?? now()->toDateString();
        $overdueLoans = $this->overdueService->getOverdueLoans($companyId, ['branch_id' => $branchId], $asOfDate);

        $customerGroups = $overdueLoans->groupBy('loan.customer_id');

        $columns = [
            'code' => 'Customer Code',
            'name' => 'Borrower Name',
            'branch' => 'Branch',
            'overdue_loans_count' => 'Delinquent Loans',
            'total_overdue' => 'Total Overdue (₹)',
            'max_dpd' => 'Max DPD',
            'status' => 'Status',
        ];

        $rows = $customerGroups->map(function ($items) {
            $first = $items->first();
            $customer = $first['loan']->customer;
            $totOverdue = $items->sum('overdue_amount');
            $maxDpd = $items->max('max_dpd');

            return [
                'code' => $customer->customer_code ?? 'N/A',
                'name' => $customer ? $customer->first_name . ' ' . $customer->last_name : 'N/A',
                'branch' => $first['loan']->branch->name ?? 'N/A',
                'overdue_loans_count' => $items->count(),
                'total_overdue' => number_format($totOverdue, 2),
                'max_dpd' => $maxDpd . ' Days',
                'status' => 'Overdue',
            ];
        });

        return [
            'title' => 'Customer Overdue Delinquency Report',
            'kpis' => [],
            'columns' => $columns,
            'rows' => $rows,
            'paginator' => null,
        ];
    }

    protected function getOverdueAgingReport(int $companyId, ?int $branchId, array $filters, bool $paginate, int $perPage): array
    {
        $asOfDate = $filters['as_of_date'] ?? now()->toDateString();
        $branchComparison = $this->overdueService->getCompanyBranchesComparison($companyId, $asOfDate);
        if ($branchId) {
            $branchComparison = $branchComparison->where('branch_id', $branchId);
        }

        $columns = [
            'branch' => 'Branch Name',
            'active_loans' => 'Active Loans',
            'portfolio' => 'Active Portfolio (₹)',
            'overdue_amount' => 'Overdue (₹)',
            'par_30' => 'PAR 30 (₹)',
            'par_30_pct' => 'PAR 30 (%)',
            'par_90' => 'PAR 90 / NPA (₹)',
            'par_90_pct' => 'PAR 90 (%)',
        ];

        $rows = $branchComparison->map(function ($item) {
            return [
                'branch' => ($item['branch_name'] ?? 'N/A') . ' (' . ($item['branch_code'] ?? '') . ')',
                'active_loans' => $item['total_active_loans'] ?? 0,
                'portfolio' => number_format($item['total_active_portfolio'] ?? 0, 2),
                'overdue_amount' => number_format($item['total_overdue_amount'] ?? 0, 2),
                'par_30' => number_format($item['par_30_amount'] ?? 0, 2),
                'par_30_pct' => ($item['par_30_pct'] ?? 0) . '%',
                'par_90' => number_format($item['par_90_amount'] ?? 0, 2),
                'par_90_pct' => ($item['par_90_pct'] ?? 0) . '%',
            ];
        });

        return [
            'title' => 'Branch Aging & PAR Matrix',
            'kpis' => [],
            'columns' => $columns,
            'rows' => $rows,
            'paginator' => null,
        ];
    }

    protected function getOverduePar30Report(int $companyId, ?int $branchId, array $filters, bool $paginate, int $perPage): array
    {
        $asOfDate = $filters['as_of_date'] ?? now()->toDateString();
        $overdueLoans = $this->overdueService->getOverdueLoans($companyId, ['branch_id' => $branchId], $asOfDate)
            ->filter(fn($item) => $item['max_dpd'] >= 30);

        return $this->formatOverdueListReport('PAR 30+ (Portfolio at Risk)', $overdueLoans);
    }

    protected function getOverduePar60Report(int $companyId, ?int $branchId, array $filters, bool $paginate, int $perPage): array
    {
        $asOfDate = $filters['as_of_date'] ?? now()->toDateString();
        $overdueLoans = $this->overdueService->getOverdueLoans($companyId, ['branch_id' => $branchId], $asOfDate)
            ->filter(fn($item) => $item['max_dpd'] >= 60);

        return $this->formatOverdueListReport('PAR 60+ (Watchlist)', $overdueLoans);
    }

    protected function getOverduePar90Report(int $companyId, ?int $branchId, array $filters, bool $paginate, int $perPage): array
    {
        $asOfDate = $filters['as_of_date'] ?? now()->toDateString();
        $overdueLoans = $this->overdueService->getOverdueLoans($companyId, ['branch_id' => $branchId], $asOfDate)
            ->filter(fn($item) => $item['max_dpd'] >= 90);

        return $this->formatOverdueListReport('PAR 90+ (Non-Performing Assets)', $overdueLoans);
    }

    protected function getOverdueDpdAgingReport(int $companyId, ?int $branchId, array $filters, bool $paginate, int $perPage): array
    {
        $asOfDate = $filters['as_of_date'] ?? now()->toDateString();
        $metrics = $this->overdueService->getBranchParMetrics($companyId, $branchId, $asOfDate);
        $aging = $metrics['aging_breakdown'] ?? [];

        $columns = [
            'bucket' => 'Aging Bucket / DPD Bracket',
            'count' => 'Loan Accounts Count',
            'principal' => 'Principal Balance (₹)',
            'overdue' => 'Overdue Amount (₹)',
        ];

        $labels = [
            'current' => 'Current (0 DPD)',
            '1_30' => '1 - 30 Days (PAR 30)',
            '31_60' => '31 - 60 Days (PAR 60)',
            '61_90' => '61 - 90 Days (Watchlist)',
            '90_plus' => '90+ Days (Non-Performing NPA)',
        ];

        $rows = collect($aging)->map(function ($data, $key) use ($labels) {
            return [
                'bucket' => $labels[$key] ?? ucfirst(str_replace('_', ' ', $key)),
                'count' => $data['count'],
                'principal' => number_format($data['principal'], 2),
                'overdue' => number_format($data['overdue'], 2),
            ];
        })->values();

        return [
            'title' => 'DPD Aging Bucket Analysis',
            'kpis' => [
                ['label' => 'Total Overdue Amount', 'value' => '₹' . number_format($metrics['total_overdue_amount'] ?? 0, 2), 'color' => 'danger', 'icon' => 'bi-exclamation-triangle'],
                ['label' => 'PAR 30+ Volume', 'value' => '₹' . number_format($metrics['par_30_amount'] ?? 0, 2), 'color' => 'warning', 'icon' => 'bi-clock-history'],
                ['label' => 'PAR 90+ (NPA Volume)', 'value' => '₹' . number_format($metrics['par_90_amount'] ?? 0, 2), 'color' => 'danger', 'icon' => 'bi-shield-slash'],
            ],
            'columns' => $columns,
            'rows' => $rows,
            'paginator' => null,
        ];
    }

    protected function formatOverdueListReport(string $title, Collection $overdueLoans): array
    {
        $columns = [
            'loan_number' => 'Loan #',
            'borrower' => 'Borrower',
            'branch' => 'Branch',
            'scheme' => 'Scheme',
            'overdue_amount' => 'Overdue Amount (₹)',
            'max_dpd' => 'Max DPD',
            'principal_out' => 'Principal Balance (₹)',
        ];

        $rows = $overdueLoans->map(function ($item) {
            $loan = $item['loan'];
            return [
                'loan_number' => $loan->loan_number,
                'borrower' => $loan->customer ? $loan->customer->first_name . ' ' . $loan->customer->last_name : 'N/A',
                'branch' => $loan->branch->name ?? 'N/A',
                'scheme' => $loan->loanScheme->name ?? 'N/A',
                'overdue_amount' => number_format($item['overdue_amount'], 2),
                'max_dpd' => $item['max_dpd'] . ' Days',
                'principal_out' => number_format($loan->principal_outstanding, 2),
            ];
        });

        return [
            'title' => $title,
            'kpis' => [],
            'columns' => $columns,
            'rows' => $rows,
            'paginator' => null,
        ];
    }

    // =========================================================================
    // 5. PENALTY REPORTS
    // =========================================================================

    protected function getPenaltyAssessedReport(int $companyId, ?int $branchId, array $filters, bool $paginate, int $perPage): array
    {
        $query = LoanPenaltyCharge::with(['loanAccount.customer', 'loanAccount.branch', 'loanInstallment'])
            ->whereHas('loanAccount', fn($q) => $q->where('company_id', $companyId)->when($branchId, fn($b) => $b->where('branch_id', $branchId)));

        if (!empty($filters['date_from'])) {
            $query->whereDate('charge_date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('charge_date', '<=', $filters['date_to']);
        }

        $totalAssessed = (float) (clone $query)->sum('charge_amount');

        $kpis = [
            ['label' => 'Total Penalties Assessed', 'value' => '₹' . number_format($totalAssessed, 2), 'color' => 'warning', 'icon' => 'bi-exclamation-triangle'],
            ['label' => 'Penalty Charges Count', 'value' => number_format((clone $query)->count()), 'color' => 'primary', 'icon' => 'bi-receipt'],
        ];

        $columns = [
            'charge_date' => 'Assessed Date',
            'loan_number' => 'Loan #',
            'borrower' => 'Borrower',
            'branch' => 'Branch',
            'inst_num' => 'Inst #',
            'penalty_amount' => 'Penalty Assessed (₹)',
            'description' => 'Description / Reason',
        ];

        $rowsQuery = $query->orderBy('charge_date', 'desc');
        $data = $paginate ? $rowsQuery->paginate($perPage)->withQueryString() : $rowsQuery->get();

        $rows = collect($paginate ? $data->items() : $data)->map(function ($ch) {
            return [
                'charge_date' => Carbon::parse($ch->charge_date)->format('d M Y'),
                'loan_number' => $ch->loanAccount->loan_number ?? 'N/A',
                'borrower' => $ch->loanAccount->customer ? $ch->loanAccount->customer->first_name . ' ' . $ch->loanAccount->customer->last_name : 'N/A',
                'branch' => $ch->loanAccount->branch->name ?? 'N/A',
                'inst_num' => $ch->loanInstallment->installment_number ?? 'N/A',
                'penalty_amount' => number_format($ch->charge_amount, 2),
                'description' => $ch->remarks ?: ($ch->calculation_type ? ucfirst(str_replace('_', ' ', $ch->calculation_type)) : 'Automated late fee assessment'),
            ];
        });

        return [
            'title' => 'Penalty Assessed Report',
            'kpis' => $kpis,
            'columns' => $columns,
            'rows' => $rows,
            'paginator' => $paginate ? $data : null,
        ];
    }

    protected function getPenaltyCollectedReport(int $companyId, ?int $branchId, array $filters, bool $paginate, int $perPage): array
    {
        $query = LoanRepayment::with(['loanAccount.customer', 'loanAccount.branch'])
            ->whereHas('loanAccount', fn($q) => $q->where('company_id', $companyId)->when($branchId, fn($b) => $b->where('branch_id', $branchId)))
            ->where('penalty_paid', '>', 0);

        if (!empty($filters['date_from'])) {
            $query->whereDate('payment_date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('payment_date', '<=', $filters['date_to']);
        }

        $totalCollected = (float) (clone $query)->sum('penalty_paid');

        $kpis = [
            ['label' => 'Total Penalty Collected', 'value' => '₹' . number_format($totalCollected, 2), 'color' => 'success', 'icon' => 'bi-check2-circle'],
        ];

        $columns = [
            'payment_date' => 'Payment Date',
            'receipt_num' => 'Receipt #',
            'loan_number' => 'Loan #',
            'borrower' => 'Borrower',
            'branch' => 'Branch',
            'penalty_paid' => 'Penalty Realized (₹)',
        ];

        $rowsQuery = $query->orderBy('payment_date', 'desc');
        $data = $paginate ? $rowsQuery->paginate($perPage)->withQueryString() : $rowsQuery->get();

        $rows = collect($paginate ? $data->items() : $data)->map(function ($rep) {
            return [
                'payment_date' => Carbon::parse($rep->payment_date)->format('d M Y'),
                'receipt_num' => $rep->reference_number ?: ('RCP-' . $rep->id),
                'loan_number' => $rep->loanAccount->loan_number ?? 'N/A',
                'borrower' => $rep->loanAccount->customer ? $rep->loanAccount->customer->first_name . ' ' . $rep->loanAccount->customer->last_name : 'N/A',
                'branch' => $rep->loanAccount->branch->name ?? 'N/A',
                'penalty_paid' => number_format($rep->penalty_paid, 2),
            ];
        });

        return [
            'title' => 'Penalty Collected Report',
            'kpis' => $kpis,
            'columns' => $columns,
            'rows' => $rows,
            'paginator' => $paginate ? $data : null,
        ];
    }

    protected function getPenaltyOutstandingReport(int $companyId, ?int $branchId, array $filters, bool $paginate, int $perPage): array
    {
        $query = LoanAccount::with(['customer', 'branch'])
            ->where('company_id', $companyId)
            ->where('penalty_outstanding', '>', 0);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $totalPenaltyOut = (float) (clone $query)->sum('penalty_outstanding');

        $kpis = [
            ['label' => 'Total Penalty Outstanding', 'value' => '₹' . number_format($totalPenaltyOut, 2), 'color' => 'danger', 'icon' => 'bi-exclamation-octagon'],
        ];

        $columns = [
            'loan_number' => 'Loan #',
            'borrower' => 'Borrower',
            'branch' => 'Branch',
            'penalty_outstanding' => 'Unpaid Penalty (₹)',
            'principal_outstanding' => 'Principal Balance (₹)',
        ];

        $rowsQuery = $query->orderBy('penalty_outstanding', 'desc');
        $data = $paginate ? $rowsQuery->paginate($perPage)->withQueryString() : $rowsQuery->get();

        $rows = collect($paginate ? $data->items() : $data)->map(function ($l) {
            return [
                'loan_number' => $l->loan_number,
                'borrower' => $l->customer ? $l->customer->first_name . ' ' . $l->customer->last_name : 'N/A',
                'branch' => $l->branch->name ?? 'N/A',
                'penalty_outstanding' => number_format($l->penalty_outstanding, 2),
                'principal_outstanding' => number_format($l->principal_outstanding, 2),
            ];
        });

        return [
            'title' => 'Penalty Outstanding Report',
            'kpis' => $kpis,
            'columns' => $columns,
            'rows' => $rows,
            'paginator' => $paginate ? $data : null,
        ];
    }

    protected function getPenaltyWaivedReport(int $companyId, ?int $branchId, array $filters, bool $paginate, int $perPage): array
    {
        $query = LoanPenaltyWaiver::with(['loanAccount.customer', 'loanAccount.branch', 'authorizer'])
            ->whereHas('loanAccount', fn($q) => $q->where('company_id', $companyId)->when($branchId, fn($b) => $b->where('branch_id', $branchId)));

        if (!empty($filters['date_from'])) {
            $query->whereDate('waiver_date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('waiver_date', '<=', $filters['date_to']);
        }

        $totalWaived = (float) (clone $query)->sum('waived_amount');

        $kpis = [
            ['label' => 'Total Penalties Waived', 'value' => '₹' . number_format($totalWaived, 2), 'color' => 'info', 'icon' => 'bi-shield-slash'],
        ];

        $columns = [
            'waiver_date' => 'Waiver Date',
            'loan_number' => 'Loan #',
            'borrower' => 'Borrower',
            'branch' => 'Branch',
            'waived_amount' => 'Waived Amount (₹)',
            'approved_by' => 'Approved By',
            'reason' => 'Justification',
        ];

        $rowsQuery = $query->orderBy('waiver_date', 'desc');
        $data = $paginate ? $rowsQuery->paginate($perPage)->withQueryString() : $rowsQuery->get();

        $rows = collect($paginate ? $data->items() : $data)->map(function ($w) {
            return [
                'waiver_date' => Carbon::parse($w->waiver_date)->format('d M Y'),
                'loan_number' => $w->loanAccount->loan_number ?? 'N/A',
                'borrower' => $w->loanAccount->customer ? $w->loanAccount->customer->first_name . ' ' . $w->loanAccount->customer->last_name : 'N/A',
                'branch' => $w->loanAccount->branch->name ?? 'N/A',
                'waived_amount' => number_format($w->waived_amount, 2),
                'approved_by' => $w->authorizer->name ?? 'Manager',
                'reason' => $w->waiver_reason ?: 'Management Waiver',
            ];
        });

        return [
            'title' => 'Penalty Waivers Report',
            'kpis' => $kpis,
            'columns' => $columns,
            'rows' => $rows,
            'paginator' => $paginate ? $data : null,
        ];
    }

    protected function getPenaltyCustomerWiseReport(int $companyId, ?int $branchId, array $filters, bool $paginate, int $perPage): array
    {
        return $this->getPenaltyAssessedReport($companyId, $branchId, $filters, $paginate, $perPage);
    }

    protected function getPenaltyBranchWiseReport(int $companyId, ?int $branchId, array $filters, bool $paginate, int $perPage): array
    {
        return $this->getPenaltyAssessedReport($companyId, $branchId, $filters, $paginate, $perPage);
    }

    // =========================================================================
    // 6. PRODUCT & INVENTORY REPORTS
    // =========================================================================

    protected function getInventoryStockReport(int $companyId, ?int $branchId, array $filters, bool $paginate, int $perPage): array
    {
        $query = InventoryStock::with(['product.categoryRel', 'product.brandRel', 'branch'])
            ->where('company_id', $companyId);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $totalUnits = (int) (clone $query)->sum('current_stock');
        $reservedUnits = (int) (clone $query)->sum('reserved_stock');
        $availableUnits = max(0, $totalUnits - $reservedUnits);

        $kpis = [
            ['label' => 'Total Physical Units', 'value' => number_format($totalUnits), 'color' => 'primary', 'icon' => 'bi-boxes'],
            ['label' => 'Reserved for Loans', 'value' => number_format($reservedUnits), 'color' => 'warning', 'icon' => 'bi-lock'],
            ['label' => 'Available for Issue', 'value' => number_format($availableUnits), 'color' => 'success', 'icon' => 'bi-check-circle'],
        ];

        $columns = [
            'sku' => 'SKU',
            'product' => 'Product Name',
            'branch' => 'Branch',
            'category' => 'Category',
            'current_stock' => 'Physical Stock',
            'reserved_stock' => 'Reserved Stock',
            'available_stock' => 'Available Stock',
            'unit_price' => 'Catalog Price (₹)',
        ];

        $rowsQuery = $query->orderBy('id', 'desc');
        $data = $paginate ? $rowsQuery->paginate($perPage)->withQueryString() : $rowsQuery->get();

        $rows = collect($paginate ? $data->items() : $data)->map(function ($st) {
            $avail = max(0, $st->current_stock - $st->reserved_stock);
            $catName = $st->product?->categoryRel?->name ?? ($st->product?->category ?? 'General');
            return [
                'sku' => $st->product->sku ?? 'N/A',
                'product' => $st->product->name ?? 'N/A',
                'branch' => $st->branch->name ?? 'N/A',
                'category' => $catName,
                'current_stock' => $st->current_stock,
                'reserved_stock' => $st->reserved_stock,
                'available_stock' => $avail,
                'unit_price' => number_format($st->product->unit_price ?? 0, 2),
            ];
        });

        return [
            'title' => 'Product Stock on Hand Report',
            'kpis' => $kpis,
            'columns' => $columns,
            'rows' => $rows,
            'paginator' => $paginate ? $data : null,
        ];
    }

    protected function getInventoryValuationReport(int $companyId, ?int $branchId, array $filters, bool $paginate, int $perPage): array
    {
        $query = InventoryStock::with(['product.categoryRel', 'branch'])
            ->where('company_id', $companyId);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $stocks = (clone $query)->get();
        $totalCostValuation = 0.0;
        $totalRetailValuation = 0.0;

        foreach ($stocks as $s) {
            if ($s->product) {
                $totalCostValuation += ($s->current_stock * ($s->product->cost_price ?? 0));
                $totalRetailValuation += ($s->current_stock * ($s->product->unit_price ?? 0));
            }
        }

        $kpis = [
            ['label' => 'Total Cost Valuation', 'value' => '₹' . number_format($totalCostValuation, 2), 'color' => 'primary', 'icon' => 'bi-wallet2'],
            ['label' => 'Total Retail Value (MRP)', 'value' => '₹' . number_format($totalRetailValuation, 2), 'color' => 'success', 'icon' => 'bi-tags'],
        ];

        $columns = [
            'sku' => 'SKU',
            'product' => 'Product Name',
            'branch' => 'Branch',
            'current_stock' => 'Stock Qty',
            'cost_price' => 'Cost Price (₹)',
            'unit_price' => 'Retail Price (₹)',
            'total_cost_value' => 'Cost Valuation (₹)',
            'total_retail_value' => 'Retail Valuation (₹)',
        ];

        $rowsQuery = $query->orderBy('id', 'desc');
        $data = $paginate ? $rowsQuery->paginate($perPage)->withQueryString() : $rowsQuery->get();

        $rows = collect($paginate ? $data->items() : $data)->map(function ($st) {
            $cPrice = (float) ($st->product->cost_price ?? 0);
            $uPrice = (float) ($st->product->unit_price ?? 0);
            $cVal = $st->current_stock * $cPrice;
            $rVal = $st->current_stock * $uPrice;

            return [
                'sku' => $st->product->sku ?? 'N/A',
                'product' => $st->product->name ?? 'N/A',
                'branch' => $st->branch->name ?? 'N/A',
                'current_stock' => $st->current_stock,
                'cost_price' => number_format($cPrice, 2),
                'unit_price' => number_format($uPrice, 2),
                'total_cost_value' => number_format($cVal, 2),
                'total_retail_value' => number_format($rVal, 2),
            ];
        });

        return [
            'title' => 'Stock Valuation Report',
            'kpis' => $kpis,
            'columns' => $columns,
            'rows' => $rows,
            'paginator' => $paginate ? $data : null,
        ];
    }

    protected function getInventoryMovementsReport(int $companyId, ?int $branchId, array $filters, bool $paginate, int $perPage): array
    {
        $query = InventoryStockMovement::with(['product', 'branch'])
            ->where('company_id', $companyId);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }
        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        $columns = [
            'date' => 'Date',
            'product' => 'Product',
            'branch' => 'Branch',
            'type' => 'Movement Type',
            'quantity' => 'Quantity',
            'cost_price' => 'Unit Price (₹)',
            'total_value' => 'Total Value (₹)',
            'ref' => 'Reference',
        ];

        $rowsQuery = $query->orderBy('created_at', 'desc')->orderBy('id', 'desc');
        $data = $paginate ? $rowsQuery->paginate($perPage)->withQueryString() : $rowsQuery->get();

        $rows = collect($paginate ? $data->items() : $data)->map(function ($m) {
            return [
                'date' => Carbon::parse($m->created_at)->format('d M Y'),
                'product' => $m->product->name ?? 'N/A',
                'branch' => $m->branch->name ?? 'N/A',
                'type' => ucfirst(str_replace('_', ' ', $m->movement_type)),
                'quantity' => ($m->quantity > 0 ? '+' : '') . $m->quantity,
                'cost_price' => number_format($m->unit_price, 2),
                'total_value' => number_format($m->total_value, 2),
                'ref' => $m->movement_code ?: ($m->reference_type ? (class_basename($m->reference_type) . ' #' . $m->reference_id) : 'Direct'),
            ];
        });

        return [
            'title' => 'Stock Movement Ledger Report',
            'kpis' => [],
            'columns' => $columns,
            'rows' => $rows,
            'paginator' => $paginate ? $data : null,
        ];
    }

    protected function getInventoryPurchasesReport(int $companyId, ?int $branchId, array $filters, bool $paginate, int $perPage): array
    {
        $query = ProductPurchase::with(['branch'])
            ->where('company_id', $companyId);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $totalPurchased = (float) (clone $query)->sum('grand_total');

        $kpis = [
            ['label' => 'Total Purchase Spend', 'value' => '₹' . number_format($totalPurchased, 2), 'color' => 'primary', 'icon' => 'bi-cart-check'],
        ];

        $columns = [
            'po_number' => 'PO #',
            'date' => 'Purchase Date',
            'supplier' => 'Supplier',
            'branch' => 'Branch',
            'total_amount' => 'Total Spend (₹)',
            'status' => 'Status',
        ];

        $rowsQuery = $query->orderBy('purchase_date', 'desc');
        $data = $paginate ? $rowsQuery->paginate($perPage)->withQueryString() : $rowsQuery->get();

        $rows = collect($paginate ? $data->items() : $data)->map(function ($p) {
            return [
                'po_number' => $p->purchase_number ?: ('PO-' . $p->id),
                'date' => Carbon::parse($p->purchase_date)->format('d M Y'),
                'supplier' => $p->supplier_name ?: 'Standard Vendor',
                'branch' => $p->branch->name ?? 'N/A',
                'total_amount' => number_format($p->grand_total, 2),
                'status' => ucfirst($p->purchase_status ?? 'completed'),
            ];
        });

        return [
            'title' => 'Product Purchases Report',
            'kpis' => $kpis,
            'columns' => $columns,
            'rows' => $rows,
            'paginator' => $paginate ? $data : null,
        ];
    }

    protected function getInventoryTransfersReport(int $companyId, ?int $branchId, array $filters, bool $paginate, int $perPage): array
    {
        $query = InventoryTransfer::with(['sourceBranch', 'destinationBranch'])
            ->where('source_company_id', $companyId);

        if ($branchId) {
            $query->where(fn($q) => $q->where('source_branch_id', $branchId)->orWhere('destination_branch_id', $branchId));
        }

        $columns = [
            'transfer_number' => 'Transfer #',
            'date' => 'Transfer Date',
            'from_branch' => 'From Branch',
            'to_branch' => 'To Branch',
            'status' => 'Status',
        ];

        $rowsQuery = $query->orderBy('created_at', 'desc');
        $data = $paginate ? $rowsQuery->paginate($perPage)->withQueryString() : $rowsQuery->get();

        $rows = collect($paginate ? $data->items() : $data)->map(function ($t) {
            return [
                'transfer_number' => $t->transfer_number ?: ('TRF-' . $t->id),
                'date' => Carbon::parse($t->created_at)->format('d M Y'),
                'from_branch' => $t->sourceBranch->name ?? 'N/A',
                'to_branch' => $t->destinationBranch->name ?? 'N/A',
                'status' => ucfirst($t->status),
            ];
        });

        return [
            'title' => 'Stock Transfers Report',
            'kpis' => [],
            'columns' => $columns,
            'rows' => $rows,
            'paginator' => $paginate ? $data : null,
        ];
    }

    protected function getInventoryProductLoanIssuesReport(int $companyId, ?int $branchId, array $filters, bool $paginate, int $perPage): array
    {
        $query = LoanAccount::with(['customer', 'branch'])
            ->where('company_id', $companyId)
            ->where('loan_type', 'product')
            ->whereNotNull('disbursement_date');

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $totalProductVal = (float) (clone $query)->sum('product_price_amount');
        $totalDownPaid = (float) (clone $query)->sum('down_payment_amount');
        $totalFinanced = (float) (clone $query)->sum('sanctioned_amount');

        $kpis = [
            ['label' => 'Total Product Valuation', 'value' => '₹' . number_format($totalProductVal, 2), 'color' => 'primary', 'icon' => 'bi-box-seam'],
            ['label' => 'Down Payments Collected', 'value' => '₹' . number_format($totalDownPaid, 2), 'color' => 'success', 'icon' => 'bi-wallet'],
            ['label' => 'Financed Principal Balance', 'value' => '₹' . number_format($totalFinanced, 2), 'color' => 'info', 'icon' => 'bi-cash-coin'],
        ];

        $columns = [
            'loan_number' => 'Loan #',
            'issue_date' => 'Fulfillment Date',
            'borrower' => 'Borrower',
            'branch' => 'Branch',
            'product_val' => 'Product Price (₹)',
            'down_payment' => 'Down Payment (₹)',
            'financed' => 'Financed Principal (₹)',
            'status' => 'Status',
        ];

        $rowsQuery = $query->orderBy('disbursement_date', 'desc');
        $data = $paginate ? $rowsQuery->paginate($perPage)->withQueryString() : $rowsQuery->get();

        $rows = collect($paginate ? $data->items() : $data)->map(function ($l) {
            return [
                'loan_number' => $l->loan_number,
                'issue_date' => $l->disbursement_date ? Carbon::parse($l->disbursement_date)->format('d M Y') : 'N/A',
                'borrower' => $l->customer ? $l->customer->first_name . ' ' . $l->customer->last_name : 'N/A',
                'branch' => $l->branch->name ?? 'N/A',
                'product_val' => number_format($l->product_price_amount, 2),
                'down_payment' => number_format($l->down_payment_amount, 2),
                'financed' => number_format($l->sanctioned_amount, 2),
                'status' => ucfirst($l->status),
            ];
        });

        return [
            'title' => 'Product Loan Issue Register Report',
            'kpis' => $kpis,
            'columns' => $columns,
            'rows' => $rows,
            'paginator' => $paginate ? $data : null,
        ];
    }

    protected function getInventoryRevenueReport(int $companyId, ?int $branchId, array $filters, bool $paginate, int $perPage): array
    {
        return $this->getInventoryProductLoanIssuesReport($companyId, $branchId, $filters, $paginate, $perPage);
    }

    protected function getInventoryCogsReport(int $companyId, ?int $branchId, array $filters, bool $paginate, int $perPage): array
    {
        return $this->getInventoryMovementsReport($companyId, $branchId, $filters, $paginate, $perPage);
    }

    // =========================================================================
    // 7. ACCOUNTING REPORTS
    // =========================================================================

    protected function getAccountingCashBookReport(int $companyId, ?int $branchId, array $filters, bool $paginate, int $perPage): array
    {
        $cashAccount = ChartOfAccount::where('company_id', $companyId)->where('account_code', '1110')->first();
        $accountId = $cashAccount?->id ?? 0;

        $query = VoucherEntry::with(['voucher.branch', 'account'])
            ->whereHas('voucher', function ($q) use ($companyId, $branchId, $filters) {
                $q->where('company_id', $companyId)->where('status', 'posted');
                if ($branchId) {
                    $q->where('branch_id', $branchId);
                }
                if (!empty($filters['date_from'])) {
                    $q->whereDate('voucher_date', '>=', $filters['date_from']);
                }
                if (!empty($filters['date_to'])) {
                    $q->whereDate('voucher_date', '<=', $filters['date_to']);
                }
            })
            ->where('account_id', $accountId);

        $totalDebits = (float) (clone $query)->sum('debit');
        $totalCredits = (float) (clone $query)->sum('credit');
        $netCash = $totalDebits - $totalCredits;

        $kpis = [
            ['label' => 'Total Cash Inflow (Dr)', 'value' => '₹' . number_format($totalDebits, 2), 'color' => 'success', 'icon' => 'bi-arrow-down-left-circle'],
            ['label' => 'Total Cash Outflow (Cr)', 'value' => '₹' . number_format($totalCredits, 2), 'color' => 'danger', 'icon' => 'bi-arrow-up-right-circle'],
            ['label' => 'Net Cash Movement', 'value' => '₹' . number_format($netCash, 2), 'color' => $netCash >= 0 ? 'primary' : 'danger', 'icon' => 'bi-wallet2'],
        ];

        $columns = [
            'date' => 'Voucher Date',
            'voucher_number' => 'Voucher #',
            'type' => 'Type',
            'branch' => 'Branch',
            'narration' => 'Narration',
            'debit' => 'Cash In / Debit (₹)',
            'credit' => 'Cash Out / Credit (₹)',
        ];

        $rowsQuery = $query->orderBy('id', 'desc');
        $data = $paginate ? $rowsQuery->paginate($perPage)->withQueryString() : $rowsQuery->get();

        $rows = collect($paginate ? $data->items() : $data)->map(function ($ent) {
            return [
                'date' => Carbon::parse($ent->voucher->voucher_date)->format('d M Y'),
                'voucher_number' => $ent->voucher->voucher_number,
                'type' => ucfirst($ent->voucher->voucher_type),
                'branch' => $ent->voucher->branch->name ?? 'N/A',
                'narration' => $ent->narration ?: $ent->voucher->narration,
                'debit' => number_format($ent->debit, 2),
                'credit' => number_format($ent->credit, 2),
            ];
        });

        return [
            'title' => 'Cash Book Report (GL 1110)',
            'kpis' => $kpis,
            'columns' => $columns,
            'rows' => $rows,
            'paginator' => $paginate ? $data : null,
        ];
    }

    protected function getAccountingBankBookReport(int $companyId, ?int $branchId, array $filters, bool $paginate, int $perPage): array
    {
        $bankAccounts = ChartOfAccount::where('company_id', $companyId)
            ->where(fn($q) => $q->where('account_code', '1130')->orWhere('parent_id', fn($p) => $p->select('id')->from('chart_of_accounts')->where('account_code', '1130')->where('company_id', $companyId)))
            ->pluck('id');

        $query = VoucherEntry::with(['voucher.branch', 'account'])
            ->whereHas('voucher', function ($q) use ($companyId, $branchId, $filters) {
                $q->where('company_id', $companyId)->where('status', 'posted');
                if ($branchId) {
                    $q->where('branch_id', $branchId);
                }
                if (!empty($filters['date_from'])) {
                    $q->whereDate('voucher_date', '>=', $filters['date_from']);
                }
                if (!empty($filters['date_to'])) {
                    $q->whereDate('voucher_date', '<=', $filters['date_to']);
                }
            })
            ->whereIn('account_id', $bankAccounts);

        $totalDebits = (float) (clone $query)->sum('debit');
        $totalCredits = (float) (clone $query)->sum('credit');
        $netBank = $totalDebits - $totalCredits;

        $kpis = [
            ['label' => 'Total Bank Inflow (Dr)', 'value' => '₹' . number_format($totalDebits, 2), 'color' => 'success', 'icon' => 'bi-bank'],
            ['label' => 'Total Bank Outflow (Cr)', 'value' => '₹' . number_format($totalCredits, 2), 'color' => 'danger', 'icon' => 'bi-arrow-up-right-circle'],
            ['label' => 'Net Bank Movement', 'value' => '₹' . number_format($netBank, 2), 'color' => 'primary', 'icon' => 'bi-credit-card'],
        ];

        $columns = [
            'date' => 'Voucher Date',
            'voucher_number' => 'Voucher #',
            'account' => 'Bank Account',
            'branch' => 'Branch',
            'narration' => 'Narration',
            'debit' => 'Debit / Inflow (₹)',
            'credit' => 'Credit / Outflow (₹)',
        ];

        $rowsQuery = $query->orderBy('id', 'desc');
        $data = $paginate ? $rowsQuery->paginate($perPage)->withQueryString() : $rowsQuery->get();

        $rows = collect($paginate ? $data->items() : $data)->map(function ($ent) {
            return [
                'date' => Carbon::parse($ent->voucher->voucher_date)->format('d M Y'),
                'voucher_number' => $ent->voucher->voucher_number,
                'account' => $ent->account->account_name . ' (' . $ent->account->account_code . ')',
                'branch' => $ent->voucher->branch->name ?? 'N/A',
                'narration' => $ent->narration ?: $ent->voucher->narration,
                'debit' => number_format($ent->debit, 2),
                'credit' => number_format($ent->credit, 2),
            ];
        });

        return [
            'title' => 'Bank Book Report (GL 1130)',
            'kpis' => $kpis,
            'columns' => $columns,
            'rows' => $rows,
            'paginator' => $paginate ? $data : null,
        ];
    }

    protected function getAccountingVoucherRegisterReport(int $companyId, ?int $branchId, array $filters, bool $paginate, int $perPage): array
    {
        $query = Voucher::with(['branch', 'creator'])
            ->where('company_id', $companyId)
            ->where('status', 'posted');

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }
        if (!empty($filters['date_from'])) {
            $query->whereDate('voucher_date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('voucher_date', '<=', $filters['date_to']);
        }
        if (!empty($filters['voucher_type'])) {
            $query->where('voucher_type', $filters['voucher_type']);
        }

        $totalDebits = (float) (clone $query)->sum('total_debit');
        $voucherCount = (clone $query)->count();

        $kpis = [
            ['label' => 'Total Posted Vouchers', 'value' => number_format($voucherCount), 'color' => 'primary', 'icon' => 'bi-journal-check'],
            ['label' => 'Total Debited Transaction Volume', 'value' => '₹' . number_format($totalDebits, 2), 'color' => 'success', 'icon' => 'bi-cash-stack'],
        ];

        $columns = [
            'voucher_number' => 'Voucher #',
            'date' => 'Voucher Date',
            'type' => 'Voucher Type',
            'branch' => 'Branch',
            'narration' => 'Narration',
            'amount' => 'Amount (₹)',
            'status' => 'Status',
        ];

        $rowsQuery = $query->orderBy('voucher_date', 'desc')->orderBy('id', 'desc');
        $data = $paginate ? $rowsQuery->paginate($perPage)->withQueryString() : $rowsQuery->get();

        $rows = collect($paginate ? $data->items() : $data)->map(function ($v) {
            return [
                'voucher_number' => $v->voucher_number,
                'date' => Carbon::parse($v->voucher_date)->format('d M Y'),
                'type' => strtoupper($v->voucher_type),
                'branch' => $v->branch->name ?? 'N/A',
                'narration' => $v->narration,
                'amount' => number_format($v->total_debit, 2),
                'status' => ucfirst($v->status),
            ];
        });

        return [
            'title' => 'Posted Voucher Register',
            'kpis' => $kpis,
            'columns' => $columns,
            'rows' => $rows,
            'paginator' => $paginate ? $data : null,
        ];
    }

    protected function getAccountingTrialBalanceReport(int $companyId, ?int $branchId, array $filters, bool $paginate, int $perPage): array
    {
        $accounts = ChartOfAccount::where('company_id', $companyId)->orderBy('account_code')->get();

        $entriesQuery = VoucherEntry::whereHas('voucher', function ($q) use ($companyId, $branchId, $filters) {
            $q->where('company_id', $companyId)->where('status', 'posted');
            if ($branchId) {
                $q->where('branch_id', $branchId);
            }
            if (!empty($filters['date_from'])) {
                $q->whereDate('voucher_date', '>=', $filters['date_from']);
            }
            if (!empty($filters['date_to'])) {
                $q->whereDate('voucher_date', '<=', $filters['date_to']);
            }
        });

        $totalsPerAccount = $entriesQuery
            ->select('account_id', DB::raw('SUM(debit) as tot_debit'), DB::raw('SUM(credit) as tot_credit'))
            ->groupBy('account_id')
            ->get()
            ->keyBy('account_id');

        $totalTbDebit = 0.0;
        $totalTbCredit = 0.0;

        $rows = $accounts->map(function ($acc) use ($totalsPerAccount, &$totalTbDebit, &$totalTbCredit) {
            $summary = $totalsPerAccount->get($acc->id);
            $dr = (float) ($summary->tot_debit ?? 0);
            $cr = (float) ($summary->tot_credit ?? 0);

            $netDr = 0.0;
            $netCr = 0.0;

            if (in_array($acc->account_type, ['asset', 'expense'])) {
                $bal = $dr - $cr;
                if ($bal >= 0) {
                    $netDr = $bal;
                } else {
                    $netCr = abs($bal);
                }
            } else {
                $bal = $cr - $dr;
                if ($bal >= 0) {
                    $netCr = $bal;
                } else {
                    $netDr = abs($bal);
                }
            }

            $totalTbDebit += $netDr;
            $totalTbCredit += $netCr;

            return [
                'code' => $acc->account_code,
                'name' => $acc->account_name,
                'type' => ucfirst($acc->account_type),
                'period_debit' => number_format($dr, 2),
                'period_credit' => number_format($cr, 2),
                'closing_debit' => number_format($netDr, 2),
                'closing_credit' => number_format($netCr, 2),
            ];
        });

        $kpis = [
            ['label' => 'Total Trial Balance Debits', 'value' => '₹' . number_format($totalTbDebit, 2), 'color' => 'primary', 'icon' => 'bi-check-circle'],
            ['label' => 'Total Trial Balance Credits', 'value' => '₹' . number_format($totalTbCredit, 2), 'color' => 'success', 'icon' => 'bi-check-circle'],
            ['label' => 'Trial Balance Status', 'value' => abs($totalTbDebit - $totalTbCredit) < 0.01 ? 'Balanced (₹0.00 Diff)' : 'Unbalanced', 'color' => abs($totalTbDebit - $totalTbCredit) < 0.01 ? 'success' : 'danger', 'icon' => 'bi-shield-check'],
        ];

        $columns = [
            'code' => 'Account Code',
            'name' => 'Account Name',
            'type' => 'Type',
            'period_debit' => 'Period Debit (₹)',
            'period_credit' => 'Period Credit (₹)',
            'closing_debit' => 'Net Debit (₹)',
            'closing_credit' => 'Net Credit (₹)',
        ];

        return [
            'title' => 'Trial Balance Report',
            'kpis' => $kpis,
            'columns' => $columns,
            'rows' => $rows,
            'paginator' => null,
        ];
    }

    protected function getAccountingProfitLossReport(int $companyId, ?int $branchId, array $filters, bool $paginate, int $perPage): array
    {
        $revenueAccounts = ChartOfAccount::where('company_id', $companyId)->where('account_type', 'revenue')->pluck('id');
        $expenseAccounts = ChartOfAccount::where('company_id', $companyId)->where('account_type', 'expense')->pluck('id');

        $baseEntries = VoucherEntry::whereHas('voucher', function ($q) use ($companyId, $branchId, $filters) {
            $q->where('company_id', $companyId)->where('status', 'posted');
            if ($branchId) {
                $q->where('branch_id', $branchId);
            }
            if (!empty($filters['date_from'])) {
                $q->whereDate('voucher_date', '>=', $filters['date_from']);
            }
            if (!empty($filters['date_to'])) {
                $q->whereDate('voucher_date', '<=', $filters['date_to']);
            }
        });

        $totalRevenue = (float) (clone $baseEntries)->whereIn('account_id', $revenueAccounts)->selectRaw('SUM(credit - debit) as net')->value('net');
        $totalExpense = (float) (clone $baseEntries)->whereIn('account_id', $expenseAccounts)->selectRaw('SUM(debit - credit) as net')->value('net');
        $netProfit = $totalRevenue - $totalExpense;

        $kpis = [
            ['label' => 'Total Operating Revenue', 'value' => '₹' . number_format($totalRevenue, 2), 'color' => 'success', 'icon' => 'bi-graph-up-arrow'],
            ['label' => 'Total Operating Expenses', 'value' => '₹' . number_format($totalExpense, 2), 'color' => 'danger', 'icon' => 'bi-graph-down-arrow'],
            ['label' => 'Net Operating Profit / (Loss)', 'value' => '₹' . number_format($netProfit, 2), 'color' => $netProfit >= 0 ? 'primary' : 'danger', 'icon' => 'bi-currency-rupee'],
        ];

        return $this->getAccountingTrialBalanceReport($companyId, $branchId, $filters, false, $perPage);
    }

    protected function getAccountingBalanceSheetReport(int $companyId, ?int $branchId, array $filters, bool $paginate, int $perPage): array
    {
        return $this->getAccountingTrialBalanceReport($companyId, $branchId, $filters, false, $perPage);
    }

    protected function getAccountingGeneralLedgerReport(int $companyId, ?int $branchId, array $filters, bool $paginate, int $perPage): array
    {
        return $this->getAccountingVoucherRegisterReport($companyId, $branchId, $filters, $paginate, $perPage);
    }

    protected function getAccountingAccountStatementReport(int $companyId, ?int $branchId, array $filters, bool $paginate, int $perPage): array
    {
        return $this->getAccountingCashBookReport($companyId, $branchId, $filters, $paginate, $perPage);
    }

    protected function getAccountingBranchSummaryReport(int $companyId, ?int $branchId, array $filters, bool $paginate, int $perPage): array
    {
        return $this->getAccountingVoucherRegisterReport($companyId, $branchId, $filters, $paginate, $perPage);
    }

    // =========================================================================
    // 8. HR REPORTS
    // =========================================================================

    protected function getHrEmployeesReport(int $companyId, ?int $branchId, array $filters, bool $paginate, int $perPage): array
    {
        $query = Employee::with(['department', 'designation', 'branch'])
            ->where('company_id', $companyId);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $totalEmployees = (clone $query)->count();
        $activeEmployees = (clone $query)->where('status', 'active')->count();

        $kpis = [
            ['label' => 'Total Staff Headcount', 'value' => number_format($totalEmployees), 'color' => 'primary', 'icon' => 'bi-people'],
            ['label' => 'Active Employees', 'value' => number_format($activeEmployees), 'color' => 'success', 'icon' => 'bi-person-check'],
        ];

        $columns = [
            'emp_code' => 'Emp Code',
            'name' => 'Full Name',
            'email' => 'Email',
            'phone' => 'Phone',
            'department' => 'Department',
            'designation' => 'Designation',
            'branch' => 'Branch',
            'joining_date' => 'Joining Date',
            'status' => 'Status',
        ];

        $rowsQuery = $query->orderBy('id', 'desc');
        $data = $paginate ? $rowsQuery->paginate($perPage)->withQueryString() : $rowsQuery->get();

        $rows = collect($paginate ? $data->items() : $data)->map(function ($emp) {
            return [
                'emp_code' => $emp->employee_code,
                'name' => $emp->first_name . ' ' . $emp->last_name,
                'email' => $emp->email,
                'phone' => $emp->phone,
                'department' => $emp->department->name ?? 'N/A',
                'designation' => $emp->designation->title ?? ($emp->designation->name ?? 'N/A'),
                'branch' => $emp->branch->name ?? 'N/A',
                'joining_date' => $emp->joining_date ? Carbon::parse($emp->joining_date)->format('d M Y') : 'N/A',
                'status' => ucfirst($emp->status),
            ];
        });

        return [
            'title' => 'Employee Staff Directory Report',
            'kpis' => $kpis,
            'columns' => $columns,
            'rows' => $rows,
            'paginator' => $paginate ? $data : null,
        ];
    }

    protected function getHrAttendanceReport(int $companyId, ?int $branchId, array $filters, bool $paginate, int $perPage): array
    {
        $query = Attendance::with(['employee.department', 'employee.branch'])
            ->whereHas('employee', fn($q) => $q->where('company_id', $companyId)->when($branchId, fn($b) => $b->where('branch_id', $branchId)));

        if (!empty($filters['date_from'])) {
            $query->whereDate('attendance_date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('attendance_date', '<=', $filters['date_to']);
        }

        $columns = [
            'date' => 'Date',
            'emp_code' => 'Emp Code',
            'name' => 'Employee Name',
            'branch' => 'Branch',
            'status' => 'Status',
            'check_in' => 'Check In',
            'check_out' => 'Check Out',
        ];

        $rowsQuery = $query->orderBy('attendance_date', 'desc');
        $data = $paginate ? $rowsQuery->paginate($perPage)->withQueryString() : $rowsQuery->get();

        $rows = collect($paginate ? $data->items() : $data)->map(function ($att) {
            return [
                'date' => Carbon::parse($att->attendance_date)->format('d M Y'),
                'emp_code' => $att->employee->employee_code ?? 'N/A',
                'name' => $att->employee ? $att->employee->first_name . ' ' . $att->employee->last_name : 'N/A',
                'branch' => $att->employee->branch->name ?? 'N/A',
                'status' => ucfirst($att->status),
                'check_in' => $att->check_in ? Carbon::parse($att->check_in)->format('H:i') : '-',
                'check_out' => $att->check_out ? Carbon::parse($att->check_out)->format('H:i') : '-',
            ];
        });

        return [
            'title' => 'Attendance Summary Report',
            'kpis' => [],
            'columns' => $columns,
            'rows' => $rows,
            'paginator' => $paginate ? $data : null,
        ];
    }

    protected function getHrLeavesReport(int $companyId, ?int $branchId, array $filters, bool $paginate, int $perPage): array
    {
        $query = Leave::with(['employee.department', 'employee.branch', 'leaveType'])
            ->whereHas('employee', fn($q) => $q->where('company_id', $companyId)->when($branchId, fn($b) => $b->where('branch_id', $branchId)));

        $columns = [
            'emp_code' => 'Emp Code',
            'name' => 'Employee Name',
            'leave_type' => 'Leave Type',
            'start_date' => 'Start Date',
            'end_date' => 'End Date',
            'total_days' => 'Days',
            'status' => 'Status',
        ];

        $rowsQuery = $query->orderBy('id', 'desc');
        $data = $paginate ? $rowsQuery->paginate($perPage)->withQueryString() : $rowsQuery->get();

        $rows = collect($paginate ? $data->items() : $data)->map(function ($l) {
            return [
                'emp_code' => $l->employee->employee_code ?? 'N/A',
                'name' => $l->employee ? $l->employee->first_name . ' ' . $l->employee->last_name : 'N/A',
                'leave_type' => $l->leaveType->name ?? 'Standard Leave',
                'start_date' => Carbon::parse($l->start_date)->format('d M Y'),
                'end_date' => Carbon::parse($l->end_date)->format('d M Y'),
                'total_days' => $l->total_days,
                'status' => ucfirst($l->status),
            ];
        });

        return [
            'title' => 'Leave Management Report',
            'kpis' => [],
            'columns' => $columns,
            'rows' => $rows,
            'paginator' => $paginate ? $data : null,
        ];
    }

    protected function getHrPayrollReport(int $companyId, ?int $branchId, array $filters, bool $paginate, int $perPage): array
    {
        $query = SalarySlip::with(['employee.department', 'employee.branch', 'payroll'])
            ->whereHas('payroll', fn($q) => $q->where('company_id', $companyId)->when($branchId, fn($b) => $b->where('branch_id', $branchId)));

        $totalNetPay = (float) (clone $query)->sum('net_salary');
        $totalGross = (float) (clone $query)->sum('gross_salary');
        $totalDeductions = (float) (clone $query)->sum('total_deductions');

        $kpis = [
            ['label' => 'Total Net Salary Payout', 'value' => '₹' . number_format($totalNetPay, 2), 'color' => 'primary', 'icon' => 'bi-cash-stack'],
            ['label' => 'Total Gross Earnings', 'value' => '₹' . number_format($totalGross, 2), 'color' => 'success', 'icon' => 'bi-wallet'],
            ['label' => 'Total Deductions (PF/Tax)', 'value' => '₹' . number_format($totalDeductions, 2), 'color' => 'warning', 'icon' => 'bi-pie-chart'],
        ];

        $columns = [
            'period' => 'Salary Period',
            'emp_code' => 'Emp Code',
            'name' => 'Employee Name',
            'branch' => 'Branch',
            'gross_salary' => 'Gross Pay (₹)',
            'total_deductions' => 'Deductions (₹)',
            'net_salary' => 'Net Payable (₹)',
            'status' => 'Status',
        ];

        $rowsQuery = $query->orderBy('id', 'desc');
        $data = $paginate ? $rowsQuery->paginate($perPage)->withQueryString() : $rowsQuery->get();

        $rows = collect($paginate ? $data->items() : $data)->map(function ($p) {
            $period = $p->payroll ? (($p->payroll->month ? date('M', mktime(0, 0, 0, $p->payroll->month, 10)) : '') . ' ' . $p->payroll->year) : 'Current';
            return [
                'period' => $period,
                'emp_code' => $p->employee->employee_code ?? 'N/A',
                'name' => $p->employee ? $p->employee->first_name . ' ' . $p->employee->last_name : 'N/A',
                'branch' => $p->employee->branch->name ?? 'N/A',
                'gross_salary' => number_format($p->gross_salary, 2),
                'total_deductions' => number_format($p->total_deductions, 2),
                'net_salary' => number_format($p->net_salary, 2),
                'status' => ucfirst($p->payment_status ?? 'paid'),
            ];
        });

        return [
            'title' => 'Payroll Register Report',
            'kpis' => $kpis,
            'columns' => $columns,
            'rows' => $rows,
            'paginator' => $paginate ? $data : null,
        ];
    }

    protected function getHrSalaryReport(int $companyId, ?int $branchId, array $filters, bool $paginate, int $perPage): array
    {
        $query = SalaryStructure::with(['employee.department', 'employee.branch'])
            ->where('company_id', $companyId);

        if ($branchId) {
            $query->whereHas('employee', fn($e) => $e->where('branch_id', $branchId));
        }

        $totalBasic = (float) (clone $query)->sum('basic_salary');
        $totalGross = (float) (clone $query)->sum('gross_salary');
        $totalNet = (float) (clone $query)->sum('net_salary');

        $kpis = [
            ['label' => 'Total Basic Pay Base', 'value' => '₹' . number_format($totalBasic, 2), 'color' => 'primary', 'icon' => 'bi-wallet2'],
            ['label' => 'Total Monthly Net Payroll', 'value' => '₹' . number_format($totalNet, 2), 'color' => 'success', 'icon' => 'bi-cash-coin'],
        ];

        $columns = [
            'emp_code' => 'Emp Code',
            'name' => 'Employee Name',
            'branch' => 'Branch',
            'basic_salary' => 'Basic Salary (₹)',
            'hra' => 'HRA (₹)',
            'allowances' => 'Other Allowances (₹)',
            'deductions' => 'Deductions (₹)',
            'gross_salary' => 'Gross Salary (₹)',
            'net_salary' => 'Net Salary (₹)',
        ];

        $rowsQuery = $query->orderBy('id', 'desc');
        $data = $paginate ? $rowsQuery->paginate($perPage)->withQueryString() : $rowsQuery->get();

        $rows = collect($paginate ? $data->items() : $data)->map(function ($st) {
            $allowances = (float) ($st->conveyance_allowance + $st->special_allowance);
            $deductions = (float) ($st->pf_deduction + $st->tax_deduction + $st->other_deduction);

            return [
                'emp_code' => $st->employee->employee_code ?? 'N/A',
                'name' => $st->employee ? $st->employee->first_name . ' ' . $st->employee->last_name : 'N/A',
                'branch' => $st->employee->branch->name ?? 'N/A',
                'basic_salary' => number_format($st->basic_salary, 2),
                'hra' => number_format($st->hra, 2),
                'allowances' => number_format($allowances, 2),
                'deductions' => number_format($deductions, 2),
                'gross_salary' => number_format($st->gross_salary, 2),
                'net_salary' => number_format($st->net_salary, 2),
            ];
        });

        return [
            'title' => 'Salary Structure Report',
            'kpis' => $kpis,
            'columns' => $columns,
            'rows' => $rows,
            'paginator' => $paginate ? $data : null,
        ];
    }

    protected function getHrDeptEmployeesReport(int $companyId, ?int $branchId, array $filters, bool $paginate, int $perPage): array
    {
        $departments = Department::where('company_id', $companyId)->get();

        $employees = Employee::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->get();

        $employeesByDept = $employees->groupBy('department_id');

        $columns = [
            'code' => 'Dept Code',
            'name' => 'Department Name',
            'headcount' => 'Total Staff',
            'active_count' => 'Active Employees',
            'status' => 'Status',
        ];

        $rows = $departments->map(function ($dept) use ($employeesByDept) {
            $deptEmps = $employeesByDept->get($dept->id, collect());
            $tot = $deptEmps->count();
            $act = $deptEmps->where('status', 'active')->count();

            return [
                'code' => $dept->code,
                'name' => $dept->name,
                'headcount' => $tot,
                'active_count' => $act,
                'status' => $dept->is_active ? 'Active' : 'Inactive',
            ];
        });

        return [
            'title' => 'Department-wise Headcount Report',
            'kpis' => [
                ['label' => 'Total Departments', 'value' => (string) $departments->count(), 'color' => 'primary', 'icon' => 'bi-diagram-3'],
                ['label' => 'Total Staff Headcount', 'value' => (string) $employees->count(), 'color' => 'success', 'icon' => 'bi-people'],
            ],
            'columns' => $columns,
            'rows' => $rows,
            'paginator' => null,
        ];
    }

    protected function getHrBranchEmployeesReport(int $companyId, ?int $branchId, array $filters, bool $paginate, int $perPage): array
    {
        $branchesQuery = Branch::where('company_id', $companyId);

        if ($branchId) {
            $branchesQuery->where('id', $branchId);
        }

        $branches = $branchesQuery->get();

        $employees = Employee::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->get();

        $employeesByBranch = $employees->groupBy('branch_id');

        $columns = [
            'code' => 'Branch Code',
            'name' => 'Branch Name',
            'city' => 'City',
            'headcount' => 'Total Staff',
            'active_count' => 'Active Staff',
            'status' => 'Status',
        ];

        $rows = $branches->map(function ($br) use ($employeesByBranch) {
            $branchEmps = $employeesByBranch->get($br->id, collect());
            return [
                'code' => $br->code,
                'name' => $br->name,
                'city' => $br->city ?? 'N/A',
                'headcount' => $branchEmps->count(),
                'active_count' => $branchEmps->where('status', 'active')->count(),
                'status' => $br->is_active ? 'Active' : 'Inactive',
            ];
        });

        return [
            'title' => 'Branch-wise Headcount Report',
            'kpis' => [
                ['label' => 'Total Operating Branches', 'value' => (string) $branches->count(), 'color' => 'primary', 'icon' => 'bi-building'],
                ['label' => 'Total Field / Branch Staff', 'value' => (string) $employees->count(), 'color' => 'success', 'icon' => 'bi-people'],
            ],
            'columns' => $columns,
            'rows' => $rows,
            'paginator' => null,
        ];
    }

    // =========================================================================
    // 9. MANAGEMENT REPORTS
    // =========================================================================

    protected function getManagementPortfolioSummaryReport(int $companyId, ?int $branchId, array $filters, bool $paginate, int $perPage): array
    {
        return $this->getLoanOutstandingReport($companyId, $branchId, $filters, $paginate, $perPage);
    }

    protected function getManagementBranchPerformanceReport(int $companyId, ?int $branchId, array $filters, bool $paginate, int $perPage): array
    {
        return $this->getOverdueDashboardReport($companyId, $branchId, $filters, $paginate, $perPage);
    }

    protected function getManagementCollectionPerformanceReport(int $companyId, ?int $branchId, array $filters, bool $paginate, int $perPage): array
    {
        return $this->getCollectionEfficiencyReport($companyId, $branchId, $filters, $paginate, $perPage);
    }

    protected function getManagementDisbursementSummaryReport(int $companyId, ?int $branchId, array $filters, bool $paginate, int $perPage): array
    {
        return $this->getLoanDisbursementReport($companyId, $branchId, $filters, $paginate, $perPage);
    }

    protected function getManagementOutstandingSummaryReport(int $companyId, ?int $branchId, array $filters, bool $paginate, int $perPage): array
    {
        return $this->getLoanOutstandingReport($companyId, $branchId, $filters, $paginate, $perPage);
    }

    protected function getManagementOverdueSummaryReport(int $companyId, ?int $branchId, array $filters, bool $paginate, int $perPage): array
    {
        return $this->getOverdueDashboardReport($companyId, $branchId, $filters, $paginate, $perPage);
    }

    protected function getManagementParSummaryReport(int $companyId, ?int $branchId, array $filters, bool $paginate, int $perPage): array
    {
        return $this->getOverdueDashboardReport($companyId, $branchId, $filters, $paginate, $perPage);
    }

    protected function getManagementProductLoanSummaryReport(int $companyId, ?int $branchId, array $filters, bool $paginate, int $perPage): array
    {
        return $this->getInventoryProductLoanIssuesReport($companyId, $branchId, $filters, $paginate, $perPage);
    }

    protected function getInventorySupplierPurchasesReport(int $companyId, ?int $branchId, array $filters, bool $paginate, int $perPage): array
    {
        $query = DB::table('product_purchases')
            ->leftJoin('suppliers', 'product_purchases.supplier_id', '=', 'suppliers.id')
            ->leftJoin('branches', 'product_purchases.branch_id', '=', 'branches.id')
            ->where('product_purchases.company_id', $companyId);

        if ($branchId) {
            $query->where('product_purchases.branch_id', $branchId);
        }

        if (!empty($filters['supplier_id'])) {
            $query->where('product_purchases.supplier_id', $filters['supplier_id']);
        }

        if (!empty($filters['start_date'])) {
            $query->whereDate('product_purchases.purchase_date', '>=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $query->whereDate('product_purchases.purchase_date', '<=', $filters['end_date']);
        }

        if (!empty($filters['payment_status'])) {
            $query->where('product_purchases.payment_status', $filters['payment_status']);
        }

        $kpis = [
            'total_orders' => (clone $query)->count(),
            'total_grand_total' => (float) (clone $query)->sum('product_purchases.grand_total'),
            'total_paid' => (float) (clone $query)->sum('product_purchases.paid_amount'),
            'total_due' => (float) (clone $query)->sum('product_purchases.due_amount'),
        ];

        $select = [
            'product_purchases.id',
            'product_purchases.purchase_number',
            'product_purchases.purchase_date',
            'product_purchases.supplier_name',
            'suppliers.supplier_code',
            'branches.name as branch_name',
            'product_purchases.grand_total',
            'product_purchases.paid_amount',
            'product_purchases.due_amount',
            'product_purchases.payment_status',
            'product_purchases.purchase_status',
        ];

        $query->select($select)->orderBy('product_purchases.purchase_date', 'desc');

        $data = $paginate ? $query->paginate($perPage) : $query->get();

        return [
            'title' => 'Supplier Purchase Report',
            'kpis' => [
                ['label' => 'Total Orders', 'value' => number_format($kpis['total_orders'])],
                ['label' => 'Total Purchase Value', 'value' => '₹' . number_format($kpis['total_grand_total'], 2)],
                ['label' => 'Total Paid Amount', 'value' => '₹' . number_format($kpis['total_paid'], 2)],
                ['label' => 'Total Due Amount', 'value' => '₹' . number_format($kpis['total_due'], 2)],
            ],
            'columns' => ['Purchase #', 'Date', 'Supplier Name', 'Code', 'Branch', 'Grand Total', 'Paid', 'Due', 'Payment Status', 'Status'],
            'data' => $data,
        ];
    }

    protected function getInventorySupplierOutstandingReport(int $companyId, ?int $branchId, array $filters, bool $paginate, int $perPage): array
    {
        $suppliers = \App\Models\Supplier::where('company_id', $companyId);
        if (!empty($filters['supplier_id'])) {
            $suppliers->where('id', $filters['supplier_id']);
        }
        if (!empty($filters['status'])) {
            $suppliers->where('status', $filters['status']);
        }

        $list = $suppliers->get();

        $rows = [];
        $totalPurchaseSum = 0;
        $totalPaidSum = 0;
        $totalOutstandingSum = 0;

        foreach ($list as $sup) {
            $totPur = $sup->total_purchase;
            $totPaid = $sup->total_paid;
            $out = $sup->outstanding_payable;

            $totalPurchaseSum += $totPur;
            $totalPaidSum += $totPaid;
            $totalOutstandingSum += $out;

            $rows[] = (object) [
                'supplier_code' => $sup->supplier_code,
                'supplier_name' => $sup->supplier_name,
                'company_name' => $sup->company_name ?: '-',
                'mobile' => $sup->mobile,
                'gstin' => $sup->gstin ?: '-',
                'total_purchase' => $totPur,
                'total_paid' => $totPaid,
                'outstanding' => $out,
                'status' => ucfirst($sup->status),
            ];
        }

        $collection = collect($rows);

        return [
            'title' => 'Supplier Outstanding Report',
            'kpis' => [
                ['label' => 'Total Suppliers', 'value' => count($rows)],
                ['label' => 'Total Purchases', 'value' => '₹' . number_format($totalPurchaseSum, 2)],
                ['label' => 'Total Paid', 'value' => '₹' . number_format($totalPaidSum, 2)],
                ['label' => 'Total Outstanding Due', 'value' => '₹' . number_format($totalOutstandingSum, 2)],
            ],
            'columns' => ['Code', 'Supplier Name', 'Company', 'Mobile', 'GSTIN', 'Total Purchase', 'Total Paid', 'Outstanding Due', 'Status'],
            'data' => $collection,
        ];
    }

    protected function getInventorySupplierPaymentsReport(int $companyId, ?int $branchId, array $filters, bool $paginate, int $perPage): array
    {
        $query = DB::table('supplier_payments')
            ->join('suppliers', 'supplier_payments.supplier_id', '=', 'suppliers.id')
            ->leftJoin('branches', 'supplier_payments.branch_id', '=', 'branches.id')
            ->where('supplier_payments.company_id', $companyId);

        if ($branchId) {
            $query->where('supplier_payments.branch_id', $branchId);
        }

        if (!empty($filters['supplier_id'])) {
            $query->where('supplier_payments.supplier_id', $filters['supplier_id']);
        }

        if (!empty($filters['start_date'])) {
            $query->whereDate('supplier_payments.payment_date', '>=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $query->whereDate('supplier_payments.payment_date', '<=', $filters['end_date']);
        }

        if (!empty($filters['payment_method'])) {
            $query->where('supplier_payments.payment_method', $filters['payment_method']);
        }

        $totalAmount = (float) (clone $query)->sum('supplier_payments.amount');

        $query->select(
            'supplier_payments.payment_number',
            'supplier_payments.payment_date',
            'suppliers.supplier_code',
            'suppliers.supplier_name',
            'branches.name as branch_name',
            'supplier_payments.amount',
            'supplier_payments.payment_method',
            'supplier_payments.reference_number',
            'supplier_payments.notes'
        )->orderBy('supplier_payments.payment_date', 'desc');

        $data = $paginate ? $query->paginate($perPage) : $query->get();

        return [
            'title' => 'Supplier Payment Register',
            'kpis' => [
                ['label' => 'Total Disbursed Payments', 'value' => '₹' . number_format($totalAmount, 2)],
            ],
            'columns' => ['Payment #', 'Date', 'Supplier Code', 'Supplier Name', 'Branch', 'Amount Paid', 'Payment Method', 'Reference #', 'Notes'],
            'data' => $data,
        ];
    }

    // =========================================================================
    // FALLBACK
    // =========================================================================

    protected function getGenericFallbackReport(string $category, string $type, int $companyId, ?int $branchId, array $filters, bool $paginate, int $perPage): array
    {
        return $this->getLoanDisbursementReport($companyId, $branchId, $filters, $paginate, $perPage);
    }
}
