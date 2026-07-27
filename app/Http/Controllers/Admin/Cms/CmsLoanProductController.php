<?php

namespace App\Http\Controllers\Admin\Cms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cms\CmsLoanProductRequest;
use App\Models\CmsLoanProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CmsLoanProductController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('website.manage');

        $query = CmsLoanProduct::query();

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

        return view('admin.cms.loan-products.index', compact('products'));
    }

    public function create()
    {
        $this->authorize('website.manage');

        return view('admin.cms.loan-products.create');
    }

    public function store(CmsLoanProductRequest $request)
    {
        $this->authorize('website.manage');

        $data = $request->validated();

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        } else {
            $data['slug'] = Str::slug($data['slug']);
        }

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('cms/loan-products', 'public');
        }

        CmsLoanProduct::create($data);

        return redirect()->route('admin.cms.loan-products.index')->with('success', 'Loan product created successfully.');
    }

    public function edit(CmsLoanProduct $loan_product)
    {
        $this->authorize('website.manage');

        return view('admin.cms.loan-products.edit', ['product' => $loan_product]);
    }

    public function update(CmsLoanProductRequest $request, CmsLoanProduct $loan_product)
    {
        $this->authorize('website.manage');

        $data = $request->validated();

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        } else {
            $data['slug'] = Str::slug($data['slug']);
        }

        if ($request->hasFile('image')) {
            if ($loan_product->image && Storage::disk('public')->exists($loan_product->image)) {
                Storage::disk('public')->delete($loan_product->image);
            }
            $data['image'] = $request->file('image')->store('cms/loan-products', 'public');
        }

        $loan_product->update($data);

        return redirect()->route('admin.cms.loan-products.index')->with('success', 'Loan product updated successfully.');
    }

    public function toggleStatus(CmsLoanProduct $loan_product)
    {
        $this->authorize('website.manage');

        $newStatus = $loan_product->status === 'active' ? 'inactive' : 'active';
        $loan_product->update(['status' => $newStatus]);

        return back()->with('success', 'Loan product status updated to ' . strtoupper($newStatus));
    }

    public function destroy(CmsLoanProduct $loan_product)
    {
        $this->authorize('website.manage');

        if ($loan_product->image && Storage::disk('public')->exists($loan_product->image)) {
            Storage::disk('public')->delete($loan_product->image);
        }

        $loan_product->delete();

        return redirect()->route('admin.cms.loan-products.index')->with('success', 'Loan product deleted successfully.');
    }
}
