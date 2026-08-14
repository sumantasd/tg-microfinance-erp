<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdjustStockRequest;
use App\Http\Requests\Admin\RestockInventoryRequest;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Product;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function __construct(protected InventoryService $inventoryService) {}

    public function index(Request $request): View
    {
        $filters = $request->only(['search', 'company_id', 'branch_id', 'product_id']);
        $stocks = $this->inventoryService->getPaginatedStock($filters);

        $companies = Company::where('is_active', true)->get();
        $branches = Branch::where('is_active', true)->get();
        $products = Product::where('is_active', true)->get();

        return view('admin.inventory.index', compact('stocks', 'filters', 'companies', 'branches', 'products'));
    }

    public function movements(Request $request): View
    {
        $filters = $request->only(['search', 'company_id', 'branch_id', 'product_id', 'movement_type']);
        $movements = $this->inventoryService->getPaginatedMovements($filters);

        $companies = Company::where('is_active', true)->get();
        $branches = Branch::where('is_active', true)->get();
        $products = Product::where('is_active', true)->get();

        return view('admin.inventory.movements', compact('movements', 'filters', 'companies', 'branches', 'products'));
    }

    public function restock(RestockInventoryRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $movement = $this->inventoryService->restockBranchProduct(
            $data['branch_id'],
            $data['product_id'],
            $data['quantity'],
            $data['unit_price'] ?? null,
            $data['remarks'] ?? null
        );

        return redirect()->back()
            ->with('success', "Restocked {$data['quantity']} units successfully. Movement Code: {$movement->movement_code}");
    }

    public function adjust(AdjustStockRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $movement = $this->inventoryService->adjustBranchStock(
            $data['branch_id'],
            $data['product_id'],
            $data['new_stock_level'],
            $data['remarks']
        );

        return redirect()->back()
            ->with('success', "Stock adjusted successfully. Movement Code: {$movement->movement_code}");
    }
}
