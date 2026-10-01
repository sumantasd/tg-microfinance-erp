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

        if (!$user->canAccessBranch($scopedBranchId)) {
            return $this->forbiddenResponse('Unauthorized access to cash book in another branch');
        }

        $companyId = $user->company_id ?? 1;
        $date = $request->query('date', now()->toDateString());
        $cashBook = $this->cashBookService->getOrCreateCashBook($companyId, $scopedBranchId, $date, $user->id);
        $cashBook->load(['entries', 'onlineCollections', 'branch']);

        return $this->successResponse($cashBook, 'Cash book register retrieved');
    }

    /**
     * Add particular entry to cash book register.
     */
    public function addEntry(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'entry_type' => 'required|in:received,payment',
            'particulars' => 'required|string|max:255',
            'cash_amount' => 'required|numeric|min:0.01',
            'date' => 'nullable|date',
            'remarks' => 'nullable|string|max:255',
        ]);

        if (!$user->canAccessBranch($validated['branch_id'])) {
            return $this->forbiddenResponse('Unauthorized access to add cash book entry in another branch');
        }

        $companyId = $user->company_id ?? 1;
        $date = $validated['date'] ?? now()->toDateString();
        $cashBook = $this->cashBookService->getOrCreateCashBook($companyId, $validated['branch_id'], $date, $user->id);

        $entry = \App\Models\CashBookEntry::create([
            'cash_book_id' => $cashBook->id,
            'entry_type' => $validated['entry_type'],
            'category_code' => 'manual_entry',
            'particulars' => $validated['particulars'],
            'entry_date' => $date,
            'cash_amount' => $validated['cash_amount'],
            'product_amount' => 0.00,
            'bank_amount' => 0.00,
            'sort_order' => 99,
            'remarks' => $validated['remarks'] ?? null,
            'created_by' => $user->id,
        ]);

        $this->cashBookService->recalculateTotals($cashBook);

        return $this->successResponse($entry->fresh(), 'Cash book entry created successfully', 201);
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

        $companyId = $user->company_id ?? 1;
        $date = $validated['date'] ?? now()->toDateString();
        $cashBook = $this->cashBookService->getOrCreateCashBook($companyId, $validated['branch_id'], $date, $user->id);

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

        $companyId = $user->company_id ?? 1;
        $date = $validated['date'] ?? now()->toDateString();
        $cashBook = $this->cashBookService->getOrCreateCashBook($companyId, $validated['branch_id'], $date, $user->id);

        $closedCashBook = $this->cashBookService->closeCashBook($cashBook, $user->id, $validated['notes'] ?? null);

        return $this->successResponse($closedCashBook, 'Cash book register closed and locked successfully');
    }
}
