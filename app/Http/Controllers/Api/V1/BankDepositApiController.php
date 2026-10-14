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
     * List active bank accounts configured for deposit submission.
     */
    public function accounts(Request $request): JsonResponse
    {
        $user = $request->user();
        $companyId = $user->company_id ?? 1;
        $branchId = $user->branch_id;

        $accounts = $this->bankDepositService->getActiveBankAccounts($companyId, $branchId);

        $data = $accounts->map(function ($acc) {
            return [
                'id' => $acc->id,
                'bank_name' => $acc->bank_name,
                'account_name' => $acc->account_name,
                'account_number_masked' => $acc->masked_account_number,
                'masked_account_number' => $acc->masked_account_number,
                'ifsc_code' => $acc->ifsc_code,
                'branch_name' => $acc->branch_name,
                'nickname' => $acc->account_nickname,
                'display_name' => $acc->display_name,
            ];
        });

        return $this->successResponse($data, 'Active bank accounts retrieved successfully');
    }

    /**
     * Submit a new bank deposit.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$request->has('branch_id') && $user->branch_id) {
            $request->merge(['branch_id' => $user->branch_id]);
        }

        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'deposit_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'bank_account_id' => 'nullable|exists:bank_accounts,id',
            'bank_name' => 'nullable|string|max:150',
            'account_number' => 'nullable|string|max:50',
            'reference_number' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:500',
            'receipt' => 'required|file|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        if (!$user->canAccessBranch($validated['branch_id'])) {
            return $this->forbiddenResponse('Unauthorized access to submit bank deposit for another branch');
        }

        try {
            $deposit = $this->bankDepositService->submitDeposit($validated, $user, $request->file('receipt'));
            return $this->successResponse($deposit->load(['bankAccount', 'branch', 'submittedBy']), 'Bank deposit submitted successfully', 201);
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
        $deposit = BankDeposit::with(['bankAccount', 'branch', 'submittedBy', 'approvedBy', 'rejectedBy'])->find($id);

        if (!$deposit) {
            return $this->notFoundResponse('Bank deposit record not found');
        }

        if (!$user->canAccessBranch($deposit->branch_id)) {
            return $this->forbiddenResponse('Unauthorized access to bank deposit in another branch');
        }

        return $this->successResponse($deposit, 'Bank deposit details retrieved');
    }

    /**
     * Securely stream receipt image to authorized mobile user.
     */
    public function receipt(Request $request, int $id)
    {
        $user = $request->user();
        $deposit = BankDeposit::find($id);

        if (!$deposit) {
            return $this->notFoundResponse('Bank deposit record not found');
        }

        if (!$user->canAccessBranch($deposit->branch_id)) {
            return $this->forbiddenResponse('Unauthorized access to bank deposit receipt in another branch');
        }

        $filePath = $this->bankDepositService->getReceiptFilePath($deposit);

        return response()->file($filePath, [
            'Content-Type' => $deposit->receipt_mime_type ?: 'image/jpeg',
            'Content-Disposition' => 'inline; filename="' . ($deposit->receipt_original_name ?: "deposit-receipt-{$deposit->id}.jpg") . '"',
        ]);
    }

    /**
     * Resubmit a rejected bank deposit.
     */
    public function resubmit(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $oldDeposit = BankDeposit::find($id);

        if (!$oldDeposit) {
            return $this->notFoundResponse('Bank deposit record not found');
        }

        if (!$user->canAccessBranch($oldDeposit->branch_id)) {
            return $this->forbiddenResponse('Unauthorized access to bank deposit in another branch');
        }

        if ($oldDeposit->status !== 'rejected') {
            return $this->errorResponse('Only rejected bank deposits can be resubmitted.', 422);
        }

        if (!$request->has('deposit_date')) {
            $request->merge(['deposit_date' => $oldDeposit->deposit_date]);
        }
        if (!$request->has('bank_account_id') && $oldDeposit->bank_account_id) {
            $request->merge(['bank_account_id' => $oldDeposit->bank_account_id]);
        }

        $validated = $request->validate([
            'deposit_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'bank_account_id' => 'nullable|exists:bank_accounts,id',
            'bank_name' => 'nullable|string|max:150',
            'account_number' => 'nullable|string|max:50',
            'reference_number' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:500',
            'receipt' => 'nullable|file|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $validated['branch_id'] = $oldDeposit->branch_id;
        $validated['company_id'] = $oldDeposit->company_id;
        if (empty($validated['description'])) {
            $validated['description'] = "Resubmission of rejected deposit #{$oldDeposit->reference_number}";
        }

        try {
            $deposit = $this->bankDepositService->resubmitDeposit($oldDeposit, $validated, $user, $request->file('receipt'));
            return $this->successResponse($deposit->load(['bankAccount', 'branch', 'submittedBy']), 'Bank deposit resubmitted successfully', 200);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
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
            return $this->successResponse($approved->load(['bankAccount', 'branch', 'submittedBy', 'approvedBy']), 'Bank deposit approved successfully');
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
            return $this->successResponse($rejected->load(['bankAccount', 'branch', 'submittedBy', 'approvedBy', 'rejectedBy']), 'Bank deposit rejected successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }
}
