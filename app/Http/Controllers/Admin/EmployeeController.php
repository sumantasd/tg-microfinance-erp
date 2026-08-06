<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreEmployeeRequest;
use App\Http\Requests\Admin\UpdateEmployeeRequest;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\User;
use App\Repositories\EmployeeRepositoryInterface;
use App\Services\EmployeeService;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function __construct(
        protected EmployeeRepositoryInterface $employeeRepository,
        protected EmployeeService $employeeService
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Employee::class);

        $filters = $request->only(['search', 'status', 'company_id', 'branch_id', 'department_id', 'designation_id']);
        $employees = $this->employeeService->getPaginatedEmployees($filters, 10);
        
        $companies = Company::where('is_active', true)->get();
        $branches = Branch::where('is_active', true)->get();
        $departments = Department::where('is_active', true)->get();
        $designations = Designation::where('is_active', true)->get();

        return view('admin.employees.index', compact('employees', 'companies', 'branches', 'departments', 'designations', 'filters'));
    }

    public function create()
    {
        $this->authorize('create', Employee::class);

        $companies = Company::where('is_active', true)->get();
        $branches = Branch::where('is_active', true)->get();
        $departments = Department::where('is_active', true)->get();
        $designations = Designation::where('is_active', true)->get();
        $managers = Employee::where('status', 'active')->get();
        $users = User::where('status', 'active')->get();
        $roles = \Spatie\Permission\Models\Role::all();

        return view('admin.employees.create', compact('companies', 'branches', 'departments', 'designations', 'managers', 'users', 'roles'));
    }

    public function store(StoreEmployeeRequest $request)
    {
        $this->authorize('create', Employee::class);

        $photo = $request->file('profile_photo');
        $documents = $request->documents ?? [];

        $this->employeeService->createEmployee($request->validated(), $photo, $documents);

        return redirect()->route('admin.employee.index')->with('success', 'Enterprise Employee Profile created successfully.');
    }

    public function show(int $id)
    {
        $employee = $this->employeeRepository->findWithTrashed($id);
        if (!$employee) {
            abort(404);
        }

        $this->authorize('view', $employee);
        $employee->load(['documents', 'reportingManager', 'subordinates']);

        return view('admin.employees.show', compact('employee'));
    }

    public function edit(Employee $employee)
    {
        $this->authorize('update', $employee);
        $employee->load('documents');

        $companies = Company::where('is_active', true)->get();
        $branches = Branch::where('is_active', true)->get();
        $departments = Department::where('is_active', true)->get();
        $designations = Designation::where('is_active', true)->get();
        $managers = Employee::where('status', 'active')->where('id', '!=', $employee->id)->get();
        $users = User::where('status', 'active')->get();
        $roles = \Spatie\Permission\Models\Role::all();

        return view('admin.employees.edit', compact('employee', 'companies', 'branches', 'departments', 'designations', 'managers', 'users', 'roles'));
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee)
    {
        $this->authorize('update', $employee);

        $photo = $request->file('profile_photo');
        $documents = $request->documents ?? [];

        $this->employeeService->updateEmployee($employee, $request->validated(), $photo, $documents);

        return redirect()->route('admin.employee.index')->with('success', 'Enterprise Employee Profile updated successfully.');
    }

    public function destroyDocument(EmployeeDocument $document)
    {
        $employee = $document->employee;
        $this->authorize('update', $employee);

        $this->employeeService->deleteDocument($document);

        return back()->with('success', 'Employee document removed successfully.');
    }

    public function toggleStatus(Request $request, Employee $employee)
    {
        $this->authorize('toggleStatus', $employee);

        $request->validate(['status' => 'required|in:active,resigned,terminated,on_leave,suspended']);
        $this->employeeService->toggleEmployeeStatus($employee, $request->status);

        return back()->with('success', "Employee status updated to " . strtoupper($request->status));
    }

    public function destroy(Employee $employee)
    {
        $this->authorize('delete', $employee);

        $this->employeeService->deleteEmployee($employee);

        return redirect()->route('admin.employee.index')->with('success', 'Employee profile soft deleted successfully.');
    }

    public function restore(int $id)
    {
        $employee = $this->employeeRepository->findWithTrashed($id);
        if (!$employee) {
            abort(404);
        }

        $this->authorize('restore', $employee);

        $this->employeeService->restoreEmployee($employee);

        return redirect()->route('admin.employee.index')->with('success', 'Employee record restored successfully.');
    }
}
