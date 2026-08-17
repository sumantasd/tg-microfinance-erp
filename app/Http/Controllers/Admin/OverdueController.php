<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\LoanAccount;
use App\Models\LoanScheme;
use App\Services\OverdueService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OverdueController extends Controller
{
    public function __construct(protected OverdueService $overdueService) {}

    protected function resolveCompanyAndBranch(Request $request): array
    {
        $user = Auth::user();
        $companyId = $user?->company_id ?? Company::first()?->id ?? 1;
        $branchId = $request->input('branch_id');

        if ($user && !$user->isSuperAdmin()) {
            if ($user->branch_id && !$user->isCompanyAdmin()) {
                $branchId = $user->branch_id;
            }
        }

        return [$companyId, $branchId ? (int) $branchId : null];
    }

    public function dashboard(Request $request)
    {
        [$companyId, $branchId] = $this->resolveCompanyAndBranch($request);
        $asOfDate = $request->input('as_of_date') ?? now(OverdueService::TIMEZONE)->toDateString();

        $metrics = $this->overdueService->getBranchParMetrics($companyId, $branchId, $asOfDate);
        $branches = Branch::where('company_id', $companyId)->where('is_active', true)->get();
        $topOverdueLoans = $this->overdueService->getOverdueLoans($companyId, ['branch_id' => $branchId], $asOfDate)->take(10);

        return view('admin.loans.overdue.dashboard', compact(
            'metrics',
            'branches',
            'topOverdueLoans',
            'branchId',
            'asOfDate'
        ));
    }

    public function loans(Request $request)
    {
        [$companyId, $branchId] = $this->resolveCompanyAndBranch($request);
        $asOfDate = $request->input('as_of_date') ?? now(OverdueService::TIMEZONE)->toDateString();

        $filters = [
            'branch_id' => $branchId,
            'loan_scheme_id' => $request->input('loan_scheme_id'),
            'loan_type' => $request->input('loan_type'),
            'dpd_bucket' => $request->input('dpd_bucket'),
            'search' => $request->input('search'),
        ];

        $overdueLoans = $this->overdueService->getOverdueLoans($companyId, $filters, $asOfDate);
        $branches = Branch::where('company_id', $companyId)->where('is_active', true)->get();
        $loanSchemes = LoanScheme::where('company_id', $companyId)->where('is_active', true)->get();

        return view('admin.loans.overdue.loans', compact(
            'overdueLoans',
            'branches',
            'loanSchemes',
            'filters',
            'branchId',
            'asOfDate'
        ));
    }

    public function installments(Request $request)
    {
        [$companyId, $branchId] = $this->resolveCompanyAndBranch($request);
        $asOfDate = $request->input('as_of_date') ?? now(OverdueService::TIMEZONE)->toDateString();

        $filters = [
            'branch_id' => $branchId,
            'loan_scheme_id' => $request->input('loan_scheme_id'),
            'search' => $request->input('search'),
        ];

        $overdueInstallments = $this->overdueService->getOverdueInstallments($companyId, $filters, $asOfDate);
        $branches = Branch::where('company_id', $companyId)->where('is_active', true)->get();
        $loanSchemes = LoanScheme::where('company_id', $companyId)->where('is_active', true)->get();

        return view('admin.loans.overdue.installments', compact(
            'overdueInstallments',
            'branches',
            'loanSchemes',
            'filters',
            'branchId',
            'asOfDate'
        ));
    }

    public function customers(Request $request)
    {
        [$companyId, $branchId] = $this->resolveCompanyAndBranch($request);
        $asOfDate = $request->input('as_of_date') ?? now(OverdueService::TIMEZONE)->toDateString();
        $search = trim((string) $request->input('search'));

        $query = Customer::where('company_id', $companyId);
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('customer_code', 'like', "%{$search}%")
                  ->orWhere('mobile_number', 'like', "%{$search}%");
            });
        }

        // Only customers having active loans
        $query->whereHas('loanAccounts', function ($q) {
            $q->whereIn('status', ['active', 'defaulted']);
        });

        $customers = $query->with('branch')->get();
        $customerSummaries = collect();

        foreach ($customers as $customer) {
            $summary = $this->overdueService->getCustomerOverdueSummary($customer, $asOfDate);
            if ($summary['total_overdue_amount'] > 0 || $search !== '') {
                $customerSummaries->push($summary);
            }
        }

        // Sort by total overdue descending
        $customerSummaries = $customerSummaries->sortByDesc('total_overdue_amount')->values();
        $branches = Branch::where('company_id', $companyId)->where('is_active', true)->get();

        return view('admin.loans.overdue.customers', compact(
            'customerSummaries',
            'branches',
            'branchId',
            'search',
            'asOfDate'
        ));
    }

    public function customerProfile(Request $request, Customer $customer)
    {
        $user = Auth::user();
        if ($user && !$user->isSuperAdmin()) {
            if ($customer->company_id !== $user->company_id) {
                abort(403, 'Unauthorized access to customer from another company.');
            }
            if ($user->branch_id && !$user->isCompanyAdmin() && $customer->branch_id !== $user->branch_id) {
                abort(403, 'Unauthorized access to customer from another branch.');
            }
        }

        $asOfDate = $request->input('as_of_date') ?? now(OverdueService::TIMEZONE)->toDateString();
        $summary = $this->overdueService->getCustomerOverdueSummary($customer, $asOfDate);

        return view('admin.loans.overdue.customer_profile', compact(
            'customer',
            'summary',
            'asOfDate'
        ));
    }

    public function branchReport(Request $request)
    {
        [$companyId, $branchId] = $this->resolveCompanyAndBranch($request);
        $asOfDate = $request->input('as_of_date') ?? now(OverdueService::TIMEZONE)->toDateString();

        $comparison = $this->overdueService->getCompanyBranchesComparison($companyId, $asOfDate);

        return view('admin.loans.overdue.branch_report', compact(
            'comparison',
            'asOfDate'
        ));
    }
}
