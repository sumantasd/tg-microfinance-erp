<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\LoanAccount;
use App\Models\LoanRepayment;
use App\Services\LoanAccountService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CollectionApiController extends Controller
{
    use ApiResponse;

    protected LoanAccountService $loanAccountService;

    public function __construct(LoanAccountService $loanAccountService)
    {
        $this->loanAccountService = $loanAccountService;
    }

    /**
     * Search customer or loan for field collection.
     */
    public function search(Request $request): JsonResponse
    {
        $user = $request->user();
        $scopedBranchId = $user->resolveScopedBranchId($request->query('branch_id'));
        $search = $request->query('q');

        if (!$search) {
            return $this->errorResponse('Search query parameter q is required', 422);
        }

        $query = LoanAccount::with(['customer', 'customerGroup', 'installments' => function ($q) {
            $q->whereIn('status', ['pending', 'partial', 'overdue']);
        }])->where('status', 'active');

        if ($scopedBranchId) {
            $query->where('branch_id', $scopedBranchId);
        }

        $query->where(function ($q) use ($search) {
            $q->where('loan_number', 'like', "%{$search}%")
              ->orWhereHas('customer', function ($cq) use ($search) {
                  $cq->where('first_name', 'like', "%{$search}%")
                     ->orWhere('last_name', 'like', "%{$search}%")
                     ->orWhere('mobile_number', 'like', "%{$search}%")
                     ->orWhere('customer_code', 'like', "%{$search}%");
              });
        });

        $loans = $query->limit(20)->get();

        $formattedResults = $loans->map(function ($loan) {
            $overdueInstallments = $loan->installments->filter(function ($inst) {
                return $inst->due_date && $inst->due_date->toDateString() < now()->toDateString();
            });

            $totalInstallmentAmount = $loan->installments->sum(function ($i) {
                return (float) ($i->installment_amount ?? $i->amount ?? 0);
            });

            $totalPaidAmount = $loan->installments->sum(function ($i) {
                return (float) ($i->total_paid ?? $i->paid_amount ?? 0);
            });

            $dueAmount = max(0, $totalInstallmentAmount - $totalPaidAmount);

            $overdueAmount = max(0, $overdueInstallments->sum(function ($i) {
                return (float) ($i->installment_amount ?? $i->amount ?? 0);
            }) - $overdueInstallments->sum(function ($i) {
                return (float) ($i->total_paid ?? $i->paid_amount ?? 0);
            }));

            $customerName = $loan->customer?->full_name;
            if (empty(trim((string) $customerName))) {
                $customerName = trim(($loan->customer?->first_name ?? '') . ' ' . ($loan->customer?->last_name ?? ''));
            }

            return [
                'loan_account_id' => $loan->id,
                'account_number' => $loan->loan_number ?? $loan->account_number,
                'customer_id' => $loan->customer_id,
                'customer_name' => $customerName,
                'customer_code' => $loan->customer?->customer_code,
                'mobile_number' => $loan->customer?->mobile_number,
                'sanctioned_amount' => (float) $loan->sanctioned_amount,
                'total_due_amount' => (float) $dueAmount,
                'overdue_amount' => (float) $overdueAmount,
                'pending_installments_count' => $loan->installments->count(),
                'next_installment' => $loan->installments->first() ? [
                    'installment_number' => $loan->installments->first()->installment_number,
                    'due_date' => $loan->installments->first()->due_date?->toDateString(),
                    'installment_amount' => (float) ($loan->installments->first()->installment_amount ?? $loan->installments->first()->amount ?? 0),
                    'status' => $loan->installments->first()->status,
                ] : null,
            ];
        });

        return $this->successResponse($formattedResults, 'Collection search results retrieved');
    }

    /**
     * Process EMI collection with GPS coordinates & duplicate offline_sync_id protection.
     */
    public function submitEmi(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'loan_account_id' => 'required|exists:loan_accounts,id',
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'nullable|in:cash,bank,online',
            'bank_account_id' => 'nullable|required_if:payment_method,bank|exists:bank_accounts,id',
            'offline_sync_id' => 'nullable|string|max:100',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'accuracy' => 'nullable|numeric',
            'remarks' => 'nullable|string|max:255',
        ]);

        $loanAccount = LoanAccount::find($validated['loan_account_id']);

        if (!$loanAccount) {
            return $this->notFoundResponse('Loan account not found');
        }

        if (!$user->canAccessBranch($loanAccount->branch_id)) {
            return $this->forbiddenResponse('Cannot collect EMI for another branch loan account');
        }

        // Duplicate offline_sync_id protection
        if (!empty($validated['offline_sync_id'])) {
            $existing = LoanRepayment::where('remarks', 'like', "%[SyncID:{$validated['offline_sync_id']}]%")->first();
            if ($existing) {
                return $this->successResponse([
                    'repayment_id' => $existing->id,
                    'receipt_number' => $existing->receipt_number,
                    'amount_paid' => (float) $existing->amount,
                    'is_duplicate' => true,
                ], 'Collection already processed (Duplicate sync ID ignored)');
            }
        }

        try {
            DB::beginTransaction();

            $remarks = ($validated['remarks'] ?? 'Mobile EMI Collection');
            if (!empty($validated['offline_sync_id'])) {
                $remarks .= " [SyncID:{$validated['offline_sync_id']}]";
            }
            if (isset($validated['latitude']) && isset($validated['longitude'])) {
                $remarks .= " [GPS:{$validated['latitude']},{$validated['longitude']}]";
            }

            $updatedAccount = $this->loanAccountService->recordRepayment(
                $loanAccount,
                (float) $validated['amount'],
                $validated['payment_method'] ?? 'cash',
                $validated['offline_sync_id'] ?? null,
                'reduce_tenure',
                $remarks,
                now()->toDateString()
            );

            $repayment = LoanRepayment::where('loan_account_id', $loanAccount->id)->latest()->first();

            DB::commit();

            return $this->successResponse([
                'repayment_id' => $repayment?->id,
                'receipt_number' => $repayment?->receipt_number,
                'amount_paid' => (float) ($repayment?->amount ?? $validated['amount']),
                'principal_allocated' => (float) ($repayment?->principal_paid ?? 0),
                'interest_allocated' => (float) ($repayment?->interest_paid ?? 0),
                'penalty_allocated' => (float) ($repayment?->penalty_paid ?? 0),
                'remaining_loan_balance' => (float) $updatedAccount->total_outstanding,
                'collected_by' => $user->name,
                'collected_at' => now()->toIso8601String(),
                'gps_location' => [
                    'latitude' => $validated['latitude'] ?? null,
                    'longitude' => $validated['longitude'] ?? null,
                ],
            ], 'EMI collection processed successfully', 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->errorResponse('Failed to record EMI repayment: ' . $e->getMessage(), 400);
        }
    }
}
