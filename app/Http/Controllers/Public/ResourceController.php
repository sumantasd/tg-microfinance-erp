<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Download;
use App\Models\Faq;
use App\Models\Gallery;
use App\Models\News;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ResourceController extends Controller
{
    /**
     * Public News List Page
     */
    public function news(Request $request)
    {
        $query = News::where('status', 'published');

        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('short_description', 'like', '%' . $request->search . '%');
        }

        $newsList = $query->orderBy('sort_order', 'asc')
                          ->orderBy('published_date', 'desc')
                          ->orderBy('id', 'desc')
                          ->paginate(9);

        return view('public.resources.news', compact('newsList'));
    }

    /**
     * Public News Article Details Page
     */
    public function newsShow($slug)
    {
        $article = News::where('slug', $slug)->where('status', 'published')->firstOrFail();

        $recentNews = News::where('status', 'published')
            ->where('id', '!=', $article->id)
            ->orderBy('published_date', 'desc')
            ->take(5)
            ->get();

        return view('public.resources.news-show', compact('article', 'recentNews'));
    }

    /**
     * Public Gallery Page
     */
    public function gallery(Request $request)
    {
        $query = Gallery::where('status', 'active');

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        $galleries = $query->orderBy('sort_order', 'asc')->orderBy('id', 'desc')->get();

        $categories = Gallery::where('status', 'active')
            ->whereNotNull('category')
            ->distinct()
            ->pluck('category');

        return view('public.resources.gallery', compact('galleries', 'categories'));
    }

    /**
     * Public Downloads Page
     */
    public function downloads()
    {
        $downloads = Download::where('status', 'active')
            ->orderBy('sort_order', 'asc')
            ->orderBy('id', 'desc')
            ->get();

        return view('public.resources.downloads', compact('downloads'));
    }

    /**
     * File Download Handler
     */
    public function downloadFile($id)
    {
        $download = Download::where('id', $id)->where('status', 'active')->firstOrFail();

        if ($download->file && Storage::disk('public')->exists($download->file)) {
            return Storage::disk('public')->download($download->file, $download->title . '.' . pathinfo($download->file, PATHINFO_EXTENSION));
        }

        return back()->with('error', 'Requested file is currently unavailable.');
    }

    /**
     * Public FAQ Page
     */
    public function faq()
    {
        $faqs = Faq::where('status', 'active')
            ->orderBy('sort_order', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        return view('public.resources.faq', compact('faqs'));
    }

    /**
     * Public Career Opportunities Page
     */
    public function career()
    {
        $jobs = \App\Models\Career::where('status', 'active')
            ->orderBy('sort_order', 'asc')
            ->orderBy('id', 'desc')
            ->get();

        return view('public.resources.career', compact('jobs'));
    }
}
