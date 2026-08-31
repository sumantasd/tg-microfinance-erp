<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\LoanAccount;
use App\Models\Branch;
use App\Models\CashBook;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardApiController extends Controller
{
    use ApiResponse;

    /**
     * Mobile dashboard statistics endpoint.
     */
    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();
        $scopedBranchId = $user->resolveScopedBranchId($request->query('branch_id'));

        $customerQuery = Customer::query();
        $groupQuery = CustomerGroup::query();
        $loanQuery = LoanAccount::query()->where('status', 'active');
        $cashBookQuery = CashBook::query()->where('date', now()->toDateString());

        if ($scopedBranchId) {
            $customerQuery->where('branch_id', $scopedBranchId);
            $groupQuery->where('branch_id', $scopedBranchId);
            $loanQuery->where('branch_id', $scopedBranchId);
            $cashBookQuery->where('branch_id', $scopedBranchId);
        }

        $branchInfo = null;
        if ($scopedBranchId) {
            $branch = Branch::find($scopedBranchId);
            if ($branch) {
                $branchInfo = [
                    'id' => $branch->id,
                    'name' => $branch->name,
                    'code' => $branch->code,
                    'vault_limit' => (float) $branch->vault_cash_limit,
                    'vault_balance' => (float) $branch->current_vault_balance,
                ];
            }
        }

        $cashBookToday = $cashBookQuery->first();

        return $this->successResponse([
            'total_customers' => $customerQuery->count(),
            'total_groups' => $groupQuery->count(),
            'active_loans_count' => $loanQuery->count(),
            'total_active_portfolio' => (float) $loanQuery->sum('sanctioned_amount'),
            'today_cash_book' => [
                'status' => $cashBookToday?->status ?? 'pending',
                'opening_balance' => (float) ($cashBookToday?->opening_cash ?? 0),
                'total_received' => (float) ($cashBookToday?->total_received ?? 0),
                'total_payments' => (float) ($cashBookToday?->total_payments ?? 0),
                'closing_balance' => (float) ($cashBookToday?->closing_cash ?? 0),
            ],
            'scoped_branch' => $branchInfo,
        ], 'Mobile dashboard statistics retrieved');
    }
}
