<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\Payroll;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HrReportController extends Controller
{
    public function index(Request $request): View
    {
        $user = auth()->user();

        // Branch-level isolation
        $empQuery = Employee::query();
        $attQuery = Attendance::query();
        $leaveQuery = Leave::query();
        $payrollQuery = Payroll::query();

        if (!$user->isSuperAdmin()) {
            if ($user->hasRole('Branch Manager')) {
                $empQuery->where('branch_id', $user->branch_id);
                $attQuery->where('branch_id', $user->branch_id);
                $leaveQuery->where('branch_id', $user->branch_id);
                $payrollQuery->where('branch_id', $user->branch_id);
            } elseif ($user->hasRole('Company Admin') || $user->company_id) {
                $empQuery->where('company_id', $user->company_id);
                $attQuery->where('company_id', $user->company_id);
                $leaveQuery->where('company_id', $user->company_id);
                $payrollQuery->where('company_id', $user->company_id);
            }
        }

        $stats = [
            'total_employees' => $empQuery->count(),
            'active_employees' => (clone $empQuery)->where('status', 'active')->count(),
            'total_present_today' => (clone $attQuery)->whereDate('attendance_date', now())->where('status', 'present')->count(),
            'pending_leaves' => (clone $leaveQuery)->where('status', 'pending')->count(),
            'total_payroll_disbursed' => (clone $payrollQuery)->where('status', 'disbursed')->sum('total_net_payout'),
        ];

        $companies = $user->isSuperAdmin() ? Company::where('is_active', true)->get() : collect();
        $branches = Branch::where('is_active', true)->get();

        return view('admin.hrm.reports.index', compact('stats', 'companies', 'branches'));
    }
}
