<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService
    ) {}

    /**
     * Display the Executive Admin ERP Dashboard.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        
        $requestedCompany = $request->filled('company_id') ? (int) $request->input('company_id') : null;
        $requestedBranch = $request->filled('branch_id') ? (int) $request->input('branch_id') : null;

        $companyId = $user ? $user->resolveScopedCompanyId($requestedCompany) : 1;
        $branchId = $user ? $user->resolveScopedBranchId($requestedBranch) : null;

        $data = $this->dashboardService->getDashboardData($companyId, $branchId);

        return view('admin.dashboard', $data);
    }
}
