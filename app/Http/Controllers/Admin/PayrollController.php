<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProcessPayrollRequest;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Payroll;
use App\Services\PayrollService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PayrollController extends Controller
{
    public function __construct(
        protected PayrollService $payrollService
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Payroll::class);

        $filters = $request->only(['company_id', 'branch_id', 'month', 'year', 'status']);
        $payrolls = $this->payrollService->getPaginatedPayrolls($filters, 15);

        $companies = auth()->user()->isSuperAdmin() ? Company::where('is_active', true)->get() : collect();
        $branches = Branch::where('is_active', true)->get();

        return view('admin.hrm.payroll.index', compact('payrolls', 'filters', 'companies', 'branches'));
    }

    public function store(ProcessPayrollRequest $request): RedirectResponse
    {
        $this->authorize('process', Payroll::class);

        $payroll = $this->payrollService->runMonthlyPayroll($request->validated());

        return redirect()->route('admin.hrm.payroll.show', $payroll->id)->with('success', 'Monthly payroll processed as draft!');
    }

    public function show(int $id): View
    {
        $payroll = $this->payrollService->getPayrollById($id);
        if (!$payroll) {
            abort(404);
        }

        $this->authorize('view', $payroll);

        return view('admin.hrm.payroll.show', compact('payroll'));
    }

    public function disburse(int $id): RedirectResponse
    {
        $payroll = $this->payrollService->getPayrollById($id);
        if (!$payroll) {
            abort(404);
        }

        $this->authorize('disburse', $payroll);

        $this->payrollService->disbursePayroll($payroll);

        return redirect()->back()->with('success', 'Payroll disbursed successfully! All salary slips marked as paid.');
    }

    public function salarySlip(string $uuid): View
    {
        $slip = $this->payrollService->getSalarySlipByUuid($uuid);
        if (!$slip) {
            abort(404);
        }

        return view('admin.hrm.payroll.salary-slip', compact('slip'));
    }
}
