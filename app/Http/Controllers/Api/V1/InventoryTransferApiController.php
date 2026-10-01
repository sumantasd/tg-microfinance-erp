<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\InventoryTransfer;
use App\Services\InventoryTransferService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryTransferApiController extends Controller
{
    use ApiResponse;

    public function __construct(protected InventoryTransferService $transferService) {}

    /**
     * List authorized inventory transfers.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $filters = $request->only(['search', 'source_branch_id', 'destination_branch_id', 'status']);

        // Data scoping
        if (!$user->isSuperAdmin() && !$user->isCompanyAdmin() && $user->branch_id) {
            $filters['branch_id'] = $user->branch_id;
        }

        $transfers = $this->transferService->getPaginatedTransfers($filters, $request->input('per_page', 15));

        return $this->successResponse($transfers, 'Inventory transfers retrieved successfully');
    }

    /**
     * View inventory transfer details.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $transfer = InventoryTransfer::with(['sourceBranch', 'destinationBranch', 'items.product', 'requester', 'approver'])->find($id);

        if (!$transfer) {
            return $this->errorResponse('Inventory transfer not found', 404);
        }

        if (!$user->isSuperAdmin() && !$user->isCompanyAdmin() && $user->branch_id) {
            if ((int)$user->branch_id !== (int)$transfer->source_branch_id && (int)$user->branch_id !== (int)$transfer->destination_branch_id) {
                return $this->errorResponse('Unauthorized access to inventory transfer for another branch', 403);
            }
        }

        return $this->successResponse($transfer, 'Inventory transfer details retrieved');
    }

    /**
     * Create stock transfer request.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->hasRole('Branch Manager') || !$user->can('inventory.transfer.create')) {
            return $this->errorResponse('Branch Managers are not authorized to create stock transfers.', 403);
        }

        $validated = $request->validate([
            'source_branch_id' => 'required|exists:branches,id',
            'destination_branch_id' => 'required|exists:branches,id|different:source_branch_id',
            'remarks' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_cost' => 'nullable|numeric|min:0',
        ]);

        try {
            $transfer = $this->transferService->createTransfer(
                [
                    'source_branch_id' => $validated['source_branch_id'],
                    'destination_branch_id' => $validated['destination_branch_id'],
                    'remarks' => $validated['remarks'] ?? null,
                ],
                $validated['items']
            );

            return $this->successResponse($transfer->load(['sourceBranch', 'destinationBranch', 'items.product']), 'Inventory transfer created successfully', 201);
        } catch (\Throwable $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * Approve inventory transfer.
     */
    public function approve(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        if ($user->hasRole('Branch Manager') || !$user->can('inventory.transfer.approve')) {
            return $this->errorResponse('Unauthorized to approve stock transfers.', 403);
        }

        $transfer = InventoryTransfer::find($id);
        if (!$transfer) {
            return $this->errorResponse('Inventory transfer not found', 404);
        }

        try {
            $this->transferService->approveTransfer($transfer);
            return $this->successResponse($transfer->fresh(['sourceBranch', 'destinationBranch', 'items']), 'Transfer approved successfully');
        } catch (\Throwable $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * Reject inventory transfer.
     */
    public function reject(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        if ($user->hasRole('Branch Manager') || !$user->can('inventory.transfer.reject')) {
            return $this->errorResponse('Unauthorized to reject stock transfers.', 403);
        }

        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:255',
        ]);

        $transfer = InventoryTransfer::find($id);
        if (!$transfer) {
            return $this->errorResponse('Inventory transfer not found', 404);
        }

        try {
            $this->transferService->rejectTransfer($transfer, $validated['rejection_reason']);
            return $this->successResponse($transfer->fresh(), 'Transfer rejected successfully');
        } catch (\Throwable $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * Dispatch inventory transfer.
     */
    public function dispatchTransfer(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        if ($user->hasRole('Branch Manager') || !$user->can('inventory.transfer.dispatch')) {
            return $this->errorResponse('Unauthorized to dispatch stock transfers.', 403);
        }

        $transfer = InventoryTransfer::find($id);
        if (!$transfer) {
            return $this->errorResponse('Inventory transfer not found', 404);
        }

        try {
            $this->transferService->dispatchTransfer($transfer);
            return $this->successResponse($transfer->fresh(), 'Transfer dispatched cleanly');
        } catch (\Throwable $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * Receive / Accept inventory transfer at destination.
     */
    public function receive(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        if (!$user->can('inventory.transfer.receive')) {
            return $this->errorResponse('Unauthorized to receive stock transfers.', 403);
        }

        $transfer = InventoryTransfer::find($id);

        if (!$transfer) {
            return $this->errorResponse('Inventory transfer not found', 404);
        }

        if (!$user->isSuperAdmin() && !$user->isCompanyAdmin()) {
            if ($user->branch_id && (int)$user->branch_id !== (int)$transfer->destination_branch_id) {
                return $this->errorResponse('Unauthorized. You can only receive stock transfers sent to your assigned branch.', 403);
            }
        }

        if ($transfer->status !== 'in_transit') {
            return $this->errorResponse('Transfer is not in transit or has already been received.', 422);
        }

        try {
            $this->transferService->receiveTransfer($transfer);
            return $this->successResponse($transfer->fresh(['sourceBranch', 'destinationBranch', 'items']), 'Transfer received and stock updated successfully');
        } catch (\Throwable $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }
}
