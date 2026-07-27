<?php

namespace App\Http\Controllers\Admin\Cms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cms\SeoSettingRequest;
use App\Models\SeoSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SeoSettingController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('website.manage');

        $query = SeoSetting::query();

        if ($request->filled('search')) {
            $query->where('page_name', 'like', '%' . $request->search . '%')
                  ->orWhere('meta_title', 'like', '%' . $request->search . '%')
                  ->orWhere('keywords', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $seoList = $query->orderBy('id', 'asc')->paginate(10);

        return view('admin.cms.seo.index', compact('seoList'));
    }

    public function create()
    {
        $this->authorize('website.manage');

        return view('admin.cms.seo.create');
    }

    public function store(SeoSettingRequest $request)
    {
        $this->authorize('website.manage');

        $data = $request->validated();
        $data['page_name'] = Str::slug($data['page_name'], '_');

        if ($request->hasFile('og_image')) {
            $data['og_image'] = $request->file('og_image')->store('cms/seo', 'public');
        }

        SeoSetting::create($data);

        return redirect()->route('admin.cms.seo.index')->with('success', 'SEO setting page created successfully.');
    }

    public function edit(SeoSetting $seo)
    {
        $this->authorize('website.manage');

        return view('admin.cms.seo.edit', compact('seo'));
    }

    public function update(SeoSettingRequest $request, SeoSetting $seo)
    {
        $this->authorize('website.manage');

        $data = $request->validated();
        $data['page_name'] = Str::slug($data['page_name'], '_');

        if ($request->hasFile('og_image')) {
            if ($seo->og_image && Storage::disk('public')->exists($seo->og_image)) {
                Storage::disk('public')->delete($seo->og_image);
            }
            $data['og_image'] = $request->file('og_image')->store('cms/seo', 'public');
        }

        $seo->update($data);

        return redirect()->route('admin.cms.seo.index')->with('success', 'SEO setting updated successfully.');
    }

    public function toggleStatus(SeoSetting $seo)
    {
        $this->authorize('website.manage');

        $newStatus = $seo->status === 'active' ? 'inactive' : 'active';
        $seo->update(['status' => $newStatus]);

        return back()->with('success', 'SEO page status updated to ' . strtoupper($newStatus));
    }

    public function destroy(SeoSetting $seo)
    {
        $this->authorize('website.manage');

        if ($seo->og_image && Storage::disk('public')->exists($seo->og_image)) {
            Storage::disk('public')->delete($seo->og_image);
        }

        $seo->delete();

        return redirect()->route('admin.cms.seo.index')->with('success', 'SEO setting page deleted successfully.');
    }
}
