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
        if ($request->user()->hasRole('Branch Manager')) {
            abort(403, 'Branch Managers are not authorized to process payroll.');
        }

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

        $user = auth()->user();
        if ($user && $user->hasRole('Branch Manager')) {
            $userEmpId = $user->employee?->id ?? \App\Models\Employee::where('user_id', $user->id)->orWhere('id', $user->employee_id)->value('id');
            $payroll->setRelation('salarySlips', $payroll->salarySlips->filter(fn ($s) => (int)$s->employee_id === (int)$userEmpId));
        }

        return view('admin.hrm.payroll.show', compact('payroll'));
    }

    public function disburse(int $id): RedirectResponse
    {
        if (auth()->user()->hasRole('Branch Manager')) {
            abort(403, 'Branch Managers are not authorized to disburse payroll.');
        }

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

        $user = auth()->user();
        if ($user && $user->hasRole('Branch Manager')) {
            $userEmpId = $user->employee?->id ?? \App\Models\Employee::where('user_id', $user->id)->orWhere('id', $user->employee_id)->value('id');
            if (!$userEmpId || (int)$slip->employee_id !== (int)$userEmpId) {
                abort(403, 'Unauthorized access to another employee salary slip.');
            }
        }

        return view('admin.hrm.payroll.salary-slip', compact('slip'));
    }
}
