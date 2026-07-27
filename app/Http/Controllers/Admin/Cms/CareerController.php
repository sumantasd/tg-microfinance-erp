<?php

namespace App\Http\Controllers\Admin\Cms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cms\CareerRequest;
use App\Models\Career;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CareerController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('website.manage');

        $query = Career::query();

        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('location', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $careers = $query->orderBy('sort_order', 'asc')->orderBy('id', 'desc')->paginate(10);

        return view('admin.cms.careers.index', compact('careers'));
    }

    public function create()
    {
        $this->authorize('website.manage');

        return view('admin.cms.careers.create');
    }

    public function store(CareerRequest $request)
    {
        $this->authorize('website.manage');

        $data = $request->validated();
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['title']);
        }

        Career::create($data);

        return redirect()->route('admin.cms.careers.index')->with('success', 'Job opening created successfully.');
    }

    public function edit(Career $career)
    {
        $this->authorize('website.manage');

        return view('admin.cms.careers.edit', compact('career'));
    }

    public function update(CareerRequest $request, Career $career)
    {
        $this->authorize('website.manage');

        $data = $request->validated();
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['title']);
        }

        $career->update($data);

        return redirect()->route('admin.cms.careers.index')->with('success', 'Job opening updated successfully.');
    }

    public function toggleStatus(Career $career)
    {
        $this->authorize('website.manage');

        $newStatus = $career->status === 'active' ? 'inactive' : 'active';
        $career->update(['status' => $newStatus]);

        return back()->with('success', 'Status updated to ' . strtoupper($newStatus));
    }

    public function destroy(Career $career)
    {
        $this->authorize('website.manage');

        $career->delete();

        return redirect()->route('admin.cms.careers.index')->with('success', 'Job opening deleted successfully.');
    }
}
