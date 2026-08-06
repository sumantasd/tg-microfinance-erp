<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreDesignationRequest;
use App\Http\Requests\Admin\UpdateDesignationRequest;
use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
use App\Repositories\DesignationRepositoryInterface;
use App\Services\DesignationService;
use Illuminate\Http\Request;

class DesignationController extends Controller
{
    public function __construct(
        protected DesignationRepositoryInterface $designationRepository,
        protected DesignationService $designationService
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Designation::class);

        $filters = $request->only(['search', 'status', 'company_id', 'department_id']);
        $designations = $this->designationRepository->getPaginatedDesignations($filters, 10);
        $companies = Company::where('is_active', true)->get();
        $departments = Department::where('is_active', true)->get();

        return view('admin.designations.index', compact('designations', 'companies', 'departments', 'filters'));
    }

    public function create()
    {
        $this->authorize('create', Designation::class);

        $companies = Company::where('is_active', true)->get();
        $departments = Department::where('is_active', true)->get();

        return view('admin.designations.create', compact('companies', 'departments'));
    }

    public function store(StoreDesignationRequest $request)
    {
        $this->authorize('create', Designation::class);

        $this->designationService->createDesignation($request->validated());

        return redirect()->route('admin.designation.index')->with('success', 'Designation created successfully.');
    }

    public function show(int $id)
    {
        $designation = $this->designationRepository->findWithTrashed($id);
        if (!$designation) {
            abort(404);
        }

        $this->authorize('view', $designation);

        return view('admin.designations.show', compact('designation'));
    }

    public function edit(Designation $designation)
    {
        $this->authorize('update', $designation);

        $companies = Company::where('is_active', true)->get();
        $departments = Department::where('is_active', true)->get();

        return view('admin.designations.edit', compact('designation', 'companies', 'departments'));
    }

    public function update(UpdateDesignationRequest $request, Designation $designation)
    {
        $this->authorize('update', $designation);

        $this->designationService->updateDesignation($designation, $request->validated());

        return redirect()->route('admin.designation.index')->with('success', 'Designation details updated successfully.');
    }

    public function toggleStatus(Request $request, Designation $designation)
    {
        $this->authorize('toggleStatus', $designation);

        $request->validate(['is_active' => 'required|boolean']);
        $this->designationService->toggleDesignationStatus($designation, (bool) $request->is_active);

        $statusName = $request->is_active ? 'Activated' : 'Deactivated';
        return back()->with('success', "Designation successfully {$statusName}.");
    }

    public function destroy(Designation $designation)
    {
        $this->authorize('delete', $designation);

        $this->designationService->deleteDesignation($designation);

        return redirect()->route('admin.designation.index')->with('success', 'Designation soft deleted successfully.');
    }

    public function restore(int $id)
    {
        $designation = $this->designationRepository->findWithTrashed($id);
        if (!$designation) {
            abort(404);
        }

        $this->authorize('restore', $designation);

        $this->designationService->restoreDesignation($designation);

        return redirect()->route('admin.designation.index')->with('success', 'Designation record restored successfully.');
    }
}
