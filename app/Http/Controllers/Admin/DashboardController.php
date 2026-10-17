<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AttendanceService;
use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService,
        protected AttendanceService $attendanceService
    ) {}

    /**
     * Display the Executive Admin ERP Dashboard.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        
        $isBranchManager = $user ? $user->isBranchManager() : true;
        $isHeadOfficeAdmin = $user && ($user->isSuperAdmin() || $user->isCompanyAdmin()) && !$isBranchManager;

        $requestedCompany = $request->filled('company_id') ? (int) $request->input('company_id') : null;
        $requestedBranch = $request->filled('branch_id') ? (int) $request->input('branch_id') : null;

        $companyId = $user ? $user->resolveScopedCompanyId($requestedCompany) : 1;

        if ($isHeadOfficeAdmin) {
            $branchId = $requestedBranch ? (int) $requestedBranch : null;
        } elseif ($isBranchManager) {
            // Strictly enforce assigned branch. If unassigned, lock to -1 (zero records)
            $branchId = $user->branch_id ? (int) $user->branch_id : -1;
        } else {
            $branchId = $user ? ($user->branch_id ? (int) $user->branch_id : -1) : -1;
        }

        $canViewProfitLoss = $user && !$isBranchManager && $user->can('profit_loss.view');
        $canViewBankBalance = $user && ($user->can('bank_accounts.view') || $user->can('accounting.view') || $user->can('bank_deposit.view'));

        $dateFilter = $request->input('date_filter', 'today');
        $fromDate = $request->input('from_date', $request->input('start_date'));
        $toDate = $request->input('to_date', $request->input('end_date'));

        $data = $this->dashboardService->getDashboardData(
            $companyId,
            $branchId,
            $canViewProfitLoss,
            $canViewBankBalance,
            $isBranchManager,
            null,
            $dateFilter,
            $fromDate,
            $toDate
        );

        $data['dutyInfo'] = $user ? $this->attendanceService->getDutyStatusForUser($user) : null;
        $data['isHeadOfficeAdmin'] = $isHeadOfficeAdmin;
        $data['isBranchManager'] = $isBranchManager;

        return view('admin.dashboard', $data);
    }
}
