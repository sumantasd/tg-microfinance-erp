<?php

namespace App\Http\Controllers\Admin\Cms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cms\BannerRequest;
use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BannerController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('website.manage');

        $query = Banner::query();

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('subtitle', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $banners = $query->orderBy('sort_order', 'asc')->orderBy('id', 'desc')->paginate(10);

        return view('admin.cms.banners.index', compact('banners'));
    }

    public function create()
    {
        $this->authorize('website.manage');

        return view('admin.cms.banners.create');
    }

    public function store(BannerRequest $request)
    {
        $this->authorize('website.manage');

        $data = $request->validated();

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('cms/banners', 'public');
        }

        Banner::create($data);

        return redirect()->route('admin.cms.banners.index')->with('success', 'Banner slide created successfully.');
    }

    public function edit(Banner $banner)
    {
        $this->authorize('website.manage');

        return view('admin.cms.banners.edit', compact('banner'));
    }

    public function update(BannerRequest $request, Banner $banner)
    {
        $this->authorize('website.manage');

        $data = $request->validated();

        if ($request->hasFile('image')) {
            if ($banner->image && Storage::disk('public')->exists($banner->image)) {
                Storage::disk('public')->delete($banner->image);
            }
            $data['image'] = $request->file('image')->store('cms/banners', 'public');
        }

        $banner->update($data);

        return redirect()->route('admin.cms.banners.index')->with('success', 'Banner slide updated successfully.');
    }

    public function toggleStatus(Banner $banner)
    {
        $this->authorize('website.manage');

        $newStatus = $banner->status === 'active' ? 'inactive' : 'active';
        $banner->update(['status' => $newStatus]);

        return back()->with('success', 'Banner status updated to ' . strtoupper($newStatus));
    }

    public function destroy(Banner $banner)
    {
        $this->authorize('website.manage');

        if ($banner->image && Storage::disk('public')->exists($banner->image)) {
            Storage::disk('public')->delete($banner->image);
        }

        $banner->delete();

        return redirect()->route('admin.cms.banners.index')->with('success', 'Banner slide deleted successfully.');
    }
}
