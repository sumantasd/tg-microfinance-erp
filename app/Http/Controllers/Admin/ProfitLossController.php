<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Company;
use App\Services\ProfitLossService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProfitLossController extends Controller
{
    public function __construct(
        protected ProfitLossService $profitLossService
    ) {}

    /**
     * Authorize Admin-Only access.
     * Enforces strict denial for Branch Managers, branch-assigned staff, and unauthorized users.
     */
    protected function authorizeAdminOnly(): void
    {
        $user = Auth::user();

        if (!$user) {
            abort(401, 'Unauthenticated.');
        }

        // Branch Managers and operational roles must NEVER have access under any circumstances
        if ($user->hasRole('Branch Manager') || ($user->branch_id && !$user->isSuperAdmin() && !$user->isCompanyAdmin())) {
            abort(403, 'Unauthorized. Profit & Loss statement is strictly restricted to Head Office and Authorized Admin roles.');
        }

        if (!$user->can('profit_loss.view') && !$user->isSuperAdmin()) {
            abort(403, 'Unauthorized. You do not possess the required profit_loss.view permission.');
        }
    }

    /**
     * Display the Enterprise Profit & Loss Statement.
     */
    public function index(Request $request): View|JsonResponse
    {
        $this->authorizeAdminOnly();

        $user = Auth::user();
        $companyId = $user->resolveScopedCompanyId($request->filled('company_id') ? (int) $request->input('company_id') : null);
        $branchId = $request->filled('branch_id') ? (int) $request->input('branch_id') : null;

        // Indian Financial Year default (April 1 to today) if not explicitly set
        $now = Carbon::now();
        $fyStartYear = $now->month >= 4 ? $now->year : $now->year - 1;
        $defaultFromDate = "{$fyStartYear}-04-01";
        $defaultToDate = $now->toDateString();

        $fromDate = $request->has('from_date')
            ? ($request->filled('from_date') ? $request->input('from_date') : null)
            : $defaultFromDate;
        $toDate = $request->has('to_date')
            ? ($request->filled('to_date') ? $request->input('to_date') : null)
            : $defaultToDate;

        $pnl = $this->profitLossService->getComprehensiveProfitLoss(
            $companyId,
            $branchId,
            $fromDate,
            $toDate
        );

        if ($request->wantsJson()) {
            return response()->json($pnl);
        }

        $companies = $user->isSuperAdmin()
            ? Company::where('is_active', true)->get()
            : Company::where('id', $companyId)->get();

        $branches = Branch::where('company_id', $companyId)
            ->where('is_active', true)
            ->get();

        return view('admin.accounting.profit-loss.index', compact(
            'pnl',
            'companies',
            'branches',
            'companyId',
            'branchId',
            'fromDate',
            'toDate'
        ));
    }

    /**
     * Export Comprehensive Profit & Loss Statement as CSV.
     */
    public function export(Request $request): StreamedResponse
    {
        $this->authorizeAdminOnly();

        $user = Auth::user();
        if (!$user->can('profit_loss.export') && !$user->isSuperAdmin()) {
            abort(403, 'Unauthorized. You do not possess the required profit_loss.export permission.');
        }

        $companyId = $user->resolveScopedCompanyId($request->filled('company_id') ? (int) $request->input('company_id') : null);
        $branchId = $request->filled('branch_id') ? (int) $request->input('branch_id') : null;

        $fromDate = $request->filled('from_date') ? $request->input('from_date') : null;
        $toDate = $request->filled('to_date') ? $request->input('to_date') : null;

        $pnl = $this->profitLossService->getComprehensiveProfitLoss(
            $companyId,
            $branchId,
            $fromDate,
            $toDate
        );

        $company = Company::find($companyId);
        $branch = $branchId ? Branch::find($branchId) : null;
        $scopeLabel = $branch ? $branch->name : 'All Branches (Consolidated)';
        $dateLabel = ($fromDate ? $fromDate : 'Beginning') . ' to ' . ($toDate ? $toDate : 'Present');

        $filename = 'profit_loss_' . ($branch ? 'BR' . $branch->id . '_' : 'Consolidated_') . date('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($pnl, $company, $scopeLabel, $dateLabel) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM for Excel
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            // Header Section
            fputcsv($handle, [$company?->name ?? 'Grihalaxmi Finance ERP', 'PROFIT & LOSS STATEMENT']);
            fputcsv($handle, ['Scope', $scopeLabel]);
            fputcsv($handle, ['Period', $dateLabel]);
            fputcsv($handle, ['Generated At', Carbon::now()->format('d M Y, h:i A')]);
            fputcsv($handle, []);

            // 1. EXECUTIVE SUMMARY
            fputcsv($handle, ['=== EXECUTIVE FINANCIAL SUMMARY ===']);
            fputcsv($handle, ['Financial Metric', 'Amount (INR)', 'Classification / Accounting Rule']);
            fputcsv($handle, ['Pending Profit (Unrealized)', number_format($pnl['pending_product_profit'], 2), 'Product Loan margin under active/partially paid status. Not counted in net profit.']);
            fputcsv($handle, ['Final Profit (Realized)', number_format($pnl['final_product_profit'], 2), 'Product Loan margin fully paid & closed. Recognized revenue.']);
            fputcsv($handle, ['Total Generated Margin', number_format($pnl['total_generated_margin'], 2), 'Sum of Pending + Finalized Product Loan Margins (Zero double-count).']);
            fputcsv($handle, ['Direct Sales Gross Profit', number_format($pnl['direct_sales_gross_profit'], 2), 'Direct Sales Revenue minus inventory COGS (discounts & taxes factored).']);
            fputcsv($handle, ['Cash Loan Operating Revenue', number_format($pnl['cash_loan_revenue'], 2), 'Interest, processing fees, insurance fees, penalties, and foreclosure fees.']);
            fputcsv($handle, ['Interest on Product Loans', number_format($pnl['interest_product_loan'], 2), 'Posted EMI interest on Product Loan accounts.']);
            fputcsv($handle, ['Other Operating Revenue', number_format($pnl['other_operating_revenue'], 2), 'Other posted 4xxx general ledger revenue accounts.']);
            fputcsv($handle, ['TOTAL RECOGNIZED OPERATING INCOME', number_format($pnl['total_recognized_income'], 2), 'Finalized Product Profit + Direct Sales Profit + Cash Loan Revenue + Product Loan Interest']);
            fputcsv($handle, ['TOTAL OPERATING EXPENSES', number_format($pnl['total_operating_expenses'], 2), 'All posted 5xxx business expenses (Salaries, Rent, Utilities, etc.). Principal excluded.']);
            fputcsv($handle, ['NET PROFIT / (LOSS)', number_format($pnl['net_profit_or_loss'], 2), 'Recognized Income minus Operating Expenses']);
            fputcsv($handle, []);

            // 2. REVENUE GL DETAILS
            fputcsv($handle, ['=== POSTED REVENUE ACCOUNTS (GL 4xxx) ===']);
            fputcsv($handle, ['Account Code', 'Account Name', 'Recognized Net Credit (INR)']);
            foreach ($pnl['accounting']['revenue_details'] as $rev) {
                fputcsv($handle, [$rev['account_code'], $rev['account_name'], number_format($rev['amount'], 2)]);
            }
            if (empty($pnl['accounting']['revenue_details'])) {
                fputcsv($handle, ['N/A', 'No revenue entries posted for this period', '0.00']);
            }
            fputcsv($handle, []);

            // 3. EXPENSE GL DETAILS
            fputcsv($handle, ['=== POSTED OPERATING EXPENSES (GL 5xxx) ===']);
            fputcsv($handle, ['Account Code', 'Account Name', 'Recognized Net Debit (INR)']);
            foreach ($pnl['accounting']['expense_details'] as $exp) {
                fputcsv($handle, [$exp['account_code'], $exp['account_name'], number_format($exp['amount'], 2)]);
            }
            if (empty($pnl['accounting']['expense_details'])) {
                fputcsv($handle, ['N/A', 'No operating expenses posted for this period', '0.00']);
            }
            fputcsv($handle, []);

            // 4. PRODUCT LOAN MARGINS AUDIT
            fputcsv($handle, ['=== PRODUCT LOAN MARGINS (TRANSACTION-LEVEL AUDIT) ===']);
            fputcsv($handle, [
                'Loan Number',
                'Borrower Name',
                'Branch',
                'Products Financed',
                'Inventory Cost (INR)',
                'Selling Price (INR)',
                'Margin Amount (INR)',
                'Profit Status',
                'Sanction Date',
                'Closed Date',
                'Finalized By',
            ]);

            // Finalized list
            foreach ($pnl['product_loan']['finalized_list'] as $row) {
                fputcsv($handle, [
                    $row['loan_number'],
                    $row['customer_name'],
                    $row['branch_name'],
                    $row['products'],
                    number_format($row['cost_price'], 2),
                    number_format($row['selling_price'], 2),
                    number_format($row['margin_amount'], 2),
                    'FINAL PROFIT (Realized)',
                    $row['sanction_date'],
                    $row['closed_at'],
                    $row['finalized_by'],
                ]);
            }

            // Pending list
            foreach ($pnl['product_loan']['pending_list'] as $row) {
                fputcsv($handle, [
                    $row['loan_number'],
                    $row['customer_name'],
                    $row['branch_name'],
                    $row['products'],
                    number_format($row['cost_price'], 2),
                    number_format($row['selling_price'], 2),
                    number_format($row['margin_amount'], 2),
                    'PENDING PROFIT (Unrealized)',
                    $row['sanction_date'],
                    'Active / In Repayment',
                    '-',
                ]);
            }
            fputcsv($handle, []);

            // 5. DIRECT PRODUCT SALES AUDIT
            fputcsv($handle, ['=== DIRECT PRODUCT SALES (TRANSACTION-LEVEL AUDIT) ===']);
            fputcsv($handle, [
                'Sale Number',
                'Customer',
                'Branch',
                'Sale Date',
                'Items Count',
                'Net Revenue (INR)',
                'COGS (INR)',
                'Gross Profit (INR)',
                'Payment Status',
            ]);

            foreach ($pnl['direct_sales']['sales_list'] as $s) {
                fputcsv($handle, [
                    $s['sale_number'],
                    $s['customer_name'],
                    $s['branch_name'],
                    $s['sale_date'],
                    $s['items_count'],
                    number_format($s['net_revenue'], 2),
                    number_format($s['cogs'], 2),
                    number_format($s['gross_profit'], 2),
                    ucfirst($s['payment_status'] ?? 'paid'),
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }
}
