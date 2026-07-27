<?php

namespace App\Http\Controllers\Admin\Cms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cms\WhyChooseUsRequest;
use App\Models\WhyChooseUs;
use Illuminate\Http\Request;

class WhyChooseUsController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('website.manage');

        $query = WhyChooseUs::query();

        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $items = $query->orderBy('sort_order', 'asc')->orderBy('id', 'desc')->paginate(10);

        return view('admin.cms.why-choose-us.index', compact('items'));
    }

    public function create()
    {
        $this->authorize('website.manage');

        return view('admin.cms.why-choose-us.create');
    }

    public function store(WhyChooseUsRequest $request)
    {
        $this->authorize('website.manage');

        WhyChooseUs::create($request->validated());

        return redirect()->route('admin.cms.why-choose-us.index')->with('success', 'Why Choose Us card created successfully.');
    }

    public function edit(WhyChooseUs $why_choose_u)
    {
        $this->authorize('website.manage');

        return view('admin.cms.why-choose-us.edit', ['item' => $why_choose_u]);
    }

    public function update(WhyChooseUsRequest $request, WhyChooseUs $why_choose_u)
    {
        $this->authorize('website.manage');

        $why_choose_u->update($request->validated());

        return redirect()->route('admin.cms.why-choose-us.index')->with('success', 'Why Choose Us card updated successfully.');
    }

    public function toggleStatus(WhyChooseUs $why_choose_u)
    {
        $this->authorize('website.manage');

        $newStatus = $why_choose_u->status === 'active' ? 'inactive' : 'active';
        $why_choose_u->update(['status' => $newStatus]);

        return back()->with('success', 'Status updated to ' . strtoupper($newStatus));
    }

    public function destroy(WhyChooseUs $why_choose_u)
    {
        $this->authorize('website.manage');

        $why_choose_u->delete();

        return redirect()->route('admin.cms.why-choose-us.index')->with('success', 'Why Choose Us card deleted successfully.');
    }
}
