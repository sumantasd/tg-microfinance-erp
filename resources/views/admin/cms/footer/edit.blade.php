@extends('layouts.admin')

@section('title', 'Footer CMS Settings - TG Microfinance ERP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1"><i class="bi bi-layout-text-window-reverse text-primary me-2"></i>Footer CMS Settings</h4>
        <p class="text-muted small mb-0">Configure footer brand logo, about excerpt, social links, contact information, and copyright notice.</p>
    </div>
</div>

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

<x-ui.card class="p-4 shadow-sm">
    <form action="{{ route('admin.cms.footer.update') }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label small fw-bold text-secondary">Footer Brand Logo</label>
                <input type="file" name="footer_logo" class="form-control bg-light">
                @if($footer->footer_logo_url)
                    <div class="mt-2 p-2 bg-dark rounded d-inline-flex align-items-center gap-3">
                        <img src="{{ $footer->footer_logo_url }}" alt="Footer Logo" style="max-height: 40px;">
                        <span class="small text-light">Current Footer Logo</span>
                    </div>
                @endif
            </div>

            <div class="col-md-6">
                <label class="form-label small fw-bold text-secondary">Copyright Text</label>
                <input type="text" name="copyright_text" value="{{ old('copyright_text', $footer->copyright_text) }}" class="form-control bg-light" placeholder="e.g. © 2026 Astha Welfare Society. Developed By Tech Googly">
            </div>

            <div class="col-12">
                <label class="form-label small fw-bold text-secondary">Footer About Text Excerpt</label>
                <textarea name="about_text" rows="3" class="form-control bg-light" placeholder="Empowering micro-entrepreneurs and financial inclusion globally...">{{ old('about_text', $footer->about_text) }}</textarea>
            </div>

            <h5 class="fw-bold text-dark pt-3 mb-0 border-top">Contact & Location Info</h5>

            <div class="col-md-4">
                <label class="form-label small fw-bold text-secondary">Physical Address</label>
                <input type="text" name="address" value="{{ old('address', $footer->address) }}" class="form-control bg-light" placeholder="100 Financial Avenue, Suite 500">
            </div>

            <div class="col-md-4">
                <label class="form-label small fw-bold text-secondary">Hotline / Phone Number</label>
                <input type="text" name="phone" value="{{ old('phone', $footer->phone) }}" class="form-control bg-light" placeholder="+1 (800) 555-0199">
            </div>

            <div class="col-md-4">
                <label class="form-label small fw-bold text-secondary">Support Email</label>
                <input type="email" name="email" value="{{ old('email', $footer->email) }}" class="form-control bg-light" placeholder="info@tgmicrofinance.org">
            </div>

            <h5 class="fw-bold text-dark pt-3 mb-0 border-top">Social Media Handles</h5>

            <div class="col-md-4">
                <label class="form-label small fw-bold text-secondary"><i class="bi bi-facebook me-1 text-primary"></i> Facebook URL</label>
                <input type="url" name="social_links[facebook]" value="{{ old('social_links.facebook', $footer->social_links['facebook'] ?? '') }}" class="form-control bg-light" placeholder="https://facebook.com/tgmicrofinance">
            </div>

            <div class="col-md-4">
                <label class="form-label small fw-bold text-secondary"><i class="bi bi-twitter-x me-1 text-dark"></i> Twitter/X URL</label>
                <input type="url" name="social_links[twitter]" value="{{ old('social_links.twitter', $footer->social_links['twitter'] ?? '') }}" class="form-control bg-light" placeholder="https://twitter.com/tgmicrofinance">
            </div>

            <div class="col-md-4">
                <label class="form-label small fw-bold text-secondary"><i class="bi bi-linkedin me-1 text-primary"></i> LinkedIn URL</label>
                <input type="url" name="social_links[linkedin]" value="{{ old('social_links.linkedin', $footer->social_links['linkedin'] ?? '') }}" class="form-control bg-light" placeholder="https://linkedin.com/company/tgmicrofinance">
            </div>

            <div class="col-md-6">
                <label class="form-label small fw-bold text-secondary"><i class="bi bi-instagram me-1 text-danger"></i> Instagram URL</label>
                <input type="url" name="social_links[instagram]" value="{{ old('social_links.instagram', $footer->social_links['instagram'] ?? '') }}" class="form-control bg-light" placeholder="https://instagram.com/tgmicrofinance">
            </div>

            <div class="col-md-6">
                <label class="form-label small fw-bold text-secondary"><i class="bi bi-youtube me-1 text-danger"></i> YouTube Channel URL</label>
                <input type="url" name="social_links[youtube]" value="{{ old('social_links.youtube', $footer->social_links['youtube'] ?? '') }}" class="form-control bg-light" placeholder="https://youtube.com/@tgmicrofinance">
            </div>

            <div class="col-12 pt-3 border-top d-flex gap-2">
                <button type="submit" class="btn btn-primary rounded-pill px-4 py-2 fw-bold shadow-sm">
                    <i class="bi bi-check-circle me-1"></i> Save Footer Settings
                </button>
            </div>
        </div>
    </form>
</x-ui.card>
@endsection
