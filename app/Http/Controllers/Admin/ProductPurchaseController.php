<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductPurchaseRequest;
use App\Http\Requests\Admin\UpdateProductPurchaseRequest;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Product;
use App\Models\ProductPurchase;
use App\Services\ProductPurchaseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductPurchaseController extends Controller
{
    public function __construct(protected ProductPurchaseService $purchaseService) {}

    public function index(Request $request): View
    {
        $filters = $request->only([
            'search', 'supplier', 'branch_id', 'product_id',
            'purchase_status', 'payment_status', 'start_date', 'end_date'
        ]);

        $purchases = $this->purchaseService->getPaginatedPurchases($filters);

        $companies = Company::where('is_active', true)->get();
        $branches = Branch::where('is_active', true)->get();
        $products = Product::where('is_active', true)->get();

        return view('admin.inventory.purchases.index', compact('purchases', 'filters', 'companies', 'branches', 'products'));
    }

    public function create(): View
    {
        $companies = Company::where('is_active', true)->get();
        $branches = Branch::where('is_active', true)->get();
        $products = Product::where('is_active', true)->get();

        return view('admin.inventory.purchases.create', compact('companies', 'branches', 'products'));
    }

    public function store(StoreProductPurchaseRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $purchase = $this->purchaseService->createPurchase($data, $data['items']);

        return redirect()->route('admin.product-purchase.show', $purchase->id)
            ->with('success', "Product Purchase '{$purchase->purchase_number}' created successfully.");
    }

    public function show(ProductPurchase $productPurchase): View
    {
        return view('admin.inventory.purchases.show', ['purchase' => $productPurchase]);
    }

    public function edit(ProductPurchase $productPurchase): View
    {
        if ($productPurchase->purchase_status !== 'draft') {
            return redirect()->route('admin.product-purchase.show', $productPurchase->id)
                ->with('error', 'Only draft purchases can be edited.');
        }

        $companies = Company::where('is_active', true)->get();
        $branches = Branch::where('is_active', true)->get();
        $products = Product::where('is_active', true)->get();

        return view('admin.inventory.purchases.edit', compact('productPurchase', 'companies', 'branches', 'products'));
    }

    public function update(UpdateProductPurchaseRequest $request, ProductPurchase $productPurchase): RedirectResponse
    {
        $data = $request->validated();
        $this->purchaseService->updatePurchase($productPurchase, $data, $data['items']);

        return redirect()->route('admin.product-purchase.show', $productPurchase->id)
            ->with('success', "Purchase '{$productPurchase->purchase_number}' updated successfully.");
    }

    public function confirm(ProductPurchase $productPurchase): RedirectResponse
    {
        $this->purchaseService->confirmPurchase($productPurchase);
        return redirect()->back()->with('success', "Purchase '{$productPurchase->purchase_number}' confirmed.");
    }

    public function receive(ProductPurchase $productPurchase): RedirectResponse
    {
        $this->purchaseService->receivePurchase($productPurchase);
        return redirect()->back()->with('success', "Purchase '{$productPurchase->purchase_number}' received. Branch stock updated cleanly.");
    }

    public function cancel(ProductPurchase $productPurchase): RedirectResponse
    {
        $this->purchaseService->cancelPurchase($productPurchase);
        return redirect()->back()->with('success', "Purchase '{$productPurchase->purchase_number}' cancelled.");
    }
}
