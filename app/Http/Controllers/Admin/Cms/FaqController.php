<?php

namespace App\Http\Controllers\Admin\Cms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cms\FaqRequest;
use App\Models\Faq;
use Illuminate\Http\Request;

class FaqController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('website.manage');

        $query = Faq::query();

        if ($request->filled('search')) {
            $query->where('question', 'like', '%' . $request->search . '%')
                  ->orWhere('answer', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $faqs = $query->orderBy('sort_order', 'asc')->orderBy('id', 'desc')->paginate(10);

        return view('admin.cms.faqs.index', compact('faqs'));
    }

    public function create()
    {
        $this->authorize('website.manage');

        return view('admin.cms.faqs.create');
    }

    public function store(FaqRequest $request)
    {
        $this->authorize('website.manage');

        Faq::create($request->validated());

        return redirect()->route('admin.cms.faq.index')->with('success', 'FAQ item created successfully.');
    }

    public function edit(Faq $faq)
    {
        $this->authorize('website.manage');

        return view('admin.cms.faqs.edit', compact('faq'));
    }

    public function update(FaqRequest $request, Faq $faq)
    {
        $this->authorize('website.manage');

        $faq->update($request->validated());

        return redirect()->route('admin.cms.faq.index')->with('success', 'FAQ item updated successfully.');
    }

    public function toggleStatus(Faq $faq)
    {
        $this->authorize('website.manage');

        $newStatus = $faq->status === 'active' ? 'inactive' : 'active';
        $faq->update(['status' => $newStatus]);

        return back()->with('success', 'FAQ status updated to ' . strtoupper($newStatus));
    }

    public function destroy(Faq $faq)
    {
        $this->authorize('website.manage');

        $faq->delete();

        return redirect()->route('admin.cms.faq.index')->with('success', 'FAQ item deleted successfully.');
    }
}
