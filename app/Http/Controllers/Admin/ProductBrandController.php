<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductBrandRequest;
use App\Http\Requests\Admin\UpdateProductBrandRequest;
use App\Models\Company;
use App\Models\Product;
use App\Models\ProductBrand;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ProductBrandController extends Controller
{
    public function index(Request $request): View
    {
        $user = Auth::user();
        $query = ProductBrand::with(['company', 'creator'])->withCount('products');

        if ($user && !$user->isSuperAdmin()) {
            $query->where('company_id', $user->company_id);
        }

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', (bool) $request->input('is_active'));
        }

        $brands = $query->orderBy('name', 'asc')->paginate(15)->withQueryString();
        $companies = Company::where('is_active', true)->get();

        return view('admin.products.brands.index', compact('brands', 'companies'));
    }

    public function create(): View
    {
        $companies = Company::where('is_active', true)->get();
        return view('admin.products.brands.create', compact('companies'));
    }

    public function store(StoreProductBrandRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $user = Auth::user();

        if ($user && !$user->isSuperAdmin()) {
            $data['company_id'] = $user->company_id;
        } elseif (empty($data['company_id'])) {
            $data['company_id'] = Company::first()->id ?? 1;
        }

        $data['created_by'] = Auth::id();
        $data['updated_by'] = Auth::id();
        $data['is_active'] = isset($data['is_active']) ? (bool) $data['is_active'] : true;

        $brand = ProductBrand::create($data);

        return redirect()->route('admin.product-brand.index')
            ->with('success', "Brand '{$brand->name}' created successfully.");
    }

    public function edit(ProductBrand $productBrand): View
    {
        $companies = Company::where('is_active', true)->get();
        return view('admin.products.brands.edit', compact('productBrand', 'companies'));
    }

    public function update(UpdateProductBrandRequest $request, ProductBrand $productBrand): RedirectResponse
    {
        $data = $request->validated();
        $data['updated_by'] = Auth::id();
        if (isset($data['is_active'])) {
            $data['is_active'] = (bool) $data['is_active'];
        }

        $productBrand->update($data);

        return redirect()->route('admin.product-brand.index')
            ->with('success', "Brand '{$productBrand->name}' updated successfully.");
    }

    public function destroy(ProductBrand $productBrand): RedirectResponse
    {
        // Safe deletion check: prevent deleting if products are assigned
        $productsCount = Product::where('brand_id', $productBrand->id)
            ->orWhere('brand', $productBrand->name)
            ->count();

        if ($productsCount > 0) {
            return redirect()->route('admin.product-brand.index')
                ->with('error', "Cannot delete brand '{$productBrand->name}' because it is assigned to {$productsCount} product(s). Please reassign or remove those products first.");
        }

        $productBrand->delete();

        return redirect()->route('admin.product-brand.index')
            ->with('success', "Brand '{$productBrand->name}' deleted successfully.");
    }
}
