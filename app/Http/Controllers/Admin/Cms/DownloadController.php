<?php

namespace App\Http\Controllers\Admin\Cms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cms\DownloadRequest;
use App\Models\Download;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DownloadController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('website.manage');

        $query = Download::query();

        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $downloads = $query->orderBy('sort_order', 'asc')->orderBy('id', 'desc')->paginate(10);

        return view('admin.cms.downloads.index', compact('downloads'));
    }

    public function create()
    {
        $this->authorize('website.manage');

        return view('admin.cms.downloads.create');
    }

    public function store(DownloadRequest $request)
    {
        $this->authorize('website.manage');

        $data = $request->validated();

        if ($request->hasFile('file')) {
            $data['file'] = $request->file('file')->store('cms/downloads', 'public');
        }

        Download::create($data);

        return redirect()->route('admin.cms.downloads.index')->with('success', 'Download document uploaded successfully.');
    }

    public function edit(Download $download)
    {
        $this->authorize('website.manage');

        return view('admin.cms.downloads.edit', compact('download'));
    }

    public function update(DownloadRequest $request, Download $download)
    {
        $this->authorize('website.manage');

        $data = $request->validated();

        if ($request->hasFile('file')) {
            if ($download->file && Storage::disk('public')->exists($download->file)) {
                Storage::disk('public')->delete($download->file);
            }
            $data['file'] = $request->file('file')->store('cms/downloads', 'public');
        }

        $download->update($data);

        return redirect()->route('admin.cms.downloads.index')->with('success', 'Download document updated successfully.');
    }

    public function toggleStatus(Download $download)
    {
        $this->authorize('website.manage');

        $newStatus = $download->status === 'active' ? 'inactive' : 'active';
        $download->update(['status' => $newStatus]);

        return back()->with('success', 'Download document status updated to ' . strtoupper($newStatus));
    }

    public function destroy(Download $download)
    {
        $this->authorize('website.manage');

        if ($download->file && Storage::disk('public')->exists($download->file)) {
            Storage::disk('public')->delete($download->file);
        }

        $download->delete();

        return redirect()->route('admin.cms.downloads.index')->with('success', 'Download document deleted successfully.');
    }
}
