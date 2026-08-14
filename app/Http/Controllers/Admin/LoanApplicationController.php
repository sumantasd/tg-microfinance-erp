<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreLoanApplicationRequest;
use App\Http\Requests\Admin\UpdateLoanApplicationRequest;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\LoanApplication;
use App\Models\LoanScheme;
use App\Models\Product;
use App\Services\LoanApplicationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LoanApplicationController extends Controller
{
    public function __construct(protected LoanApplicationService $applicationService) {}

    public function index(Request $request): View
    {
        $filters = $request->only([
            'search', 'loan_type', 'borrower_type', 'loan_scheme_id',
            'branch_id', 'status', 'start_date', 'end_date'
        ]);

        $applications = $this->applicationService->getPaginatedApplications($filters);

        $companies = Company::where('is_active', true)->get();
        $branches = Branch::where('is_active', true)->get();
        $schemes = LoanScheme::where('is_active', true)->get();

        return view('admin.loans.applications.index', compact('applications', 'filters', 'companies', 'branches', 'schemes'));
    }

    public function create(): View
    {
        $companies = Company::where('is_active', true)->get();
        $branches = Branch::where('is_active', true)->get();
        $schemes = LoanScheme::where('is_active', true)->get();
        $customers = Customer::where('status', 'active')->get();
        $groups = CustomerGroup::with('members.customer')->where('status', 'active')->get();
        $products = Product::where('is_active', true)->get();

        return view('admin.loans.applications.create', compact('companies', 'branches', 'schemes', 'customers', 'groups', 'products'));
    }

    public function store(StoreLoanApplicationRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $members = $request->input('members', []);
        $products = $request->input('products', []);

        $application = $this->applicationService->createApplication($data, $members, $products);

        return redirect()->route('admin.loan-application.show', $application->id)
            ->with('success', "Loan Application '{$application->application_number}' created successfully.");
    }

    public function show(LoanApplication $loanApplication): View
    {
        return view('admin.loans.applications.show', ['application' => $loanApplication]);
    }

    public function edit(LoanApplication $loanApplication): View
    {
        if ($loanApplication->status !== 'draft') {
            return redirect()->route('admin.loan-application.show', $loanApplication->id)
                ->with('error', 'Only draft loan applications can be edited.');
        }

        $companies = Company::where('is_active', true)->get();
        $branches = Branch::where('is_active', true)->get();
        $schemes = LoanScheme::where('is_active', true)->get();
        $customers = Customer::where('status', 'active')->get();
        $groups = CustomerGroup::with('members.customer')->where('status', 'active')->get();
        $products = Product::where('is_active', true)->get();

        return view('admin.loans.applications.edit', compact('loanApplication', 'companies', 'branches', 'schemes', 'customers', 'groups', 'products'));
    }

    public function update(UpdateLoanApplicationRequest $request, LoanApplication $loanApplication): RedirectResponse
    {
        $data = $request->validated();
        $members = $request->input('members', []);
        $products = $request->input('products', []);

        $this->applicationService->updateApplication($loanApplication, $data, $members, $products);

        return redirect()->route('admin.loan-application.show', $loanApplication->id)
            ->with('success', "Loan Application '{$loanApplication->application_number}' updated successfully.");
    }

    public function submitApplication(LoanApplication $loanApplication): RedirectResponse
    {
        $this->applicationService->submitApplication($loanApplication);
        return redirect()->back()->with('success', "Loan Application '{$loanApplication->application_number}' submitted for review.");
    }

    public function startReview(LoanApplication $loanApplication): RedirectResponse
    {
        $this->applicationService->startReview($loanApplication);
        return redirect()->back()->with('success', "Loan Application '{$loanApplication->application_number}' is now under review.");
    }

    public function approve(Request $request, LoanApplication $loanApplication): RedirectResponse
    {
        $request->validate(['approved_amount' => 'nullable|numeric|min:1']);
        $approvedAmount = $request->input('approved_amount') ? (float) $request->input('approved_amount') : null;

        $this->applicationService->approveApplication($loanApplication, $approvedAmount);

        return redirect()->back()->with('success', "Loan Application '{$loanApplication->application_number}' approved successfully. Eligible for future disbursement.");
    }

    public function reject(Request $request, LoanApplication $loanApplication): RedirectResponse
    {
        $request->validate(['rejection_reason' => 'required|string|max:255']);
        $this->applicationService->rejectApplication($loanApplication, $request->input('rejection_reason'));

        return redirect()->back()->with('success', "Loan Application '{$loanApplication->application_number}' rejected.");
    }

    public function cancel(LoanApplication $loanApplication): RedirectResponse
    {
        $this->applicationService->cancelApplication($loanApplication);
        return redirect()->back()->with('success', "Loan Application '{$loanApplication->application_number}' cancelled.");
    }
}
