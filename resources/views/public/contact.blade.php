@extends('layouts.public')

@section('title', 'Contact Us - ' . ($settings->company_name ?? 'TG Microfinance ERP'))
@section('meta_description', 'Get in touch with ' . ($settings->company_name ?? 'TG Microfinance') . ' Head Office, customer support line, or branch inquiries.')

@section('content')
<x-ui.page-banner
    title="Contact Customer Support"
    subtitle="Have questions about our loan products or savings schemes? Reach out to our customer support team."
    badge="Get In Touch"
    :breadcrumbs="['Contact' => '']"
/>

<section class="container-xl py-5">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger mb-4">
            <h6 class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-1"></i> Please fix validation errors:</h6>
            <ul class="mb-0 small ps-3">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-lg-7">
            <x-ui.card class="p-4 p-md-5">
                <h4 class="fw-bold mb-4">Send Us a Message</h4>
                <form action="{{ route('public.contact.submit') }}" method="POST">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Your Full Name *</label>
                            <input type="text" name="name" value="{{ old('name') }}" class="form-control bg-light" placeholder="John Doe" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Phone Number</label>
                            <input type="text" name="phone" value="{{ old('phone') }}" class="form-control bg-light" placeholder="+1 (555) 000-0000">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Email Address *</label>
                            <input type="email" name="email" value="{{ old('email') }}" class="form-control bg-light" placeholder="name@example.com" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Subject / Category</label>
                            <input type="text" name="subject" value="{{ old('subject') }}" class="form-control bg-light" placeholder="e.g. Loan Inquiry, Savings Account">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold text-muted">Message / Inquiry *</label>
                            <textarea name="message" class="form-control bg-light" rows="4" placeholder="How can our branch assist your business?" required>{{ old('message') }}</textarea>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary rounded-pill px-4 py-2.5 fw-bold shadow-sm">
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
                    <p class="small text-muted mb-0">{{ $settings->address ?? '100 Financial Avenue, Suite 500' }}</p>
                </div>
                <div class="mb-3">
                    <h6 class="fw-bold small text-primary mb-1"><i class="bi bi-telephone me-1"></i> Customer Hotline</h6>
                    <p class="small text-muted mb-0">{{ $settings->phone ?? '+1 (800) 555-0199' }}</p>
                </div>
                <div class="mb-3">
                    <h6 class="fw-bold small text-primary mb-1"><i class="bi bi-envelope me-1"></i> Email Inquiries</h6>
                    <p class="small text-muted mb-0">{{ $settings->email ?? 'info@tgmicrofinance.com' }}</p>
                </div>
            </x-ui.card>
        </div>
    </div>
</section>

<x-ui.cta />
@endsection
