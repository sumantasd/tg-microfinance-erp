<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Requests\Admin\UpdateProductRequest;
use App\Models\Company;
use App\Models\Product;
use App\Models\ProductBrand;
use App\Models\ProductCategory;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(protected InventoryService $inventoryService) {}

    public function index(Request $request): View
    {
        if (auth()->user()?->hasRole('Branch Manager')) {
            abort(403, 'Branch Managers are not authorized to view the product catalog.');
        }

        $filters = $request->only(['search', 'company_id', 'category', 'category_id', 'brand_id', 'is_active']);
        $products = $this->inventoryService->getPaginatedProducts($filters);
        $companies = Company::where('is_active', true)->get();
        $categories = ProductCategory::where('is_active', true)->orderBy('name')->get();
        $brands = ProductBrand::where('is_active', true)->orderBy('name')->get();

        return view('admin.products.index', compact('products', 'filters', 'companies', 'categories', 'brands'));
    }

    public function create(): View
    {
        if (auth()->user()->hasRole('Branch Manager') || !auth()->user()->can('product.create')) {
            abort(403, 'Branch Managers are not authorized to add new products.');
        }

        $companies = Company::where('is_active', true)->get();
        $categories = ProductCategory::where('is_active', true)->orderBy('name')->get();
        $brands = ProductBrand::where('is_active', true)->orderBy('name')->get();

        return view('admin.products.create', compact('companies', 'categories', 'brands'));
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        if ($request->user()->hasRole('Branch Manager') || !$request->user()->can('product.create')) {
            abort(403, 'Branch Managers are not authorized to add new products.');
        }

        $product = $this->inventoryService->createProduct($request->validated());

        return redirect()->route('admin.product.index')
            ->with('success', "Product '{$product->name}' (SKU: {$product->sku}) added to catalog successfully.");
    }

    public function show(Product $product): View
    {
        if (auth()->user()?->hasRole('Branch Manager') || !auth()->user()?->can('product.view')) {
            abort(403, 'Branch Managers are not authorized to view product details.');
        }

        return view('admin.products.show', compact('product'));
    }

    public function edit(Product $product): View
    {
        if (auth()->user()->hasRole('Branch Manager') || !auth()->user()->can('product.edit')) {
            abort(403, 'Branch Managers are not authorized to edit products.');
        }

        $companies = Company::where('is_active', true)->get();
        $categories = ProductCategory::where('is_active', true)->orderBy('name')->get();
        $brands = ProductBrand::where('is_active', true)->orderBy('name')->get();

        return view('admin.products.edit', compact('product', 'companies', 'categories', 'brands'));
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        if ($request->user()->hasRole('Branch Manager') || !$request->user()->can('product.edit')) {
            abort(403, 'Branch Managers are not authorized to update products.');
        }

        $updatedProduct = $this->inventoryService->updateProduct($product, $request->validated());

        return redirect()->route('admin.product.index')
            ->with('success', "Product '{$updatedProduct->name}' updated successfully.");
    }

    public function destroy(Product $product): RedirectResponse
    {
        if (auth()->user()->hasRole('Branch Manager') || !auth()->user()->can('product.delete')) {
            abort(403, 'Branch Managers are not authorized to delete products.');
        }

        $this->inventoryService->deleteProduct($product);

        return redirect()->route('admin.product.index')
            ->with('success', "Product '{$product->name}' removed from catalog.");
    }
}
