<?php

namespace App\Http\Controllers\Admin\Cms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cms\HomepageSectionRequest;
use App\Models\HomepageSection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class HomepageSectionController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('website.manage');

        $query = HomepageSection::query();

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('section_key', 'like', '%' . $request->search . '%')
                  ->orWhere('subtitle', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $sections = $query->orderBy('sort_order', 'asc')->orderBy('id', 'desc')->paginate(10);

        return view('admin.cms.homepage.index', compact('sections'));
    }

    public function create()
    {
        $this->authorize('website.manage');

        return view('admin.cms.homepage.create');
    }

    public function store(HomepageSectionRequest $request)
    {
        $this->authorize('website.manage');

        $data = $request->validated();

        if (isset($data['governance_bullets']) && is_array($data['governance_bullets'])) {
            $data['governance_bullets'] = array_values(array_filter($data['governance_bullets'], function ($item) {
                return !is_null($item) && trim($item) !== '';
            }));
        }

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('cms/homepage', 'public');
        }

        HomepageSection::create($data);

        return redirect()->route('admin.cms.homepage.index')->with('success', 'Homepage section created successfully.');
    }

    public function edit(HomepageSection $homepage_section)
    {
        $this->authorize('website.manage');

        return view('admin.cms.homepage.edit', ['section' => $homepage_section]);
    }

    public function update(HomepageSectionRequest $request, HomepageSection $homepage_section)
    {
        $this->authorize('website.manage');

        $data = $request->validated();

        if (isset($data['governance_bullets']) && is_array($data['governance_bullets'])) {
            $data['governance_bullets'] = array_values(array_filter($data['governance_bullets'], function ($item) {
                return !is_null($item) && trim($item) !== '';
            }));
        }

        if ($request->hasFile('image')) {
            if ($homepage_section->image && Storage::disk('public')->exists($homepage_section->image)) {
                Storage::disk('public')->delete($homepage_section->image);
            }
            $data['image'] = $request->file('image')->store('cms/homepage', 'public');
        }

        $homepage_section->update($data);

        return redirect()->route('admin.cms.homepage.index')->with('success', 'Homepage section updated successfully.');
    }

    public function toggleStatus(HomepageSection $homepage_section)
    {
        $this->authorize('website.manage');

        $newStatus = $homepage_section->status === 'active' ? 'inactive' : 'active';
        $homepage_section->update(['status' => $newStatus]);

        return back()->with('success', 'Homepage section status updated to ' . strtoupper($newStatus));
    }

    public function destroy(HomepageSection $homepage_section)
    {
        $this->authorize('website.manage');

        if ($homepage_section->image && Storage::disk('public')->exists($homepage_section->image)) {
            Storage::disk('public')->delete($homepage_section->image);
        }

        $homepage_section->delete();

        return redirect()->route('admin.cms.homepage.index')->with('success', 'Homepage section deleted successfully.');
    }
}
