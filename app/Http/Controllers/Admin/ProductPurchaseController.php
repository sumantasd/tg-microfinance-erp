<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductPurchaseRequest;
use App\Http\Requests\Admin\UpdateProductPurchaseRequest;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Product;
use App\Models\ProductBrand;
use App\Models\ProductCategory;
use App\Models\ProductPurchase;
use App\Models\Supplier;
use App\Services\ProductPurchaseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ProductPurchaseController extends Controller
{
    public function __construct(protected ProductPurchaseService $purchaseService) {}

    public function index(Request $request): View
    {
        $filters = $request->only([
            'search', 'supplier', 'supplier_id', 'branch_id', 'product_id',
            'purchase_status', 'payment_status', 'start_date', 'end_date'
        ]);

        $purchases = $this->purchaseService->getPaginatedPurchases($filters);

        $companies = Company::where('is_active', true)->get();
        $branches = Branch::where('is_active', true)->get();
        $products = Product::where('is_active', true)->get();
        $user = Auth::user();
        $suppliers = Supplier::where('status', 'active')
            ->when($user && !$user->isSuperAdmin(), fn($q) => $q->where('company_id', $user->company_id))
            ->get();

        return view('admin.inventory.purchases.index', compact('purchases', 'filters', 'companies', 'branches', 'products', 'suppliers'));
    }

    public function create(): View
    {
        $companies = Company::where('is_active', true)->get();
        $branches = Branch::where('is_active', true)->get();
        $categories = ProductCategory::where('is_active', true)->orderBy('name')->get();
        $brands = ProductBrand::where('is_active', true)->orderBy('name')->get();
        $products = Product::where('is_active', true)->get();
        $user = Auth::user();
        $suppliers = Supplier::where('status', 'active')
            ->when($user && !$user->isSuperAdmin(), fn($q) => $q->where('company_id', $user->company_id))
            ->get();

        return view('admin.inventory.purchases.create', compact('companies', 'branches', 'categories', 'brands', 'products', 'suppliers'));
    }

    public function store(StoreProductPurchaseRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $purchase = $this->purchaseService->createPurchase($data, $data['items']);

        return redirect()->route('admin.product-purchase.show', $purchase->id)
            ->with('success', "Product Purchase '{$purchase->purchase_number}' created successfully.");
    }

    public function show(ProductPurchase $purchase): View
    {
        $purchase->load(['company', 'branch', 'supplier', 'items.product.categoryRel', 'items.product.brandRel']);
        return view('admin.inventory.purchases.show', compact('purchase'));
    }

    public function edit(ProductPurchase $purchase): View
    {
        if ($purchase->purchase_status !== 'draft') {
            return redirect()->route('admin.product-purchase.show', $purchase->id)
                ->with('error', 'Only draft purchases can be edited.');
        }

        $purchase->load(['items.product.categoryRel', 'items.product.brandRel']);

        $companies = Company::where('is_active', true)->get();
        $branches = Branch::where('is_active', true)->get();
        $categories = ProductCategory::where('is_active', true)->orderBy('name')->get();
        $brands = ProductBrand::where('is_active', true)->orderBy('name')->get();
        $products = Product::where('is_active', true)->get();
        $user = Auth::user();
        $suppliers = Supplier::where('status', 'active')
            ->when($user && !$user->isSuperAdmin(), fn($q) => $q->where('company_id', $user->company_id))
            ->get();

        return view('admin.inventory.purchases.edit', [
            'productPurchase' => $purchase,
            'companies' => $companies,
            'branches' => $branches,
            'categories' => $categories,
            'brands' => $brands,
            'products' => $products,
            'suppliers' => $suppliers,
        ]);
    }

    public function update(UpdateProductPurchaseRequest $request, ProductPurchase $purchase): RedirectResponse
    {
        $data = $request->validated();
        $this->purchaseService->updatePurchase($purchase, $data, $data['items']);

        return redirect()->route('admin.product-purchase.show', $purchase->id)
            ->with('success', "Purchase '{$purchase->purchase_number}' updated successfully.");
    }

    public function confirm(ProductPurchase $purchase): RedirectResponse
    {
        $this->purchaseService->confirmPurchase($purchase);
        return redirect()->back()->with('success', "Purchase '{$purchase->purchase_number}' confirmed.");
    }

    public function receive(ProductPurchase $purchase): RedirectResponse
    {
        $this->purchaseService->receivePurchase($purchase);
        return redirect()->back()->with('success', "Purchase '{$purchase->purchase_number}' received. Branch stock updated cleanly.");
    }

    public function cancel(ProductPurchase $purchase): RedirectResponse
    {
        $this->purchaseService->cancelPurchase($purchase);
        return redirect()->back()->with('success', "Purchase '{$purchase->purchase_number}' cancelled.");
    }
}
