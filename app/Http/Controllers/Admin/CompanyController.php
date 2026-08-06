<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCompanyRequest;
use App\Http\Requests\Admin\UpdateCompanyRequest;
use App\Models\Company;
use App\Repositories\CompanyRepositoryInterface;
use App\Services\CompanyService;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function __construct(
        protected CompanyRepositoryInterface $companyRepository,
        protected CompanyService $companyService
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Company::class);

        $filters = $request->only(['search', 'status']);
        $companies = $this->companyRepository->getPaginatedCompanies($filters, 10);

        return view('admin.companies.index', compact('companies', 'filters'));
    }

    public function create()
    {
        $this->authorize('create', Company::class);

        return view('admin.companies.create');
    }

    public function store(StoreCompanyRequest $request)
    {
        $this->authorize('create', Company::class);

        $this->companyService->createCompany($request->validated());

        return redirect()->route('admin.company.index')->with('success', 'Company profile created successfully.');
    }

    public function show(int $id)
    {
        $company = $this->companyRepository->findWithTrashed($id);
        if (!$company) {
            abort(404);
        }

        $this->authorize('view', $company);

        return view('admin.companies.show', compact('company'));
    }

    public function edit(Company $company)
    {
        $this->authorize('update', $company);

        return view('admin.companies.edit', compact('company'));
    }

    public function update(UpdateCompanyRequest $request, Company $company)
    {
        $this->authorize('update', $company);

        $this->companyService->updateCompany($company, $request->validated());

        return redirect()->route('admin.company.index')->with('success', 'Company profile updated successfully.');
    }

    public function toggleStatus(Request $request, Company $company)
    {
        $this->authorize('update', $company);

        $request->validate(['is_active' => 'required|boolean']);
        $this->companyService->toggleCompanyStatus($company, (bool) $request->is_active);

        $statusName = $request->is_active ? 'Activated' : 'Deactivated';
        return back()->with('success', "Company successfully {$statusName}.");
    }

    public function destroy(Company $company)
    {
        $this->authorize('delete', $company);

        $this->companyService->deleteCompany($company);

        return redirect()->route('admin.company.index')->with('success', 'Company record soft deleted successfully.');
    }

    public function restore(int $id)
    {
        $company = $this->companyRepository->findWithTrashed($id);
        if (!$company) {
            abort(404);
        }

        $this->authorize('restore', $company);

        $this->companyService->restoreCompany($company);

        return redirect()->route('admin.company.index')->with('success', 'Company record restored successfully.');
    }
}
