<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cms\ContactInquiryRequest;
use App\Models\ContactInquiry;
use App\Models\WebsiteSetting;

class ContactController extends Controller
{
    /**
     * Show Public Contact Us Page
     */
    public function show()
    {
        $settings = WebsiteSetting::first();
        return view('public.contact', compact('settings'));
    }

    /**
     * Handle Public Contact Form Submission
     */
    public function submit(ContactInquiryRequest $request)
    {
        ContactInquiry::create($request->validated());

        return back()->with('success', 'Thank you for reaching out! Your message has been received. Our customer support team will contact you shortly.');
    }
}
