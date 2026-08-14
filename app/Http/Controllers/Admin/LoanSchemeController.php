<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreLoanSchemeRequest;
use App\Http\Requests\Admin\UpdateLoanSchemeRequest;
use App\Models\Branch;
use App\Models\Company;
use App\Models\LoanScheme;
use App\Services\LoanSchemeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LoanSchemeController extends Controller
{
    public function __construct(protected LoanSchemeService $schemeService) {}

    public function index(Request $request): View
    {
        $filters = $request->only(['search', 'company_id', 'branch_id', 'loan_type', 'applicant_type', 'is_active']);
        $schemes = $this->schemeService->getPaginatedSchemes($filters);

        $companies = Company::where('is_active', true)->get();
        $branches = Branch::where('is_active', true)->get();

        return view('admin.loan-schemes.index', compact('schemes', 'filters', 'companies', 'branches'));
    }

    public function create(): View
    {
        $companies = Company::where('is_active', true)->get();
        $branches = Branch::where('is_active', true)->get();

        return view('admin.loan-schemes.create', compact('companies', 'branches'));
    }

    public function store(StoreLoanSchemeRequest $request): RedirectResponse
    {
        $scheme = $this->schemeService->createScheme($request->validated());

        return redirect()->route('admin.loan-scheme.index')
            ->with('success', "Loan Scheme '{$scheme->name}' ({$scheme->code}) created successfully.");
    }

    public function show(LoanScheme $loanScheme): View
    {
        return view('admin.loan-schemes.show', ['scheme' => $loanScheme]);
    }

    public function edit(LoanScheme $loanScheme): View
    {
        $companies = Company::where('is_active', true)->get();
        $branches = Branch::where('is_active', true)->get();

        return view('admin.loan-schemes.edit', ['scheme' => $loanScheme, 'companies' => $companies, 'branches' => $branches]);
    }

    public function update(UpdateLoanSchemeRequest $request, LoanScheme $loanScheme): RedirectResponse
    {
        $updatedScheme = $this->schemeService->updateScheme($loanScheme, $request->validated());

        return redirect()->route('admin.loan-scheme.index')
            ->with('success', "Loan Scheme '{$updatedScheme->name}' updated successfully.");
    }

    public function destroy(LoanScheme $loanScheme): RedirectResponse
    {
        $this->schemeService->deleteScheme($loanScheme);

        return redirect()->route('admin.loan-scheme.index')
            ->with('success', "Loan Scheme '{$loanScheme->name}' deleted successfully.");
    }
}
