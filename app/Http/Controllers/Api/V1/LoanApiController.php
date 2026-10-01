<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\LoanAccount;
use App\Models\LoanApplication;
use App\Models\LoanScheme;
use App\Services\LoanAccountService;
use App\Services\LoanApplicationService;
use App\Services\LoanSettlementService;
use App\Services\OverdueService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LoanApiController extends Controller
{
    use ApiResponse;

    protected LoanAccountService $loanAccountService;
    protected LoanApplicationService $loanApplicationService;
    protected OverdueService $overdueService;
    protected LoanSettlementService $settlementService;

    public function __construct(
        LoanAccountService $loanAccountService,
        LoanApplicationService $loanApplicationService,
        OverdueService $overdueService,
        LoanSettlementService $settlementService
    ) {
        $this->loanAccountService = $loanAccountService;
        $this->loanApplicationService = $loanApplicationService;
        $this->overdueService = $overdueService;
        $this->settlementService = $settlementService;
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

        if (!$user->canAccessCompany($account->company_id) || !$user->canAccessBranch($account->branch_id)) {
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

        if (!$user->canAccessCompany($account->company_id) || !$user->canAccessBranch($account->branch_id)) {
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

    /**
     * Submit a new loan application from mobile.
     */
    public function storeApplication(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'loan_scheme_id' => 'required|exists:loan_schemes,id',
            'loan_type' => 'required|in:cash,product',
            'borrower_type' => 'required|in:individual,group',
            'customer_id' => 'nullable|required_if:borrower_type,individual|exists:customers,id',
            'customer_group_id' => 'nullable|required_if:borrower_type,group|exists:customer_groups,id',
            'requested_amount' => 'nullable|numeric|min:100',
            'tenure_months' => 'nullable|integer|min:1',
            'application_date' => 'nullable|date',
            'purpose' => 'nullable|string|max:255',
            'remarks' => 'nullable|string|max:255',
            'auto_submit' => 'nullable|boolean',
            'products' => 'nullable|array',
            'products.*.category_id' => 'required_with:products|exists:product_categories,id',
            'products.*.brand_id' => 'required_with:products|exists:product_brands,id',
            'products.*.product_id' => 'required_with:products|exists:products,id',
            'products.*.quantity' => 'required_with:products|integer|min:1',
            'members' => 'nullable|array',
            'members.*.customer_id' => 'required_with:members|exists:customers,id',
            'members.*.requested_amount' => 'required_with:members|numeric|min:1',
        ]);

        if (!$user->canAccessBranch($validated['branch_id'])) {
            return $this->forbiddenResponse('Cannot submit loan application for unauthorized branch');
        }

        try {
            $application = $this->loanApplicationService->createApplication(
                $validated,
                $validated['members'] ?? [],
                $validated['products'] ?? []
            );

            if (!empty($validated['auto_submit'])) {
                $application = $this->loanApplicationService->submitApplication($application);
            }

            return $this->successResponse($application->load(['customer', 'customerGroup', 'loanScheme', 'branch']), 'Loan application created successfully', 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->errorResponse(implode(' ', \Illuminate\Support\Arr::flatten($e->errors())), 422);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Review / Approve / Reject loan application.
     */
    public function reviewApplication(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $application = LoanApplication::find($id);

        if (!$application) {
            return $this->notFoundResponse('Loan application not found');
        }

        if (!$user->canAccessCompany($application->company_id) || !$user->canAccessBranch($application->branch_id)) {
            return $this->forbiddenResponse('Unauthorized access to review application from another branch');
        }

        $validated = $request->validate([
            'action' => 'required|in:start_review,approve,reject',
            'approved_amount' => 'nullable|numeric|min:1',
            'rejection_reason' => 'nullable|required_if:action,reject|string|max:255',
        ]);

        if ($validated['action'] === 'approve' && !$user->can('loan_application.approve')) {
            return $this->forbiddenResponse('Permission denied to approve loan application');
        }

        if ($validated['action'] === 'reject' && !$user->can('loan_application.reject')) {
            return $this->forbiddenResponse('Permission denied to reject loan application');
        }

        if ($validated['action'] === 'start_review' && !($user->can('loan_application.review') || $user->can('loan_application.approve'))) {
            return $this->forbiddenResponse('Permission denied to start loan application review');
        }

        try {
            if ($validated['action'] === 'start_review') {
                $updated = $this->loanApplicationService->startReview($application);
            } elseif ($validated['action'] === 'approve') {
                $updated = $this->loanApplicationService->approveApplication($application, $validated['approved_amount'] ?? null);
            } else {
                $updated = $this->loanApplicationService->rejectApplication($application, $validated['rejection_reason']);
            }

            return $this->successResponse($updated, 'Loan application status updated successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->errorResponse(implode(' ', \Illuminate\Support\Arr::flatten($e->errors())), 422);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Get complete repayment schedule and repayment history for a loan account.
     */
    public function schedule(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $account = LoanAccount::with([
            'customer',
            'loanScheme',
            'branch',
            'installments' => function ($q) {
                $q->orderBy('installment_number', 'asc');
            },
            'repayments' => function ($q) {
                $q->orderBy('payment_date', 'desc');
            },
        ])->find($id);

        if (!$account) {
            return $this->notFoundResponse('Loan account not found');
        }

        if (!$user->canAccessCompany($account->company_id) || !$user->canAccessBranch($account->branch_id)) {
            return $this->forbiddenResponse('Unauthorized access to loan schedule');
        }

        return $this->successResponse([
            'loan_account' => [
                'id' => $account->id,
                'loan_number' => $account->loan_number,
                'customer_name' => $account->customer?->full_name,
                'sanctioned_amount' => (float) $account->sanctioned_amount,
                'principal_outstanding' => (float) $account->principal_outstanding,
                'interest_outstanding' => (float) $account->interest_outstanding,
                'total_outstanding' => (float) $account->total_outstanding,
                'status' => $account->status,
            ],
            'installments' => $account->installments,
            'repayments' => $account->repayments,
        ], 'Loan repayment schedule and history retrieved');
    }

    /**
     * Get overdue loan accounts with DPD calculation.
     */
    public function overdueList(Request $request): JsonResponse
    {
        $user = $request->user();
        $scopedBranchId = $user->resolveScopedBranchId($request->query('branch_id'));

        $query = LoanAccount::with(['customer', 'branch', 'installments'])
            ->whereIn('status', ['active', 'defaulted']);

        if ($scopedBranchId) {
            $query->where('branch_id', $scopedBranchId);
        }

        $perPage = min((int) ($request->query('per_page', 15)), 100);
        $accounts = $query->latest()->paginate($perPage);

        $items = collect($accounts->items())->map(function ($account) {
            $overdueDetails = $this->overdueService->getLoanOverdueDetails($account);
            return [
                'loan_id' => $account->id,
                'loan_number' => $account->loan_number,
                'customer_id' => $account->customer_id,
                'customer_name' => $account->customer?->full_name,
                'customer_code' => $account->customer?->customer_code,
                'mobile_number' => $account->customer?->mobile_number,
                'branch_name' => $account->branch?->name,
                'dpd' => $overdueDetails['dpd'],
                'overdue_amount' => $overdueDetails['overdue_amount'],
                'aging_bucket' => $overdueDetails['aging_bucket'],
                'overdue_installments_count' => $overdueDetails['overdue_installments_count'],
                'oldest_overdue_date' => $overdueDetails['oldest_overdue_date'],
                'principal_outstanding' => (float) $account->principal_outstanding,
                'total_outstanding' => (float) $account->total_outstanding,
                'status' => $account->status,
            ];
        })->filter(fn($item) => $item['dpd'] > 0 || $item['overdue_amount'] > 0)->values();

        return $this->successResponse([
            'current_page' => $accounts->currentPage(),
            'per_page' => $accounts->perPage(),
            'total' => $accounts->total(),
            'data' => $items,
        ], 'Overdue loan accounts list retrieved');
    }

    /**
     * Get loan settlement / foreclosure calculation quote.
     */
    public function settlementQuote(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $account = LoanAccount::with(['loanScheme', 'installments'])->find($id);

        if (!$account) {
            return $this->notFoundResponse('Loan account not found');
        }

        if (!$user->canAccessCompany($account->company_id) || !$user->canAccessBranch($account->branch_id)) {
            return $this->forbiddenResponse('Unauthorized access to loan settlement quote');
        }

        $type = $request->query('type', 'foreclosure');
        if ($type === 'foreclosure') {
            $quote = $this->settlementService->calculateForeclosure($account);
        } else {
            $proposedAmount = (float) $request->query('proposed_amount', $account->total_outstanding);
            $quote = $this->settlementService->calculateSettlementOts($account, $proposedAmount);
        }

        return $this->successResponse($quote, 'Settlement quote calculated successfully');
    }

    /**
     * Process loan settlement or voluntary foreclosure payment.
     */
    public function processSettlement(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $account = LoanAccount::find($id);

        if (!$account) {
            return $this->notFoundResponse('Loan account not found');
        }

        if (!$user->canAccessCompany($account->company_id) || !$user->canAccessBranch($account->branch_id)) {
            return $this->forbiddenResponse('Unauthorized access to process settlement for another branch');
        }

        $validated = $request->validate([
            'request_type' => 'required|in:foreclosure,settlement_ots,write_off',
            'payment_method' => 'nullable|in:cash,bank,online',
            'proposed_settlement_amount' => 'nullable|numeric|min:0',
            'remarks' => 'nullable|string|max:255',
            'execute_now' => 'nullable|boolean',
        ]);

        if ($validated['request_type'] === 'foreclosure' && !$user->can('loan_foreclosure.process')) {
            return $this->forbiddenResponse('Permission denied to process foreclosure');
        }

        if ($validated['request_type'] === 'settlement_ots' && !($user->can('loan_settlement.request') || $user->can('loan_settlement.approve'))) {
            return $this->forbiddenResponse('Permission denied to request settlement');
        }

        if ($validated['request_type'] === 'write_off' && !($user->can('loan_write_off.request') || $user->can('loan_write_off.approve'))) {
            return $this->forbiddenResponse('Permission denied to request write-off');
        }

        try {
            if (!empty($validated['execute_now']) && $validated['request_type'] === 'foreclosure') {
                $result = $this->settlementService->executeForeclosure($account, $validated, $user);
                return $this->successResponse($result, 'Loan foreclosure executed and settled successfully');
            }

            $settlementRequest = $this->settlementService->createSettlementRequest($account, $validated, $user);
            return $this->successResponse($settlementRequest, 'Settlement request submitted successfully', 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->errorResponse(implode(' ', \Illuminate\Support\Arr::flatten($e->errors())), 422);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }
}
