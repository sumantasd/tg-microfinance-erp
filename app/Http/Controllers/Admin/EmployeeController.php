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
        if (auth()->user()?->hasRole('Branch Manager')) {
            abort(403, 'Branch Managers are not authorized to access the employee directory.');
        }

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

        $user = auth()->user();
        $companyId = $user?->isSuperAdmin() ? null : $user?->company_id;

        $companies = Company::where('is_active', true)->get();

        $branchesQuery = Branch::where('is_active', true);
        if ($companyId) {
            $branchesQuery->where('company_id', $companyId);
        }
        $branches = $branchesQuery->orderBy('name')->get();

        $departmentsQuery = Department::where('is_active', true);
        if ($companyId) {
            $departmentsQuery->where('company_id', $companyId);
        }
        $departments = $departmentsQuery->orderBy('name')->get();

        $designationsQuery = Designation::where('is_active', true);
        if ($companyId) {
            $designationsQuery->where('company_id', $companyId);
        }
        $designations = $designationsQuery->orderBy('title')->get();

        $managersQuery = Employee::where('status', 'active');
        if ($companyId) {
            $managersQuery->where('company_id', $companyId);
        }
        $managers = $managersQuery->orderBy('first_name')->get();

        $usersQuery = User::whereDoesntHave('employee')->where('status', 'active');
        if ($companyId) {
            $usersQuery->where('company_id', $companyId);
        }
        $users = $usersQuery->orderBy('name')->get();

        $rolesQuery = \Spatie\Permission\Models\Role::query();
        if (!$user?->isSuperAdmin()) {
            $rolesQuery->where('name', '!=', 'Super Admin');
        }
        $roles = $rolesQuery->orderBy('name')->get();

        $selectedCompanyId = old('company_id', $companyId ?: ($companies->first()?->id ?? 1));
        $previewCode = $this->employeeService->generateEmployeeCode((int) $selectedCompanyId);

        $companyCodePreviews = [];
        foreach ($companies as $comp) {
            $companyCodePreviews[$comp->id] = $this->employeeService->generateEmployeeCode((int) $comp->id);
        }

        return view('admin.employees.create', compact('companies', 'branches', 'departments', 'designations', 'managers', 'users', 'roles', 'previewCode', 'companyCodePreviews'));
    }

    public function previewCode(Request $request)
    {
        $this->authorize('create', Employee::class);

        $user = auth()->user();
        $companyId = $request->get('company_id');

        if (!$user?->isSuperAdmin() || empty($companyId)) {
            $companyId = $user?->company_id ?? 1;
        }

        $code = $this->employeeService->generateEmployeeCode((int) $companyId);

        return response()->json([
            'success' => true,
            'company_id' => (int) $companyId,
            'code' => $code,
        ]);
    }

    public function store(StoreEmployeeRequest $request)
    {
        $this->authorize('create', Employee::class);

        $photo = $request->file('profile_photo');
        $documents = $request->documents ?? [];

        $employee = $this->employeeService->createEmployee($request->validated(), $photo, $documents);

        return redirect()->route('admin.employee.index')
            ->with('success', "Enterprise Employee [{$employee->full_name}] created successfully with Employee Code: {$employee->employee_code}.")
            ->with('assigned_employee_code', $employee->employee_code)
            ->with('created_employee_name', $employee->full_name);
    }

    public function show(int $id)
    {
        $employee = Employee::withTrashed()->find($id);
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

        $user = auth()->user();
        $companyId = $user?->isSuperAdmin() ? $employee->company_id : $user?->company_id;

        $companies = Company::where('is_active', true)->get();

        $branchesQuery = Branch::where('is_active', true);
        if ($companyId) {
            $branchesQuery->where('company_id', $companyId);
        }
        $branches = $branchesQuery->orderBy('name')->get();

        $departmentsQuery = Department::where('is_active', true);
        if ($companyId) {
            $departmentsQuery->where('company_id', $companyId);
        }
        $departments = $departmentsQuery->orderBy('name')->get();

        $designationsQuery = Designation::where('is_active', true);
        if ($companyId) {
            $designationsQuery->where('company_id', $companyId);
        }
        $designations = $designationsQuery->orderBy('title')->get();

        // Properly define $managers scoped to company and excluding current employee
        $managersQuery = Employee::where('status', 'active')->where('id', '!=', $employee->id);
        if ($companyId) {
            $managersQuery->where('company_id', $companyId);
        }
        $managers = $managersQuery->orderBy('first_name')->get();

        $usersQuery = User::where('status', 'active')->where(function ($q) use ($employee) {
            $q->whereDoesntHave('employee');
            if ($employee->user_id) {
                $q->orWhere('id', $employee->user_id);
            }
        });
        if ($companyId) {
            $usersQuery->where('company_id', $companyId);
        }
        $users = $usersQuery->orderBy('name')->get();

        $rolesQuery = \Spatie\Permission\Models\Role::query();
        if (!$user?->isSuperAdmin()) {
            $rolesQuery->where('name', '!=', 'Super Admin');
        }
        $roles = $rolesQuery->orderBy('name')->get();

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
