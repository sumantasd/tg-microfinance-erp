<?php

namespace App\Http\Controllers\Admin\Cms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cms\PageRequest;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PageController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('website.manage');

        $query = Page::query();

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('slug', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $pages = $query->latest()->paginate(10);

        return view('admin.cms.pages.index', compact('pages'));
    }

    public function create()
    {
        $this->authorize('website.manage');

        return view('admin.cms.pages.create');
    }

    public function store(PageRequest $request)
    {
        $this->authorize('website.manage');

        $data = $request->validated();

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['title']);
        } else {
            $data['slug'] = Str::slug($data['slug']);
        }

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('cms/pages', 'public');
        }

        Page::create($data);

        return redirect()->route('admin.cms.pages.index')->with('success', 'CMS page created successfully.');
    }

    public function edit(Page $page)
    {
        $this->authorize('website.manage');

        return view('admin.cms.pages.edit', compact('page'));
    }

    public function update(PageRequest $request, Page $page)
    {
        $this->authorize('website.manage');

        $data = $request->validated();

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['title']);
        } else {
            $data['slug'] = Str::slug($data['slug']);
        }

        if ($request->hasFile('image')) {
            if ($page->image && Storage::disk('public')->exists($page->image)) {
                Storage::disk('public')->delete($page->image);
            }
            $data['image'] = $request->file('image')->store('cms/pages', 'public');
        }

        $page->update($data);

        return redirect()->route('admin.cms.pages.index')->with('success', 'CMS page updated successfully.');
    }

    public function toggleStatus(Page $page)
    {
        $this->authorize('website.manage');

        $newStatus = $page->status === 'published' ? 'draft' : 'published';
        $page->update(['status' => $newStatus]);

        return back()->with('success', 'Page status updated to ' . strtoupper($newStatus));
    }

    public function destroy(Page $page)
    {
        $this->authorize('website.manage');

        if ($page->image && Storage::disk('public')->exists($page->image)) {
            Storage::disk('public')->delete($page->image);
        }

        $page->delete();

        return redirect()->route('admin.cms.pages.index')->with('success', 'CMS page deleted successfully.');
    }
}
