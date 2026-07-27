<?php

namespace App\Http\Controllers\Admin\Cms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cms\CmsSavingsProductRequest;
use App\Models\CmsSavingsProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CmsSavingsProductController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('website.manage');

        $query = CmsSavingsProduct::query();

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $products = $query->orderBy('sort_order', 'asc')->orderBy('id', 'desc')->paginate(10);

        return view('admin.cms.savings-products.index', compact('products'));
    }

    public function create()
    {
        $this->authorize('website.manage');

        return view('admin.cms.savings-products.create');
    }

    public function store(CmsSavingsProductRequest $request)
    {
        $this->authorize('website.manage');

        $data = $request->validated();

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        } else {
            $data['slug'] = Str::slug($data['slug']);
        }

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('cms/savings-products', 'public');
        }

        CmsSavingsProduct::create($data);

        return redirect()->route('admin.cms.savings-products.index')->with('success', 'Savings product created successfully.');
    }

    public function edit(CmsSavingsProduct $savings_product)
    {
        $this->authorize('website.manage');

        return view('admin.cms.savings-products.edit', ['product' => $savings_product]);
    }

    public function update(CmsSavingsProductRequest $request, CmsSavingsProduct $savings_product)
    {
        $this->authorize('website.manage');

        $data = $request->validated();

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        } else {
            $data['slug'] = Str::slug($data['slug']);
        }

        if ($request->hasFile('image')) {
            if ($savings_product->image && Storage::disk('public')->exists($savings_product->image)) {
                Storage::disk('public')->delete($savings_product->image);
            }
            $data['image'] = $request->file('image')->store('cms/savings-products', 'public');
        }

        $savings_product->update($data);

        return redirect()->route('admin.cms.savings-products.index')->with('success', 'Savings product updated successfully.');
    }

    public function toggleStatus(CmsSavingsProduct $savings_product)
    {
        $this->authorize('website.manage');

        $newStatus = $savings_product->status === 'active' ? 'inactive' : 'active';
        $savings_product->update(['status' => $newStatus]);

        return back()->with('success', 'Savings product status updated to ' . strtoupper($newStatus));
    }

    public function destroy(CmsSavingsProduct $savings_product)
    {
        $this->authorize('website.manage');

        if ($savings_product->image && Storage::disk('public')->exists($savings_product->image)) {
            Storage::disk('public')->delete($savings_product->image);
        }

        $savings_product->delete();

        return redirect()->route('admin.cms.savings-products.index')->with('success', 'Savings product deleted successfully.');
    }
}
