<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AddGroupMemberRequest;
use App\Http\Requests\Admin\StoreCustomerGroupRequest;
use App\Http\Requests\Admin\UpdateCustomerGroupRequest;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Services\CustomerGroupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerGroupController extends Controller
{
    public function __construct(protected CustomerGroupService $groupService) {}

    public function index(Request $request): View
    {
        $this->authorize('group.view');

        $filters = $request->only(['search', 'company_id', 'branch_id', 'status']);
        $groups = $this->groupService->getPaginatedGroups($filters, 15);
        $branches = Branch::where('is_active', true)->get();
        $companies = Company::where('is_active', true)->get();

        return view('admin.customer-groups.index', compact('groups', 'branches', 'companies', 'filters'));
    }

    public function create(): View
    {
        $this->authorize('group.create');

        $branches = Branch::where('is_active', true)->get();
        $companies = Company::where('is_active', true)->get();

        return view('admin.customer-groups.create', compact('branches', 'companies'));
    }

    public function store(StoreCustomerGroupRequest $request): RedirectResponse
    {
        $group = $this->groupService->createGroup($request->validated());

        return redirect()->route('admin.customer-group.show', $group->id)
            ->with('success', "Customer Group '{$group->name}' ({$group->group_code}) created successfully.");
    }

    public function show(CustomerGroup $group): View
    {
        $this->authorize('group.view');

        $group = $this->groupService->getGroupById($group->id);

        // Fetch eligible customers from the same branch who are NOT already active members of this group
        $existingMemberIds = $group->activeMembers->pluck('customer_id')->toArray();
        $availableCustomers = Customer::where('company_id', $group->company_id)
            ->where('branch_id', $group->branch_id)
            ->where('status', 'active')
            ->whereNotIn('id', $existingMemberIds)
            ->orderBy('first_name', 'asc')
            ->get();

        return view('admin.customer-groups.show', compact('group', 'availableCustomers'));
    }

    public function edit(CustomerGroup $group): View
    {
        $this->authorize('group.edit');

        $branches = Branch::where('is_active', true)->get();
        $companies = Company::where('is_active', true)->get();

        return view('admin.customer-groups.edit', compact('group', 'branches', 'companies'));
    }

    public function update(UpdateCustomerGroupRequest $request, CustomerGroup $group): RedirectResponse
    {
        $updatedGroup = $this->groupService->updateGroup($group, $request->validated());

        return redirect()->route('admin.customer-group.show', $updatedGroup->id)
            ->with('success', "Group '{$updatedGroup->name}' updated successfully.");
    }

    public function destroy(CustomerGroup $group): RedirectResponse
    {
        $this->authorize('group.delete');

        $name = $group->name;
        $this->groupService->deleteGroup($group);

        return redirect()->route('admin.customer-group.index')
            ->with('success', "Group '{$name}' deleted successfully.");
    }

    public function addMember(AddGroupMemberRequest $request, CustomerGroup $group): RedirectResponse
    {
        $this->groupService->addMemberToGroup(
            $group,
            $request->validated('customer_id'),
            $request->validated('role')
        );

        return redirect()->route('admin.customer-group.show', $group->id)
            ->with('success', 'Customer successfully added to group.');
    }

    public function removeMember(Request $request, CustomerGroup $group, Customer $customer): RedirectResponse
    {
        $this->authorize('group.manage_members');

        $this->groupService->removeMemberFromGroup($group, $customer->id);

        return redirect()->route('admin.customer-group.show', $group->id)
            ->with('success', "Customer '{$customer->full_name}' removed from group.");
    }

    public function assignLeader(Request $request, CustomerGroup $group): RedirectResponse
    {
        $this->authorize('group.edit');

        $request->validate([
            'leader_customer_id' => 'required|exists:customers,id',
        ]);

        $this->groupService->assignGroupLeader($group, (int) $request->input('leader_customer_id'));

        return redirect()->route('admin.customer-group.show', $group->id)
            ->with('success', 'Group Leader updated successfully.');
    }

    public function toggleStatus(Request $request, CustomerGroup $group): RedirectResponse
    {
        $this->authorize('group.change_status');

        $request->validate([
            'status' => 'required|in:active,inactive,closed',
        ]);

        $this->groupService->updateGroup($group, ['status' => $request->input('status')]);

        return redirect()->back()
            ->with('success', "Group status updated to '{$request->input('status')}'.");
    }
}
