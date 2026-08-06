<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAttendanceRequest;
use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Employee;
use App\Services\AttendanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function __construct(
        protected AttendanceService $attendanceService
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Attendance::class);

        $filters = $request->only(['company_id', 'branch_id', 'date', 'status', 'search']);
        $attendances = $this->attendanceService->getPaginatedAttendances($filters, 15);

        $companies = auth()->user()->isSuperAdmin() ? Company::where('is_active', true)->get() : collect();
        $branches = Branch::where('is_active', true)->get();
        $employees = Employee::where('status', 'active')->get();

        return view('admin.hrm.attendance.index', compact('attendances', 'filters', 'companies', 'branches', 'employees'));
    }

    public function store(StoreAttendanceRequest $request): RedirectResponse
    {
        $this->authorize('create', Attendance::class);

        $this->attendanceService->markDailyAttendance($request->validated());

        return redirect()->route('admin.hrm.attendance.index')->with('success', 'Staff attendance marked successfully!');
    }
}
