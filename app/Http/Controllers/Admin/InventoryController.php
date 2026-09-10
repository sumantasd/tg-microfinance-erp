<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdjustStockRequest;
use App\Http\Requests\Admin\RestockInventoryRequest;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Product;
use App\Models\ProductBrand;
use App\Models\ProductCategory;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function __construct(protected InventoryService $inventoryService) {}

    public function index(Request $request): View|RedirectResponse
    {
        $user = auth()->user();

        // 1. Retrieve accessible retail branches for the logged-in user according to RBAC
        $branchQuery = Branch::where('is_active', true)->where('is_warehouse', false);
        if ($user && !$user->isSuperAdmin()) {
            $branchQuery->where('company_id', $user->company_id);
            if (!$user->isCompanyAdmin() && $user->branch_id) {
                $branchQuery->where('id', $user->branch_id);
            }
        }
        $branches = $branchQuery->orderBy('name')->get();

        $selectedBranch = null;
        $stocks = null;
        $filters = $request->only(['search', 'company_id', 'branch_id', 'product_id', 'stock_status']);

        // 2. Check if a specific branch is selected
        if ($request->filled('branch_id')) {
            $requestedBranchId = (int) $request->get('branch_id');

            // Server-side authorization check: user must have access to the requested branch
            if (!$user || !$user->canAccessBranch($requestedBranchId) || !$branches->contains('id', $requestedBranchId)) {
                return redirect()->route('admin.inventory.index')
                    ->with('error', 'You are not authorized to view inventory for the requested branch.');
            }

            $selectedBranch = $branches->firstWhere('id', $requestedBranchId) ?? Branch::find($requestedBranchId);
            $filters['branch_id'] = $selectedBranch->id;

            // Fetch inventory stock scoped exclusively to the selected branch
            $stocks = $this->inventoryService->getPaginatedStock($filters);
        }

        // 3. For Step 1 (Branch List View), calculate summary stats for accessible branches
        $branchStats = collect();
        if (!$selectedBranch && $branches->isNotEmpty()) {
            $statsData = DB::table('inventory_stocks')
                ->select(
                    'branch_id',
                    DB::raw('COUNT(DISTINCT product_id) as products_count'),
                    DB::raw('SUM(CASE WHEN current_stock > reserved_stock THEN (current_stock - reserved_stock) ELSE 0 END) as total_available_units'),
                    DB::raw('SUM(CASE WHEN current_stock <= 0 THEN 1 ELSE 0 END) as out_of_stock_count'),
                    DB::raw('SUM(CASE WHEN current_stock > 0 AND current_stock <= reorder_level THEN 1 ELSE 0 END) as low_stock_count')
                )
                ->whereIn('branch_id', $branches->pluck('id'))
                ->groupBy('branch_id')
                ->get();

            $branchStats = $statsData->keyBy('branch_id');
        }

        $companies = Company::where('is_active', true)->get();
        $products = Product::where('is_active', true)->orderBy('name')->get();
        $categories = ProductCategory::where('is_active', true)->orderBy('name')->get();
        $brands = ProductBrand::where('is_active', true)->orderBy('name')->get();

        return view('admin.inventory.index', compact(
            'branches',
            'selectedBranch',
            'stocks',
            'branchStats',
            'filters',
            'companies',
            'products',
            'categories',
            'brands'
        ));
    }

    public function getBrandsByCategory(Request $request): JsonResponse
    {
        $categoryId = (int) $request->get('category_id');
        $user = auth()->user();

        $query = ProductBrand::where('is_active', true);
        if ($user && !$user->isSuperAdmin() && $user->company_id) {
            $query->where('company_id', $user->company_id);
        }

        $brands = (clone $query)->whereHas('products', function ($q) use ($categoryId) {
            $q->where('category_id', $categoryId)->where('is_active', true);
        })->orderBy('name')->get(['id', 'name', 'code']);

        if ($brands->isEmpty()) {
            $brands = $query->orderBy('name')->get(['id', 'name', 'code']);
        }

        return response()->json($brands);
    }

    public function getProductsByBrand(Request $request): JsonResponse
    {
        $categoryId = (int) $request->get('category_id');
        $brandId = (int) $request->get('brand_id');
        $user = auth()->user();

        if (!$categoryId || !$brandId) {
            return response()->json([]);
        }

        $query = Product::where('is_active', true)
            ->where('category_id', $categoryId)
            ->where('brand_id', $brandId);

        if ($user && !$user->isSuperAdmin() && $user->company_id) {
            $query->where('company_id', $user->company_id);
        }

        $products = $query->orderBy('name')->get(['id', 'name', 'sku', 'unit_price', 'cost_price']);

        return response()->json($products);
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
