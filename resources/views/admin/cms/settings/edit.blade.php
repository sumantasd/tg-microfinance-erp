@extends('layouts.admin')

@section('title', 'Website Settings - TG Microfinance ERP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1"><i class="bi bi-sliders text-info me-2"></i>Website & Calculator Settings</h4>
        <p class="text-muted small mb-0">Configure company metadata, brand logos, contact details, social links, footer, and homepage loan rate estimator calculator.</p>
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
    <form action="{{ route('admin.cms.settings.update') }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="row g-4">
            <!-- Company Info Header -->
            <div class="col-12">
                <h6 class="fw-bold text-primary border-bottom pb-2 mb-0"><i class="bi bi-building me-1"></i> General Branding & Company Info</h6>
            </div>

            <div class="col-md-6">
                <label class="form-label small fw-bold text-secondary">Company Name *</label>
                <input type="text" name="company_name" value="{{ old('company_name', $settings->company_name) }}" class="form-control bg-light" placeholder="TG Microfinance" required>
            </div>

            <div class="col-md-3">
                <label class="form-label small fw-bold text-secondary">Brand Logo</label>
                <input type="file" name="logo" class="form-control bg-light">
                @if($settings->logo)
                    <div class="mt-2 p-2 bg-light border rounded d-flex align-items-center gap-2">
                        <img src="{{ asset('storage/' . $settings->logo) }}" alt="Logo" style="max-height: 40px;" class="rounded">
                        <span class="small text-muted">Current Logo</span>
                    </div>
                @endif
            </div>

            <div class="col-md-3">
                <label class="form-label small fw-bold text-secondary">Favicon</label>
                <input type="file" name="favicon" class="form-control bg-light">
                @if($settings->favicon)
                    <div class="mt-2 p-2 bg-light border rounded d-flex align-items-center gap-2">
                        <img src="{{ asset('storage/' . $settings->favicon) }}" alt="Favicon" style="max-height: 24px;">
                        <span class="small text-muted">Current Favicon</span>
                    </div>
                @endif
            </div>

            <!-- Homepage Loan Calculator Settings -->
            <div class="col-12 pt-2">
                <h6 class="fw-bold text-primary border-bottom pb-2 mb-0"><i class="bi bi-calculator me-1"></i> Homepage Loan Calculator Settings</h6>
            </div>

            <div class="col-md-3">
                <div class="form-check form-switch mt-4">
                    <input class="form-check-input" type="checkbox" name="calc_enabled" value="1" id="calcEnabled" {{ old('calc_enabled', $settings->calc_enabled ?? true) ? 'checked' : '' }}>
                    <label class="form-check-label fw-bold text-dark" for="calcEnabled">Enable Estimator Widget</label>
                </div>
            </div>

            <div class="col-md-4">
                <label class="form-label small fw-bold text-secondary">Calculator Title</label>
                <input type="text" name="calc_title" value="{{ old('calc_title', $settings->calc_title ?? 'Loan Rate Estimator') }}" class="form-control bg-light">
            </div>

            <div class="col-md-5">
                <label class="form-label small fw-bold text-secondary">Calculator Subtitle</label>
                <input type="text" name="calc_subtitle" value="{{ old('calc_subtitle', $settings->calc_subtitle ?? 'Instant repayment calculation') }}" class="form-control bg-light">
            </div>

            <div class="col-md-3">
                <label class="form-label small fw-bold text-secondary">Default Amount (₹)</label>
                <input type="text" name="calc_default_amount" value="{{ old('calc_default_amount', $settings->calc_default_amount ?? '5000') }}" class="form-control bg-light">
            </div>

            <div class="col-md-3">
                <label class="form-label small fw-bold text-secondary">Minimum Amount (₹)</label>
                <input type="text" name="calc_min_amount" value="{{ old('calc_min_amount', $settings->calc_min_amount ?? '500') }}" class="form-control bg-light">
            </div>

            <div class="col-md-3">
                <label class="form-label small fw-bold text-secondary">Maximum Amount (₹)</label>
                <input type="text" name="calc_max_amount" value="{{ old('calc_max_amount', $settings->calc_max_amount ?? '25000') }}" class="form-control bg-light">
            </div>

            <div class="col-md-3">
                <label class="form-label small fw-bold text-secondary">Default Interest Rate (P.A.)</label>
                <input type="text" name="calc_interest_rate" value="{{ old('calc_interest_rate', $settings->calc_interest_rate ?? '12.5% P.A.') }}" class="form-control bg-light">
            </div>

            <div class="col-md-4">
                <label class="form-label small fw-bold text-secondary">Calculation Method Type</label>
                <select name="calc_type" class="form-select bg-light">
                    <option value="reducing_balance" {{ old('calc_type', $settings->calc_type ?? 'reducing_balance') === 'reducing_balance' ? 'selected' : '' }}>Reducing Balance Method</option>
                    <option value="flat_rate" {{ old('calc_type', $settings->calc_type ?? '') === 'flat_rate' ? 'selected' : '' }}>Flat Interest Rate Method</option>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label small fw-bold text-secondary">Rounding Type *</label>
                <select name="calc_rounding_type" class="form-select bg-light">
                    <option value="nearest_integer" {{ old('calc_rounding_type', $settings->calc_rounding_type ?? 'nearest_integer') === 'nearest_integer' ? 'selected' : '' }}>Nearest Dollar / Integer</option>
                    <option value="round_up" {{ old('calc_rounding_type', $settings->calc_rounding_type ?? '') === 'round_up' ? 'selected' : '' }}>Round Up (Ceil)</option>
                    <option value="round_down" {{ old('calc_rounding_type', $settings->calc_rounding_type ?? '') === 'round_down' ? 'selected' : '' }}>Round Down (Floor)</option>
                    <option value="none" {{ old('calc_rounding_type', $settings->calc_rounding_type ?? '') === 'none' ? 'selected' : '' }}>Exact Decimals (Cents)</option>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label small fw-bold text-secondary">CTA Button Text</label>
                <input type="text" name="calc_cta_text" value="{{ old('calc_cta_text', $settings->calc_cta_text ?? 'Proceed with Application') }}" class="form-control bg-light">
            </div>

            <!-- Homepage Location & Support Box Section -->
            <div class="col-12 pt-2">
                <h6 class="fw-bold text-primary border-bottom pb-2 mb-0"><i class="bi bi-geo-alt me-1"></i> Homepage Location & Support Section</h6>
            </div>

            <div class="col-md-6">
                <label class="form-label small fw-bold text-secondary">Location Section Heading</label>
                <input type="text" name="location_heading" value="{{ old('location_heading', $settings->location_heading ?? 'Headquarters & Branch Network') }}" class="form-control bg-light">
            </div>

            <div class="col-md-6">
                <label class="form-label small fw-bold text-secondary">Location Subtitle / Description</label>
                <input type="text" name="location_description" value="{{ old('location_description', $settings->location_description ?? 'Visit any of our branch offices for counter disbursements, deposits, and officer guidance.') }}" class="form-control bg-light">
            </div>

            <div class="col-md-4">
                <label class="form-label small fw-bold text-secondary">Support Box Title</label>
                <input type="text" name="support_box_title" value="{{ old('support_box_title', $settings->support_box_title ?? 'Direct Inquiries & Assistance') }}" class="form-control bg-light">
            </div>

            <div class="col-md-4">
                <label class="form-label small fw-bold text-secondary">Support Box Button Text</label>
                <input type="text" name="support_box_button_text" value="{{ old('support_box_button_text', $settings->support_box_button_text ?? 'Contact Support Team') }}" class="form-control bg-light">
            </div>

            <div class="col-md-4">
                <label class="form-label small fw-bold text-secondary">Support Box Button URL</label>
                <input type="text" name="support_box_button_url" value="{{ old('support_box_button_url', $settings->support_box_button_url ?? '/contact') }}" class="form-control bg-light">
            </div>

            <!-- Contact Details -->
            <div class="col-12 pt-2">
                <h6 class="fw-bold text-primary border-bottom pb-2 mb-0"><i class="bi bi-envelope-paper me-1"></i> Public Contact Information</h6>
            </div>

            <div class="col-md-6">
                <label class="form-label small fw-bold text-secondary">Contact Phone Number</label>
                <input type="text" name="phone" value="{{ old('phone', $settings->phone) }}" class="form-control bg-light" placeholder="+1 (800) 555-0199">
            </div>

            <div class="col-md-6">
                <label class="form-label small fw-bold text-secondary">Contact Email Address</label>
                <input type="email" name="email" value="{{ old('email', $settings->email) }}" class="form-control bg-light" placeholder="info@tgmicrofinance.org">
            </div>

            <div class="col-12">
                <label class="form-label small fw-bold text-secondary">Head Office Physical Address</label>
                <textarea name="address" rows="2" class="form-control bg-light" placeholder="123 Financial Plaza, Suite 400, Capital City">{{ old('address', $settings->address) }}</textarea>
            </div>

            <!-- Social Links -->
            <div class="col-12 pt-2">
                <h6 class="fw-bold text-primary border-bottom pb-2 mb-0"><i class="bi bi-share me-1"></i> Social Media Links</h6>
            </div>

            <div class="col-md-4">
                <label class="form-label small fw-bold text-secondary"><i class="bi bi-facebook text-primary me-1"></i> Facebook URL</label>
                <input type="url" name="social_links[facebook]" value="{{ old('social_links.facebook', $settings->social_links['facebook'] ?? '') }}" class="form-control bg-light" placeholder="https://facebook.com/tgmicrofinance">
            </div>

            <div class="col-md-4">
                <label class="form-label small fw-bold text-secondary"><i class="bi bi-twitter-x me-1"></i> X (Twitter) URL</label>
                <input type="url" name="social_links[twitter]" value="{{ old('social_links.twitter', $settings->social_links['twitter'] ?? '') }}" class="form-control bg-light" placeholder="https://twitter.com/tgmicrofinance">
            </div>

            <div class="col-md-4">
                <label class="form-label small fw-bold text-secondary"><i class="bi bi-linkedin text-info me-1"></i> LinkedIn URL</label>
                <input type="url" name="social_links[linkedin]" value="{{ old('social_links.linkedin', $settings->social_links['linkedin'] ?? '') }}" class="form-control bg-light" placeholder="https://linkedin.com/company/tgmicrofinance">
            </div>

            <div class="col-md-6">
                <label class="form-label small fw-bold text-secondary"><i class="bi bi-instagram text-danger me-1"></i> Instagram URL</label>
                <input type="url" name="social_links[instagram]" value="{{ old('social_links.instagram', $settings->social_links['instagram'] ?? '') }}" class="form-control bg-light" placeholder="https://instagram.com/tgmicrofinance">
            </div>

            <div class="col-md-6">
                <label class="form-label small fw-bold text-secondary"><i class="bi bi-youtube text-danger me-1"></i> YouTube URL</label>
                <input type="url" name="social_links[youtube]" value="{{ old('social_links.youtube', $settings->social_links['youtube'] ?? '') }}" class="form-control bg-light" placeholder="https://youtube.com/@tgmicrofinance">
            </div>

            <!-- Footer Text -->
            <div class="col-12 pt-2">
                <h6 class="fw-bold text-primary border-bottom pb-2 mb-0"><i class="bi bi-layout-sidebar-reverse me-1"></i> Footer Copyright & Disclaimers</h6>
            </div>

            <div class="col-12">
                <label class="form-label small fw-bold text-secondary">Footer Text / Copyright Notice</label>
                <textarea name="footer_text" rows="3" class="form-control bg-light" placeholder="© 2026 TG Microfinance ERP. All rights reserved.">{{ old('footer_text', $settings->footer_text) }}</textarea>
            </div>

            <div class="col-12 pt-3 border-top d-flex gap-2">
                <button type="submit" class="btn btn-primary rounded-pill px-4 py-2 fw-bold shadow-sm">
                    <i class="bi bi-check-circle me-1"></i> Save Website Settings
                </button>
            </div>
        </div>
    </form>
</x-ui.card>
@endsection
