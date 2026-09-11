<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CashBook;
use App\Services\CashBookService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CashBookApiController extends Controller
{
    use ApiResponse;

    protected CashBookService $cashBookService;

    public function __construct(CashBookService $cashBookService)
    {
        $this->cashBookService = $cashBookService;
    }

    /**
     * Get cash book register for assigned branch and date.
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $scopedBranchId = $user->resolveScopedBranchId($request->query('branch_id'));

        if (!$scopedBranchId) {
            return $this->errorResponse('Branch selection is required', 422);
        }

        $date = $request->query('date', now()->toDateString());
        $cashBook = $this->cashBookService->getOrCreateCashBook($scopedBranchId, $date, $user->id);
        $cashBook->load(['entries', 'onlineCollections', 'branch']);

        return $this->successResponse($cashBook, 'Cash book register retrieved');
    }

    /**
     * Add particular entry to cash book (Disabled - automatic register).
     */
    public function addEntry(Request $request): JsonResponse
    {
        return $this->errorResponse('Manual entry creation is disabled. Cash Book entries populate automatically from ERP transactions.', 422);
    }

    /**
     * Save cash denomination matrix (Disabled - physical count section removed).
     */
    public function saveDenomination(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'date' => 'nullable|date',
        ]);

        if (!$user->canAccessBranch($validated['branch_id'])) {
            return $this->forbiddenResponse('Unauthorized access to update cash denomination');
        }

        $date = $validated['date'] ?? now()->toDateString();
        $cashBook = $this->cashBookService->getOrCreateCashBook($validated['branch_id'], $date, $user->id);

        return $this->successResponse($cashBook->fresh(['entries', 'onlineCollections']), 'Cash Book register reconciled');
    }

    /**
     * Daily closing / locking of cash book.
     */
    public function closeRegister(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'date' => 'nullable|date',
            'notes' => 'nullable|string|max:255',
        ]);

        if (!$user->canAccessBranch($validated['branch_id'])) {
            return $this->forbiddenResponse('Unauthorized access to close register in another branch');
        }

        $date = $validated['date'] ?? now()->toDateString();
        $cashBook = $this->cashBookService->getOrCreateCashBook($validated['branch_id'], $date, $user->id);

        $closedCashBook = $this->cashBookService->closeCashBook($cashBook, $user->id, $validated['notes'] ?? null);

        return $this->successResponse($closedCashBook, 'Cash book register closed and locked successfully');
    }
}
