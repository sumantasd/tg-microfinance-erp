<?php

namespace App\Http\Controllers\Admin\Cms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cms\ServiceRequest;
use App\Models\CmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CmsServiceController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('website.manage');

        $query = CmsService::query();

        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('short_description', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $services = $query->orderBy('sort_order', 'asc')->orderBy('id', 'desc')->paginate(10);

        return view('admin.cms.services.index', compact('services'));
    }

    public function create()
    {
        $this->authorize('website.manage');

        return view('admin.cms.services.create');
    }

    public function store(ServiceRequest $request)
    {
        $this->authorize('website.manage');

        $data = $request->validated();
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['title']);
        }

        if ($request->hasFile('banner_image')) {
            $data['banner_image'] = $request->file('banner_image')->store('cms/services', 'public');
        }

        CmsService::create($data);

        return redirect()->route('admin.cms.services.index')->with('success', 'Service entry created successfully.');
    }

    public function edit(CmsService $service)
    {
        $this->authorize('website.manage');

        return view('admin.cms.services.edit', compact('service'));
    }

    public function update(ServiceRequest $request, CmsService $service)
    {
        $this->authorize('website.manage');

        $data = $request->validated();
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['title']);
        }

        if ($request->hasFile('banner_image')) {
            if ($service->banner_image && Storage::disk('public')->exists($service->banner_image)) {
                Storage::disk('public')->delete($service->banner_image);
            }
            $data['banner_image'] = $request->file('banner_image')->store('cms/services', 'public');
        }

        $service->update($data);

        return redirect()->route('admin.cms.services.index')->with('success', 'Service entry updated successfully.');
    }

    public function toggleStatus(CmsService $service)
    {
        $this->authorize('website.manage');

        $newStatus = $service->status === 'active' ? 'inactive' : 'active';
        $service->update(['status' => $newStatus]);

        return back()->with('success', 'Status updated to ' . strtoupper($newStatus));
    }

    public function destroy(CmsService $service)
    {
        $this->authorize('website.manage');

        if ($service->banner_image && Storage::disk('public')->exists($service->banner_image)) {
            Storage::disk('public')->delete($service->banner_image);
        }

        $service->delete();

        return redirect()->route('admin.cms.services.index')->with('success', 'Service entry deleted successfully.');
    }
}
