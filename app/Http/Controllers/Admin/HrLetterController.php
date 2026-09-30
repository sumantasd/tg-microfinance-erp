<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Services\EmployeeService;
use App\Services\HrLetterService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HrLetterController extends Controller
{
    public function __construct(
        protected EmployeeService $employeeService,
        protected HrLetterService $letterGenerator
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Employee::class);

        $user = auth()->user();
        $filters = $request->only(['search']);

        if ($user->hasRole('Branch Manager')) {
            $userEmpId = $user->employee?->id ?? Employee::where('user_id', $user->id)->orWhere('id', $user->employee_id)->value('id');
            $filters['employee_id'] = $userEmpId ?: 0;
        }

        $employees = $this->employeeService->getPaginatedEmployees($filters, 20);

        return view('admin.hrm.letters.index', compact('employees'));
    }

    public function generate(Request $request, int $employeeId): View
    {
        $employee = Employee::find($employeeId);
        if (!$employee) {
            abort(404);
        }

        $user = auth()->user();
        if ($user->hasRole('Branch Manager')) {
            $userEmpId = $user->employee?->id ?? Employee::where('user_id', $user->id)->orWhere('id', $user->employee_id)->value('id');
            if ((int)$employee->id !== (int)$userEmpId && (int)$employee->user_id !== (int)$user->id) {
                abort(403, 'Unauthorized access to another employee HR letter.');
            }
        }

        $this->authorize('view', $employee);

        $type = $request->query('type', 'appointment_letter');
        $letterData = $this->letterGenerator->generateLetter($employee, $type);

        return view('admin.hrm.letters.print', compact('letterData'));
    }

    public function idCard(int $employeeId): View
    {
        $employee = Employee::find($employeeId);
        if (!$employee) {
            abort(404);
        }

        $user = auth()->user();
        if ($user->hasRole('Branch Manager')) {
            $userEmpId = $user->employee?->id ?? Employee::where('user_id', $user->id)->orWhere('id', $user->employee_id)->value('id');
            if ((int)$employee->id !== (int)$userEmpId && (int)$employee->user_id !== (int)$user->id) {
                abort(403, 'Unauthorized access to another employee ID card.');
            }
        }

        $this->authorize('view', $employee);

        return view('admin.hrm.letters.id-card', compact('employee'));
    }
}
