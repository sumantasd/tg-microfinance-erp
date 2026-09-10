<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdjustStockRequest;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Product;
use App\Models\ProductBrand;
use App\Models\ProductCategory;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class WarehouseController extends Controller
{
    public function __construct(protected InventoryService $inventoryService) {}

    public function index(Request $request): View
    {
        $user = auth()->user();
        $companyId = $user?->company_id ?? 1;

        // Retrieve or initialize the Central Warehouse location for the company
        $centralWarehouse = Branch::getCentralWarehouse($companyId);

        $filters = $request->only(['search', 'product_id', 'stock_status']);
        $filters['branch_id'] = $centralWarehouse->id;

        // Retrieve paginated stock exclusively for Central Warehouse
        $stocks = $this->inventoryService->getPaginatedStock($filters);

        // Aggregate summary stats for Central Warehouse
        $statsData = DB::table('inventory_stocks')
            ->select(
                DB::raw('COUNT(DISTINCT product_id) as total_products'),
                DB::raw('SUM(CASE WHEN current_stock > reserved_stock THEN (current_stock - reserved_stock) ELSE 0 END) as total_available_units'),
                DB::raw('SUM(current_stock) as total_current_units'),
                DB::raw('SUM(CASE WHEN current_stock <= 0 THEN 1 ELSE 0 END) as out_of_stock_count'),
                DB::raw('SUM(CASE WHEN current_stock > 0 AND current_stock <= reorder_level THEN 1 ELSE 0 END) as low_stock_count')
            )
            ->where('branch_id', $centralWarehouse->id)
            ->first();

        // Accessible retail branches for stock transfer destinations
        $retailBranches = Branch::where('is_active', true)
            ->where('is_warehouse', false)
            ->when($user && !$user->isSuperAdmin(), fn($q) => $q->where('company_id', $user->company_id))
            ->orderBy('name')
            ->get();

        $products = Product::where('is_active', true)->orderBy('name')->get();
        $categories = ProductCategory::where('is_active', true)->orderBy('name')->get();
        $brands = ProductBrand::where('is_active', true)->orderBy('name')->get();

        return view('admin.warehouse.index', compact(
            'centralWarehouse',
            'stocks',
            'statsData',
            'filters',
            'retailBranches',
            'products',
            'categories',
            'brands'
        ));
    }

    public function adjust(AdjustStockRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $user = auth()->user();
        $companyId = $user?->company_id ?? 1;
        $centralWarehouse = Branch::getCentralWarehouse($companyId);

        // Force target branch to Central Warehouse
        $data['branch_id'] = $centralWarehouse->id;

        $movement = $this->inventoryService->adjustBranchStock(
            $data['branch_id'],
            $data['product_id'],
            $data['new_stock_level'],
            $data['remarks']
        );

        return redirect()->back()
            ->with('success', "Warehouse stock adjusted successfully. Movement Code: {$movement->movement_code}");
    }
}
