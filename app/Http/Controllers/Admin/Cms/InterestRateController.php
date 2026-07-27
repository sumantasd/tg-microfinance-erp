<?php

namespace App\Http\Controllers\Admin\Cms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cms\InterestRateRequest;
use App\Models\InterestRate;
use Illuminate\Http\Request;

class InterestRateController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('website.manage');

        $query = InterestRate::query();

        if ($request->filled('search')) {
            $query->where('product_name', 'like', '%' . $request->search . '%')
                  ->orWhere('interest_rate', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('product_type')) {
            $query->where('product_type', $request->product_type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $rates = $query->orderBy('sort_order', 'asc')->orderBy('id', 'desc')->paginate(10);

        return view('admin.cms.interest-rates.index', compact('rates'));
    }

    public function create()
    {
        $this->authorize('website.manage');

        return view('admin.cms.interest-rates.create');
    }

    public function store(InterestRateRequest $request)
    {
        $this->authorize('website.manage');

        InterestRate::create($request->validated());

        return redirect()->route('admin.cms.interest-rates.index')->with('success', 'Interest rate schedule entry created successfully.');
    }

    public function edit(InterestRate $interest_rate)
    {
        $this->authorize('website.manage');

        return view('admin.cms.interest-rates.edit', ['rate' => $interest_rate]);
    }

    public function update(InterestRateRequest $request, InterestRate $interest_rate)
    {
        $this->authorize('website.manage');

        $interest_rate->update($request->validated());

        return redirect()->route('admin.cms.interest-rates.index')->with('success', 'Interest rate schedule entry updated successfully.');
    }

    public function toggleStatus(InterestRate $interest_rate)
    {
        $this->authorize('website.manage');

        $newStatus = $interest_rate->status === 'active' ? 'inactive' : 'active';
        $interest_rate->update(['status' => $newStatus]);

        return back()->with('success', 'Status updated to ' . strtoupper($newStatus));
    }

    public function destroy(InterestRate $interest_rate)
    {
        $this->authorize('website.manage');

        $interest_rate->delete();

        return redirect()->route('admin.cms.interest-rates.index')->with('success', 'Interest rate schedule entry deleted successfully.');
    }
}
