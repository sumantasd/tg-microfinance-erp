<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreInventoryTransferRequest;
use App\Models\Branch;
use App\Models\Company;
use App\Models\InventoryTransfer;
use App\Models\Product;
use App\Services\InventoryTransferService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryTransferController extends Controller
{
    public function __construct(protected InventoryTransferService $transferService) {}

    public function index(Request $request): View
    {
        $filters = $request->only(['search', 'source_branch_id', 'destination_branch_id', 'status']);
        $transfers = $this->transferService->getPaginatedTransfers($filters);

        $companies = Company::where('is_active', true)->get();
        $branches = Branch::where('is_active', true)->get();

        return view('admin.inventory.transfers.index', compact('transfers', 'filters', 'companies', 'branches'));
    }

    public function create(): View
    {
        $companies = Company::where('is_active', true)->get();
        $branches = Branch::where('is_active', true)->get();
        $products = Product::where('is_active', true)->get();

        return view('admin.inventory.transfers.create', compact('companies', 'branches', 'products'));
    }

    public function store(StoreInventoryTransferRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $transfer = $this->transferService->createTransfer(
            [
                'source_branch_id' => $data['source_branch_id'],
                'destination_branch_id' => $data['destination_branch_id'],
                'remarks' => $data['remarks'] ?? null,
            ],
            $data['items']
        );

        return redirect()->route('admin.inventory-transfer.show', $transfer->id)
            ->with('success', "Inventory Transfer '{$transfer->transfer_number}' created successfully.");
    }

    public function show(InventoryTransfer $inventoryTransfer): View
    {
        return view('admin.inventory.transfers.show', ['transfer' => $inventoryTransfer]);
    }

    public function requestTransfer(InventoryTransfer $inventoryTransfer): RedirectResponse
    {
        $this->transferService->requestTransfer($inventoryTransfer);
        return redirect()->back()->with('success', "Transfer '{$inventoryTransfer->transfer_number}' requested for approval.");
    }

    public function approve(InventoryTransfer $inventoryTransfer): RedirectResponse
    {
        $this->transferService->approveTransfer($inventoryTransfer);
        return redirect()->back()->with('success', "Transfer '{$inventoryTransfer->transfer_number}' approved successfully.");
    }

    public function reject(Request $request, InventoryTransfer $inventoryTransfer): RedirectResponse
    {
        $request->validate(['rejection_reason' => 'required|string|max:255']);
        $this->transferService->rejectTransfer($inventoryTransfer, $request->input('rejection_reason'));
        return redirect()->back()->with('success', "Transfer '{$inventoryTransfer->transfer_number}' rejected.");
    }

    public function dispatchTransfer(InventoryTransfer $inventoryTransfer): RedirectResponse
    {
        $this->transferService->dispatchTransfer($inventoryTransfer);
        return redirect()->back()->with('success', "Transfer '{$inventoryTransfer->transfer_number}' dispatched cleanly. Source stock deducted.");
    }

    public function receive(InventoryTransfer $inventoryTransfer): RedirectResponse
    {
        $this->transferService->receiveTransfer($inventoryTransfer);
        return redirect()->back()->with('success', "Transfer '{$inventoryTransfer->transfer_number}' received at destination branch. Stock updated.");
    }

    public function cancel(InventoryTransfer $inventoryTransfer): RedirectResponse
    {
        $this->transferService->cancelTransfer($inventoryTransfer);
        return redirect()->back()->with('success', "Transfer '{$inventoryTransfer->transfer_number}' cancelled.");
    }
}
