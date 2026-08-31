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
        $cashBook->load(['entries', 'denominations', 'branch']);

        return $this->successResponse($cashBook, 'Cash book register retrieved');
    }

    /**
     * Add particular entry to cash book.
     */
    public function addEntry(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'type' => 'required|in:received,payment',
            'category' => 'required|string|max:100',
            'particulars' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'voucher_number' => 'nullable|string|max:50',
            'date' => 'nullable|date',
        ]);

        if (!$user->canAccessBranch($validated['branch_id'])) {
            return $this->forbiddenResponse('Unauthorized access to add entry in another branch');
        }

        $date = $validated['date'] ?? now()->toDateString();
        $cashBook = $this->cashBookService->getOrCreateCashBook($validated['branch_id'], $date, $user->id);

        if ($cashBook->isClosed()) {
            return $this->errorResponse('Cash book register is closed for today. Reopen to add entries.', 422);
        }

        $entry = $this->cashBookService->addEntry($cashBook, [
            'type' => $validated['type'],
            'category' => $validated['category'],
            'particulars' => $validated['particulars'],
            'amount' => $validated['amount'],
            'voucher_number' => $validated['voucher_number'] ?? null,
            'created_by' => $user->id,
        ]);

        return $this->successResponse($entry, 'Cash book entry added successfully', 201);
    }

    /**
     * Save cash denomination matrix.
     */
    public function saveDenomination(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'date' => 'nullable|date',
            'denominations' => 'required|array',
            'denominations.*' => 'integer|min:0',
        ]);

        if (!$user->canAccessBranch($validated['branch_id'])) {
            return $this->forbiddenResponse('Unauthorized access to update cash denomination');
        }

        $date = $validated['date'] ?? now()->toDateString();
        $cashBook = $this->cashBookService->getOrCreateCashBook($validated['branch_id'], $date, $user->id);

        $this->cashBookService->saveDenominations($cashBook, $validated['denominations']);

        return $this->successResponse($cashBook->fresh(['denominations']), 'Cash denomination matrix saved');
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
