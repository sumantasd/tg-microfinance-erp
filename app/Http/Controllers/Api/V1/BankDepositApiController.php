<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\BankDeposit;
use App\Services\BankDepositService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BankDepositApiController extends Controller
{
    use ApiResponse;

    public function __construct(protected BankDepositService $bankDepositService) {}

    /**
     * List bank deposits for authorized branch / filters.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $scopedBranchId = $user->resolveScopedBranchId($request->query('branch_id'));

        $query = BankDeposit::with(['branch', 'submittedBy', 'approvedBy'])
            ->orderByDesc('deposit_date')
            ->orderByDesc('id');

        if ($scopedBranchId) {
            $query->where('branch_id', $scopedBranchId);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('deposit_date', '>=', $request->query('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('deposit_date', '<=', $request->query('date_to'));
        }

        $deposits = $query->paginate($request->query('per_page', 15));

        return $this->successResponse($deposits, 'Bank deposits retrieved successfully');
    }

    /**
     * Submit a new bank deposit.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'deposit_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'bank_name' => 'required|string|max:150',
            'account_number' => 'nullable|string|max:50',
            'reference_number' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:500',
        ]);

        if (!$user->canAccessBranch($validated['branch_id'])) {
            return $this->forbiddenResponse('Unauthorized access to submit bank deposit for another branch');
        }

        try {
            $deposit = $this->bankDepositService->submitDeposit($validated, $user);
            return $this->successResponse($deposit->load(['branch', 'submittedBy']), 'Bank deposit submitted successfully', 201);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * Get specific bank deposit details.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $deposit = BankDeposit::with(['branch', 'submittedBy', 'approvedBy'])->find($id);

        if (!$deposit) {
            return $this->notFoundResponse('Bank deposit record not found');
        }

        if (!$user->canAccessBranch($deposit->branch_id)) {
            return $this->forbiddenResponse('Unauthorized access to bank deposit in another branch');
        }

        return $this->successResponse($deposit, 'Bank deposit details retrieved');
    }

    /**
     * Approve a submitted bank deposit (Admin / BM role).
     */
    public function approve(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $deposit = BankDeposit::find($id);

        if (!$deposit) {
            return $this->notFoundResponse('Bank deposit record not found');
        }

        if (!$user->canAccessBranch($deposit->branch_id)) {
            return $this->forbiddenResponse('Unauthorized access to approve bank deposit in another branch');
        }

        try {
            $approved = $this->bankDepositService->approveDeposit($deposit, $user, $request->input('remarks'));
            return $this->successResponse($approved->load(['branch', 'submittedBy', 'approvedBy']), 'Bank deposit approved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * Reject a submitted bank deposit (Admin / BM role).
     */
    public function reject(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $deposit = BankDeposit::find($id);

        if (!$deposit) {
            return $this->notFoundResponse('Bank deposit record not found');
        }

        if (!$user->canAccessBranch($deposit->branch_id)) {
            return $this->forbiddenResponse('Unauthorized access to reject bank deposit in another branch');
        }

        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        try {
            $rejected = $this->bankDepositService->rejectDeposit($deposit, $user, $validated['rejection_reason']);
            return $this->successResponse($rejected->load(['branch', 'submittedBy', 'approvedBy']), 'Bank deposit rejected successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }
}
