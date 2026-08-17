<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductCategoryRequest;
use App\Http\Requests\Admin\UpdateProductCategoryRequest;
use App\Models\Company;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ProductCategoryController extends Controller
{
    public function index(Request $request): View
    {
        $user = Auth::user();
        $query = ProductCategory::with(['company', 'creator'])->withCount('products');

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

        $categories = $query->orderBy('name', 'asc')->paginate(15)->withQueryString();
        $companies = Company::where('is_active', true)->get();

        return view('admin.products.categories.index', compact('categories', 'companies'));
    }

    public function create(): View
    {
        $companies = Company::where('is_active', true)->get();
        return view('admin.products.categories.create', compact('companies'));
    }

    public function store(StoreProductCategoryRequest $request): RedirectResponse
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

        $category = ProductCategory::create($data);

        return redirect()->route('admin.product-category.index')
            ->with('success', "Category '{$category->name}' created successfully.");
    }

    public function edit(ProductCategory $productCategory): View
    {
        $companies = Company::where('is_active', true)->get();
        return view('admin.products.categories.edit', compact('productCategory', 'companies'));
    }

    public function update(UpdateProductCategoryRequest $request, ProductCategory $productCategory): RedirectResponse
    {
        $data = $request->validated();
        $data['updated_by'] = Auth::id();
        if (isset($data['is_active'])) {
            $data['is_active'] = (bool) $data['is_active'];
        }

        $productCategory->update($data);

        return redirect()->route('admin.product-category.index')
            ->with('success', "Category '{$productCategory->name}' updated successfully.");
    }

    public function destroy(ProductCategory $productCategory): RedirectResponse
    {
        // Safe deletion check: prevent deleting if products are assigned
        $productsCount = Product::where('category_id', $productCategory->id)
            ->orWhere('category', $productCategory->name)
            ->count();

        if ($productsCount > 0) {
            return redirect()->route('admin.product-category.index')
                ->with('error', "Cannot delete category '{$productCategory->name}' because it is assigned to {$productsCount} product(s). Please reassign or remove those products first.");
        }

        $productCategory->delete();

        return redirect()->route('admin.product-category.index')
            ->with('success', "Category '{$productCategory->name}' deleted successfully.");
    }
}
