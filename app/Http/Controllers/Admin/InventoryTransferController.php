<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreInventoryTransferRequest;
use App\Models\Branch;
use App\Models\Company;
use App\Models\InventoryTransfer;
use App\Models\Product;
use App\Models\ProductBrand;
use App\Models\ProductCategory;
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
        $user = auth()->user();
        if ($user->hasRole('Branch Manager') || !$user->can('inventory.transfer.create')) {
            abort(403, 'Branch Managers are not authorized to create stock transfers.');
        }

        $companies = Company::where('is_active', true)->get();
        $branches = Branch::where('is_active', true)->get();
        $centralWarehouse = Branch::getCentralWarehouse($user->company_id ?? 1);
        $products = Product::where('is_active', true)->get();
        $categories = ProductCategory::where('is_active', true)->orderBy('name')->get();
        $brands = ProductBrand::where('is_active', true)->orderBy('name')->get();

        return view('admin.inventory.transfers.create', compact('companies', 'branches', 'centralWarehouse', 'products', 'categories', 'brands'));
    }

    public function store(StoreInventoryTransferRequest $request): RedirectResponse
    {
        $user = auth()->user();
        if ($user->hasRole('Branch Manager') || !$user->can('inventory.transfer.create')) {
            abort(403, 'Branch Managers are not authorized to create stock transfers.');
        }

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
        $user = auth()->user();
        if ($user && $user->branch_id && !in_array((int)$user->branch_id, [(int)$inventoryTransfer->source_branch_id, (int)$inventoryTransfer->destination_branch_id])) {
            abort(403, 'Unauthorized access to stock transfer record belonging to another branch.');
        }

        return view('admin.inventory.transfers.show', ['transfer' => $inventoryTransfer]);
    }

    public function requestTransfer(InventoryTransfer $inventoryTransfer): RedirectResponse
    {
        if (auth()->user()->hasRole('Branch Manager')) {
            abort(403, 'Branch Managers cannot request stock transfer approvals.');
        }

        $this->transferService->requestTransfer($inventoryTransfer);
        return redirect()->back()->with('success', "Transfer '{$inventoryTransfer->transfer_number}' requested for approval.");
    }

    public function approve(InventoryTransfer $inventoryTransfer): RedirectResponse
    {
        if (auth()->user()->hasRole('Branch Manager')) {
            abort(403, 'Branch Managers cannot approve stock transfers.');
        }

        $this->transferService->approveTransfer($inventoryTransfer);
        return redirect()->back()->with('success', "Transfer '{$inventoryTransfer->transfer_number}' approved successfully.");
    }

    public function reject(Request $request, InventoryTransfer $inventoryTransfer): RedirectResponse
    {
        if (auth()->user()->hasRole('Branch Manager')) {
            abort(403, 'Branch Managers cannot reject stock transfers.');
        }

        $request->validate(['rejection_reason' => 'required|string|max:255']);
        $this->transferService->rejectTransfer($inventoryTransfer, $request->input('rejection_reason'));
        return redirect()->back()->with('success', "Transfer '{$inventoryTransfer->transfer_number}' rejected.");
    }

    public function dispatchTransfer(InventoryTransfer $inventoryTransfer): RedirectResponse
    {
        if (auth()->user()->hasRole('Branch Manager')) {
            abort(403, 'Branch Managers cannot dispatch stock transfers.');
        }

        $this->transferService->dispatchTransfer($inventoryTransfer);
        return redirect()->back()->with('success', "Transfer '{$inventoryTransfer->transfer_number}' dispatched cleanly. Source stock deducted.");
    }

    public function receive(InventoryTransfer $inventoryTransfer): RedirectResponse
    {
        $user = auth()->user();
        if (!$user->isSuperAdmin() && !$user->isCompanyAdmin()) {
            if ($user->branch_id && (int)$user->branch_id !== (int)$inventoryTransfer->destination_branch_id) {
                abort(403, 'Unauthorized. You can only accept stock transfers sent to your assigned branch.');
            }
        }

        if ($inventoryTransfer->status !== 'in_transit') {
            abort(403, 'Transfer is not in transit or has already been received.');
        }

        $this->transferService->receiveTransfer($inventoryTransfer);
        return redirect()->back()->with('success', "Transfer '{$inventoryTransfer->transfer_number}' received at destination branch. Stock updated.");
    }

    public function cancel(InventoryTransfer $inventoryTransfer): RedirectResponse
    {
        if (auth()->user()->hasRole('Branch Manager')) {
            abort(403, 'Branch Managers cannot cancel stock transfers.');
        }

        $this->transferService->cancelTransfer($inventoryTransfer);
        return redirect()->back()->with('success', "Transfer '{$inventoryTransfer->transfer_number}' cancelled.");
    }
}
