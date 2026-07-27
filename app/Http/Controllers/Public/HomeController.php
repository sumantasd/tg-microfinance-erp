<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\CmsLoanProduct;
use App\Models\CmsSavingsProduct;
use App\Models\Faq;
use App\Models\Gallery;
use App\Models\HomepageSection;
use App\Models\News;
use App\Models\WebsiteSetting;
use App\Models\WhyChooseUs;

class HomeController extends Controller
{
    /**
     * Display the public homepage with full dynamic CMS content.
     */
    public function index()
    {
        $settings = WebsiteSetting::first();

        // 1. Hero Banners
        $banners = Banner::where('status', 'active')
            ->orderBy('sort_order', 'asc')
            ->orderBy('id', 'desc')
            ->get();

        // 2. Homepage Sections
        $sections = HomepageSection::where('status', 'active')
            ->orderBy('sort_order', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $sectionsKeyed = $sections->keyBy('section_key');

        // 3. Why Choose Us Items
        $whyChooseUsItems = WhyChooseUs::where('status', 'active')
            ->orderBy('sort_order', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        // 4. Loan Products Preview (Limit 3)
        $loanProducts = CmsLoanProduct::where('status', 'active')
            ->orderBy('sort_order', 'asc')
            ->orderBy('id', 'asc')
            ->take(3)
            ->get();

        // 5. Savings Products Preview (Limit 3)
        $savingsProducts = CmsSavingsProduct::where('status', 'active')
            ->orderBy('sort_order', 'asc')
            ->orderBy('id', 'asc')
            ->take(3)
            ->get();

        // 6. Latest News Articles (Limit 3)
        $latestNews = News::where('status', 'published')
            ->orderBy('published_date', 'desc')
            ->orderBy('id', 'desc')
            ->take(3)
            ->get();

        // 7. Gallery Preview Items (Limit 6)
        $galleryItems = Gallery::where('status', 'active')
            ->orderBy('sort_order', 'asc')
            ->orderBy('id', 'desc')
            ->take(6)
            ->get();

        // 8. FAQ Preview Accordion Items (Limit 5)
        $faqs = Faq::where('status', 'active')
            ->orderBy('sort_order', 'asc')
            ->orderBy('id', 'asc')
            ->take(5)
            ->get();

        return view('public.home', compact(
            'settings',
            'banners',
            'sections',
            'sectionsKeyed',
            'whyChooseUsItems',
            'loanProducts',
            'savingsProducts',
            'latestNews',
            'galleryItems',
            'faqs'
        ));
    }
}
