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

        $employees = $this->employeeService->getPaginatedEmployees($request->only(['search']), 20);

        return view('admin.hrm.letters.index', compact('employees'));
    }

    public function generate(Request $request, int $employeeId): View
    {
        $employee = $this->employeeService->getEmployeeById($employeeId);
        if (!$employee) {
            abort(404);
        }

        $this->authorize('view', $employee);

        $type = $request->query('type', 'appointment_letter');
        $letterData = $this->letterGenerator->generateLetter($employee, $type);

        return view('admin.hrm.letters.print', compact('letterData'));
    }

    public function idCard(int $employeeId): View
    {
        $employee = $this->employeeService->getEmployeeById($employeeId);
        if (!$employee) {
            abort(404);
        }

        $this->authorize('view', $employee);

        return view('admin.hrm.letters.id-card', compact('employee'));
    }
}
