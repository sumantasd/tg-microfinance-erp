<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\LoanAccount;
use App\Models\LoanApplication;
use App\Models\LoanScheme;
use App\Services\LoanAccountService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LoanApiController extends Controller
{
    use ApiResponse;

    protected LoanAccountService $loanAccountService;

    public function __construct(LoanAccountService $loanAccountService)
    {
        $this->loanAccountService = $loanAccountService;
    }

    /**
     * Get active loan schemes/products.
     */
    public function schemes(Request $request): JsonResponse
    {
        $schemes = LoanScheme::where('is_active', true)->get();

        return $this->successResponse($schemes, 'Active loan schemes retrieved');
    }

    /**
     * Loan applications listing.
     */
    public function applications(Request $request): JsonResponse
    {
        $user = $request->user();
        $scopedBranchId = $user->resolveScopedBranchId($request->query('branch_id'));

        $query = LoanApplication::with(['customer', 'customerGroup', 'loanScheme', 'branch', 'products.product']);

        if ($scopedBranchId) {
            $query->where('branch_id', $scopedBranchId);
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $applications = $query->latest()->paginate((int) ($request->query('per_page', 15)));

        return $this->successResponse($applications, 'Loan applications retrieved');
    }

    /**
     * Active loan accounts listing.
     */
    public function accounts(Request $request): JsonResponse
    {
        $user = $request->user();
        $scopedBranchId = $user->resolveScopedBranchId($request->query('branch_id'));

        $query = LoanAccount::with(['customer', 'customerGroup', 'loanScheme', 'branch']);

        if ($scopedBranchId) {
            $query->where('branch_id', $scopedBranchId);
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $accounts = $query->latest()->paginate((int) ($request->query('per_page', 15)));

        return $this->successResponse($accounts, 'Loan accounts retrieved');
    }

    /**
     * Single loan account 360° details and repayment schedule.
     */
    public function showAccount(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $account = LoanAccount::with([
            'customer',
            'customerGroup',
            'loanScheme',
            'branch',
            'installments',
            'repayments',
        ])->find($id);

        if (!$account) {
            return $this->notFoundResponse('Loan account not found');
        }

        if (!$user->canAccessBranch($account->branch_id)) {
            return $this->forbiddenResponse('Unauthorized access to another branch loan account');
        }

        return $this->successResponse($account, 'Loan account profile retrieved');
    }

    /**
     * Disburse loan using existing LoanAccountService.
     */
    public function disburse(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $account = LoanAccount::find($id);

        if (!$account) {
            return $this->notFoundResponse('Loan account not found');
        }

        if (!$user->canAccessBranch($account->branch_id)) {
            return $this->forbiddenResponse('Unauthorized access to disburse loan in another branch');
        }

        $validated = $request->validate([
            'payment_method' => 'nullable|required_if:loan_type,cash|in:cash,bank,product_fulfillment',
            'bank_account_id' => 'nullable|required_if:payment_method,bank|exists:bank_accounts,id',
            'disbursement_date' => 'nullable|date',
            'remarks' => 'nullable|string|max:255',
        ]);

        try {
            if ($account->loan_type === 'product') {
                $result = $this->loanAccountService->issueProductLoan($account, $validated['remarks'] ?? null);
            } else {
                $result = $this->loanAccountService->disburseCashLoan(
                    $account,
                    $validated['payment_method'] ?? 'cash',
                    $validated['bank_account_id'] ?? null,
                    $validated['remarks'] ?? null
                );
            }

            return $this->successResponse($result, 'Loan disbursed successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }
}
