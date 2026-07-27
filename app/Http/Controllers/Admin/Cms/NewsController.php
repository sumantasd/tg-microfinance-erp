<?php

namespace App\Http\Controllers\Admin\Cms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cms\NewsRequest;
use App\Models\News;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class NewsController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('website.manage');

        $query = News::query();

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('short_description', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $news = $query->orderBy('sort_order', 'asc')->orderBy('published_date', 'desc')->orderBy('id', 'desc')->paginate(10);

        return view('admin.cms.news.index', compact('news'));
    }

    public function create()
    {
        $this->authorize('website.manage');

        return view('admin.cms.news.create');
    }

    public function store(NewsRequest $request)
    {
        $this->authorize('website.manage');

        $data = $request->validated();

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['title']);
        } else {
            $data['slug'] = Str::slug($data['slug']);
        }

        if ($request->hasFile('featured_image')) {
            $data['featured_image'] = $request->file('featured_image')->store('cms/news', 'public');
        }

        if (empty($data['published_date'])) {
            $data['published_date'] = now()->toDateString();
        }

        News::create($data);

        return redirect()->route('admin.cms.news.index')->with('success', 'News article created successfully.');
    }

    public function edit(News $news)
    {
        $this->authorize('website.manage');

        return view('admin.cms.news.edit', compact('news'));
    }

    public function update(NewsRequest $request, News $news)
    {
        $this->authorize('website.manage');

        $data = $request->validated();

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['title']);
        } else {
            $data['slug'] = Str::slug($data['slug']);
        }

        if ($request->hasFile('featured_image')) {
            if ($news->featured_image && Storage::disk('public')->exists($news->featured_image)) {
                Storage::disk('public')->delete($news->featured_image);
            }
            $data['featured_image'] = $request->file('featured_image')->store('cms/news', 'public');
        }

        $news->update($data);

        return redirect()->route('admin.cms.news.index')->with('success', 'News article updated successfully.');
    }

    public function toggleStatus(News $news)
    {
        $this->authorize('website.manage');

        $newStatus = $news->status === 'published' ? 'draft' : 'published';
        $news->update(['status' => $newStatus]);

        return back()->with('success', 'News article status updated to ' . strtoupper($newStatus));
    }

    public function destroy(News $news)
    {
        $this->authorize('website.manage');

        if ($news->featured_image && Storage::disk('public')->exists($news->featured_image)) {
            Storage::disk('public')->delete($news->featured_image);
        }

        $news->delete();

        return redirect()->route('admin.cms.news.index')->with('success', 'News article deleted successfully.');
    }
}
