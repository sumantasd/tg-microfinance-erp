@extends('layouts.public')

@section('title', 'Contact Us - TG Microfinance ERP')
@section('meta_description', 'Get in touch with TG Microfinance Head Office, customer support line, or branch inquiries.')

@section('content')
<x-ui.page-banner
    title="Contact Customer Support"
    subtitle="Have questions about our loan products or savings schemes? Reach out to our customer support team."
    badge="Get In Touch"
    :breadcrumbs="['Contact' => '']"
/>

<section class="container-xl py-5">
    <div class="row g-4">
        <div class="col-lg-7">
            <x-ui.card class="p-4 p-md-5">
                <h4 class="fw-bold mb-4">Send Us a Message</h4>
                <form>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Your Full Name</label>
                            <input type="text" class="form-control bg-light" placeholder="John Doe">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Phone Number</label>
                            <input type="text" class="form-control bg-light" placeholder="+1 (555) 000-0000">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold text-muted">Email Address</label>
                            <input type="email" class="form-control bg-light" placeholder="name@example.com">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold text-muted">Message / Inquiry</label>
                            <textarea class="form-control bg-light" rows="4" placeholder="How can our branch assist your business?"></textarea>
                        </div>
                        <div class="col-12">
                            <button type="button" class="btn btn-primary rounded-pill px-4 py-2.5 fw-bold shadow-sm">
                                <i class="bi bi-send me-1"></i> Send Inquiry
                            </button>
                        </div>
                    </div>
                </form>
            </x-ui.card>
        </div>

        <div class="col-lg-5">
            <x-ui.card class="p-4 bg-light h-100">
                <h5 class="fw-bold mb-4 text-dark">Head Office Info</h5>
                <div class="mb-3">
                    <h6 class="fw-bold small text-primary mb-1"><i class="bi bi-building me-1"></i> Central Headquarters</h6>
                    <p class="small text-muted mb-0">100 Financial Avenue, Suite 500</p>
                </div>
                <div class="mb-3">
                    <h6 class="fw-bold small text-primary mb-1"><i class="bi bi-telephone me-1"></i> Customer Hotline</h6>
                    <p class="small text-muted mb-0">+1 (800) 555-0199 / +1 (800) 555-0200</p>
                </div>
                <div class="mb-3">
                    <h6 class="fw-bold small text-primary mb-1"><i class="bi bi-envelope me-1"></i> Email Inquiries</h6>
                    <p class="small text-muted mb-0">info@tgmicrofinance.com</p>
                </div>
            </x-ui.card>
        </div>
    </div>
</section>

<x-ui.cta />
@endsection
