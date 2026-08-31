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

        if (!$user->canAccessBranch($group->branch_id)) {
            return $this->forbiddenResponse('Unauthorized access to another branch group');
        }

        return $this->successResponse($group, 'Customer group profile retrieved');
    }
}
