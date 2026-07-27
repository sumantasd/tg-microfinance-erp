<?php

namespace App\Http\Controllers\Admin\Cms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cms\GalleryRequest;
use App\Models\Gallery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class GalleryController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('website.manage');

        $query = Gallery::query();

        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('category', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $galleries = $query->orderBy('sort_order', 'asc')->orderBy('id', 'desc')->paginate(12);

        return view('admin.cms.gallery.index', compact('galleries'));
    }

    public function create()
    {
        $this->authorize('website.manage');

        return view('admin.cms.gallery.create');
    }

    public function store(GalleryRequest $request)
    {
        $this->authorize('website.manage');

        $data = $request->validated();

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('cms/gallery', 'public');
        }

        Gallery::create($data);

        return redirect()->route('admin.cms.gallery.index')->with('success', 'Gallery item uploaded successfully.');
    }

    public function edit(Gallery $gallery)
    {
        $this->authorize('website.manage');

        return view('admin.cms.gallery.edit', compact('gallery'));
    }

    public function update(GalleryRequest $request, Gallery $gallery)
    {
        $this->authorize('website.manage');

        $data = $request->validated();

        if ($request->hasFile('image')) {
            if ($gallery->image && Storage::disk('public')->exists($gallery->image)) {
                Storage::disk('public')->delete($gallery->image);
            }
            $data['image'] = $request->file('image')->store('cms/gallery', 'public');
        }

        $gallery->update($data);

        return redirect()->route('admin.cms.gallery.index')->with('success', 'Gallery item updated successfully.');
    }

    public function toggleStatus(Gallery $gallery)
    {
        $this->authorize('website.manage');

        $newStatus = $gallery->status === 'active' ? 'inactive' : 'active';
        $gallery->update(['status' => $newStatus]);

        return back()->with('success', 'Gallery item status updated to ' . strtoupper($newStatus));
    }

    public function destroy(Gallery $gallery)
    {
        $this->authorize('website.manage');

        if ($gallery->image && Storage::disk('public')->exists($gallery->image)) {
            Storage::disk('public')->delete($gallery->image);
        }

        $gallery->delete();

        return redirect()->route('admin.cms.gallery.index')->with('success', 'Gallery item deleted successfully.');
    }
}
