<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CustomerGroup;
use App\Services\CustomerGroupService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GroupApiController extends Controller
{
    use ApiResponse;

    protected CustomerGroupService $groupService;

    public function __construct(CustomerGroupService $groupService)
    {
        $this->groupService = $groupService;
    }

    /**
     * Customer Group listing with branch scoping.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $scopedBranchId = $user->resolveScopedBranchId($request->query('branch_id'));

        $query = CustomerGroup::with(['branch', 'leader', 'members.customer']);

        if ($scopedBranchId) {
            $query->where('branch_id', $scopedBranchId);
        }

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('group_code', 'like', "%{$search}%");
            });
        }

        $groups = $query->latest()->paginate((int) ($request->query('per_page', 15)));

        return $this->successResponse($groups, 'Customer groups list retrieved');
    }

    /**
     * Show single group details.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $group = CustomerGroup::with(['branch', 'company', 'leader', 'members.customer'])->find($id);

        if (!$group) {
            return $this->notFoundResponse('Customer group not found');
        }

        if (!$user->canAccessCompany($group->company_id) || !$user->canAccessBranch($group->branch_id)) {
            return $this->forbiddenResponse('Unauthorized access to another branch group');
        }

        return $this->successResponse($group, 'Customer group profile retrieved');
    }

    /**
     * Store new customer group.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'group_code' => 'nullable|string|max:50',
            'branch_id' => 'required|exists:branches,id',
            'center_name' => 'nullable|string|max:255',
            'meeting_frequency' => 'nullable|in:weekly,biweekly,monthly',
            'meeting_day' => 'nullable|string|max:20',
            'meeting_time' => 'nullable',
            'formation_date' => 'nullable|date',
        ]);

        if (!$user->canAccessBranch($validated['branch_id'])) {
            return $this->forbiddenResponse('Cannot create group in unauthorized branch');
        }

        $validated['company_id'] = $user->resolveScopedCompanyId($request->input('company_id'));
        $validated['formation_date'] = $validated['formation_date'] ?? now()->toDateString();
        $validated['status'] = 'active';

        $group = $this->groupService->createGroup($validated);

        return $this->successResponse($group, 'Customer group created successfully', 201);
    }

    /**
     * Add member to customer group.
     */
    public function addMember(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $group = CustomerGroup::find($id);

        if (!$group) {
            return $this->notFoundResponse('Customer group not found');
        }

        if (!$user->canAccessCompany($group->company_id) || !$user->canAccessBranch($group->branch_id)) {
            return $this->forbiddenResponse('Unauthorized access to add member to another branch group');
        }

        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'role' => 'nullable|in:group_leader,member,secretary,treasurer',
        ]);

        $customer = \App\Models\Customer::find($validated['customer_id']);
        if (!$customer) {
            return $this->notFoundResponse('Customer not found');
        }

        if (!$user->canAccessCompany($customer->company_id) || !$user->canAccessBranch($customer->branch_id) || (int) $customer->branch_id !== (int) $group->branch_id) {
            return $this->forbiddenResponse('Customer must belong to the same branch as the group');
        }

        try {
            $member = $this->groupService->addMemberToGroup($group, $customer->id, $validated['role'] ?? 'member');

            return $this->successResponse($member, 'Member added to group successfully', 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->errorResponse(implode(' ', \Illuminate\Support\Arr::flatten($e->errors())), 422);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Remove member from customer group.
     */
    public function removeMember(Request $request, int $id, int $customerId): JsonResponse
    {
        $user = $request->user();
        $group = CustomerGroup::find($id);

        if (!$group) {
            return $this->notFoundResponse('Customer group not found');
        }

        if (!$user->canAccessCompany($group->company_id) || !$user->canAccessBranch($group->branch_id)) {
            return $this->forbiddenResponse('Unauthorized access to remove member from another branch group');
        }

        try {
            $this->groupService->removeMemberFromGroup($group, $customerId);

            return $this->successResponse(null, 'Member removed from group successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }
}

