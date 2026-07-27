<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\CmsLoanProduct;
use App\Models\CmsSavingsProduct;
use App\Models\CmsService;
use App\Models\InterestRate;

class ProductController extends Controller
{
    /**
     * Display the public Loan Products page.
     */
    public function loanProducts()
    {
        $products = CmsLoanProduct::where('status', 'active')
            ->orderBy('sort_order', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        return view('public.products.loan', compact('products'));
    }

    /**
     * Display the public Savings Products page.
     */
    public function savingsProducts()
    {
        $products = CmsSavingsProduct::where('status', 'active')
            ->orderBy('sort_order', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        return view('public.products.savings', compact('products'));
    }

    /**
     * Display dynamic Interest Rates schedule matrix.
     */
    public function interestRates()
    {
        $rates = InterestRate::where('status', 'active')
            ->orderBy('sort_order', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        return view('public.products.interest-rates', compact('rates'));
    }

    /**
     * Display Services Index.
     */
    public function servicesIndex()
    {
        $services = CmsService::where('status', 'active')
            ->orderBy('sort_order', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        return view('public.services.index', compact('services'));
    }

    /**
     * Display Digital Banking Service page.
     */
    public function serviceDigitalBanking()
    {
        $service = CmsService::where('slug', 'digital-banking')->first();
        return view('public.services.digital-banking', compact('service'));
    }

    /**
     * Display Collection Services page.
     */
    public function serviceCollectionServices()
    {
        $service = CmsService::where('slug', 'collection-services')->first();
        return view('public.services.collection-services', compact('service'));
    }

    /**
     * Display Financial Advisory page.
     */
    public function serviceFinancialAdvisory()
    {
        $service = CmsService::where('slug', 'financial-advisory')->first();
        return view('public.services.financial-advisory', compact('service'));
    }

    /**
     * Display Dynamic Service details by slug.
     */
    public function serviceShow($slug)
    {
        $service = CmsService::where('slug', $slug)->where('status', 'active')->firstOrFail();
        return view('public.services.show', compact('service'));
    }
}
