<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBranchRequest;
use App\Http\Requests\Admin\UpdateBranchRequest;
use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use App\Repositories\BranchRepositoryInterface;
use App\Services\BranchService;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function __construct(
        protected BranchRepositoryInterface $branchRepository,
        protected BranchService $branchService
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Branch::class);

        $filters = $request->only(['search', 'status', 'company_id']);
        $branches = $this->branchRepository->getPaginatedBranches($filters, 10);
        $companies = Company::where('is_active', true)->get();

        return view('admin.branches.index', compact('branches', 'companies', 'filters'));
    }

    public function create()
    {
        $this->authorize('create', Branch::class);

        $companies = Company::where('is_active', true)->get();
        $managers = User::where('status', 'active')->get();

        return view('admin.branches.create', compact('companies', 'managers'));
    }

    public function store(StoreBranchRequest $request)
    {
        $this->authorize('create', Branch::class);

        $this->branchService->createBranch($request->validated());

        return redirect()->route('admin.branch.index')->with('success', 'Branch office created successfully.');
    }

    public function show(int $id)
    {
        $branch = $this->branchRepository->findWithTrashed($id);
        if (!$branch) {
            abort(404);
        }

        $this->authorize('view', $branch);

        return view('admin.branches.show', compact('branch'));
    }

    public function edit(Branch $branch)
    {
        $this->authorize('update', $branch);

        $companies = Company::where('is_active', true)->get();
        $managers = User::where('status', 'active')->get();

        return view('admin.branches.edit', compact('branch', 'companies', 'managers'));
    }

    public function update(UpdateBranchRequest $request, Branch $branch)
    {
        $this->authorize('update', $branch);

        $this->branchService->updateBranch($branch, $request->validated());

        return redirect()->route('admin.branch.index')->with('success', 'Branch office details updated successfully.');
    }

    public function toggleStatus(Request $request, Branch $branch)
    {
        $this->authorize('toggleStatus', $branch);

        $request->validate(['is_active' => 'required|boolean']);
        $this->branchService->toggleBranchStatus($branch, (bool) $request->is_active);

        $statusName = $request->is_active ? 'Activated' : 'Deactivated';
        return back()->with('success', "Branch office successfully {$statusName}.");
    }

    public function destroy(Branch $branch)
    {
        $this->authorize('delete', $branch);

        $this->branchService->deleteBranch($branch);

        return redirect()->route('admin.branch.index')->with('success', 'Branch office record soft deleted successfully.');
    }

    public function restore(int $id)
    {
        $branch = $this->branchRepository->findWithTrashed($id);
        if (!$branch) {
            abort(404);
        }

        $this->authorize('restore', $branch);

        $this->branchService->restoreBranch($branch);

        return redirect()->route('admin.branch.index')->with('success', 'Branch office record restored successfully.');
    }
}
