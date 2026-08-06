<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreLeaveRequest;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\LeaveType;
use App\Services\LeaveService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeaveController extends Controller
{
    public function __construct(
        protected LeaveService $leaveService
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Leave::class);

        $filters = $request->only(['company_id', 'branch_id', 'status', 'leave_type_id', 'search']);
        $leaves = $this->leaveService->getPaginatedLeaves($filters, 15);

        $companies = auth()->user()->isSuperAdmin() ? Company::where('is_active', true)->get() : collect();
        $branches = Branch::where('is_active', true)->get();
        $leaveTypes = LeaveType::where('is_active', true)->get();
        $employees = Employee::where('status', 'active')->get();

        return view('admin.hrm.leaves.index', compact('leaves', 'filters', 'companies', 'branches', 'leaveTypes', 'employees'));
    }

    public function store(StoreLeaveRequest $request): RedirectResponse
    {
        $this->authorize('create', Leave::class);

        $this->leaveService->applyLeave($request->validated());

        return redirect()->route('admin.hrm.leave.index')->with('success', 'Leave application submitted successfully!');
    }

    public function approve(Leave $leave): RedirectResponse
    {
        $this->authorize('approve', $leave);

        $this->leaveService->approveLeave($leave);

        return redirect()->back()->with('success', 'Leave application approved!');
    }

    public function reject(Request $request, Leave $leave): RedirectResponse
    {
        $this->authorize('approve', $leave);

        $request->validate(['rejection_reason' => 'required|string|max:500']);

        $this->leaveService->rejectLeave($leave, $request->input('rejection_reason'));

        return redirect()->back()->with('success', 'Leave application rejected.');
    }
}
