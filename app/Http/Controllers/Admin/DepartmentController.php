<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreDepartmentRequest;
use App\Http\Requests\Admin\UpdateDepartmentRequest;
use App\Models\Company;
use App\Models\Department;
use App\Repositories\DepartmentRepositoryInterface;
use App\Services\DepartmentService;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function __construct(
        protected DepartmentRepositoryInterface $departmentRepository,
        protected DepartmentService $departmentService
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Department::class);

        $filters = $request->only(['search', 'status', 'company_id']);
        $departments = $this->departmentRepository->getPaginatedDepartments($filters, 10);
        $companies = Company::where('is_active', true)->get();

        return view('admin.departments.index', compact('departments', 'companies', 'filters'));
    }

    public function create()
    {
        $this->authorize('create', Department::class);

        $companies = Company::where('is_active', true)->get();

        return view('admin.departments.create', compact('companies'));
    }

    public function store(StoreDepartmentRequest $request)
    {
        $this->authorize('create', Department::class);

        $this->departmentService->createDepartment($request->validated());

        return redirect()->route('admin.department.index')->with('success', 'Department created successfully.');
    }

    public function show(int $id)
    {
        $department = $this->departmentRepository->findWithTrashed($id);
        if (!$department) {
            abort(404);
        }

        $this->authorize('view', $department);

        return view('admin.departments.show', compact('department'));
    }

    public function edit(Department $department)
    {
        $this->authorize('update', $department);

        $companies = Company::where('is_active', true)->get();

        return view('admin.departments.edit', compact('department', 'companies'));
    }

    public function update(UpdateDepartmentRequest $request, Department $department)
    {
        $this->authorize('update', $department);

        $this->departmentService->updateDepartment($department, $request->validated());

        return redirect()->route('admin.department.index')->with('success', 'Department details updated successfully.');
    }

    public function toggleStatus(Request $request, Department $department)
    {
        $this->authorize('toggleStatus', $department);

        $request->validate(['is_active' => 'required|boolean']);
        $this->departmentService->toggleDepartmentStatus($department, (bool) $request->is_active);

        $statusName = $request->is_active ? 'Activated' : 'Deactivated';
        return back()->with('success', "Department successfully {$statusName}.");
    }

    public function destroy(Department $department)
    {
        $this->authorize('delete', $department);

        $this->departmentService->deleteDepartment($department);

        return redirect()->route('admin.department.index')->with('success', 'Department soft deleted successfully.');
    }

    public function restore(int $id)
    {
        $department = $this->departmentRepository->findWithTrashed($id);
        if (!$department) {
            abort(404);
        }

        $this->authorize('restore', $department);

        $this->departmentService->restoreDepartment($department);

        return redirect()->route('admin.department.index')->with('success', 'Department record restored successfully.');
    }
}
