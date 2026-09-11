@extends('layouts.admin')

@section('title', 'System Settings - ' . config('app.name'))

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1"><i class="bi bi-gear-wide-connected text-primary me-2"></i>System & Operational Settings</h4>
        <p class="text-muted small mb-0">Configure global white-label system branding, logos, microfinance loan charges, and operational parameters.</p>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger rounded-3 shadow-sm mb-4">
        <h6 class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-1"></i> Validation Errors:</h6>
        <ul class="mb-0 small ps-3">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="row g-4">
    <!-- 1. SYSTEM BRANDING & WHITE-LABEL SETTINGS CARD -->
    <div class="col-12 col-lg-8">
        <x-ui.card class="p-4 shadow-sm border-0 bg-white mb-4">
            <div class="d-flex align-items-center justify-content-between border-bottom pb-3 mb-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="bg-primary-subtle text-primary rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                        <i class="bi bi-palette fs-5"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold text-dark mb-0 font-heading">System Branding Settings</h6>
                        <small class="text-muted">Configure company name, main logo, icon, and favicon displayed across the ERP</small>
                    </div>
                </div>
                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill">White-Label System</span>
            </div>

            <div class="alert alert-light border rounded-3 small mb-4">
                <i class="bi bi-info-circle-fill me-1 text-primary"></i>
                These settings control the name, logo, logo icon, and favicon displayed throughout the entire ERP (browser title, admin sidebar, login screens, reports, print layouts, and public views).
            </div>

            <form action="{{ route('admin.system.settings.update-branding') }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="row g-4 mb-4">
                    <!-- Application / Company Name -->
                    <div class="col-12">
                        <label class="form-label fw-bold text-dark"><i class="bi bi-building text-primary me-1"></i> System Name / Company Name <span class="text-danger">*</span></label>
                        <input type="text" name="company_name" value="{{ old('company_name', $settings->company_name ?: config('app.name')) }}" class="form-control form-control-lg bg-light fw-bold text-dark" placeholder="e.g. Grihalaxmi Finance" required>
                        <small class="text-muted">The primary system title used in page headers, reports, salary slips, and emails.</small>
                    </div>

                    <!-- Main Logo Upload & Preview -->
                    <div class="col-12">
                        <div class="p-3 bg-light rounded-3 border">
                            <label class="form-label fw-bold text-dark mb-1"><i class="bi bi-image text-primary me-1"></i> Main Logo</label>
                            <p class="text-muted extra-small mb-2">Displayed on Admin sidebar top, mobile header, login page, report headers, and website navbar. Recommended dimensions: max 400×120px (PNG, JPG, SVG, WebP, max 2MB).</p>
                            
                            <div class="row align-items-center g-3">
                                <div class="col-md-7">
                                    <input type="file" name="logo" class="form-control bg-white" accept="image/*">
                                </div>
                                <div class="col-md-5">
                                    <div class="p-2 bg-white border rounded d-flex align-items-center justify-content-between">
                                        <div class="d-flex align-items-center gap-2">
                                            <img src="{{ $branding->logo_url }}" alt="Main Logo Preview" style="max-height: 38px; max-width: 140px; object-fit: contain;" class="rounded">
                                            <span class="small text-muted">Current Logo</span>
                                        </div>
                                        @if($settings->logo)
                                            <div class="form-check ms-2">
                                                <input class="form-check-input" type="checkbox" name="remove_logo" value="1" id="removeLogo">
                                                <label class="form-check-label extra-small text-danger fw-bold" for="removeLogo">Remove</label>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Logo Icon Upload & Preview -->
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded-3 border h-100">
                            <label class="form-label fw-bold text-dark mb-1"><i class="bi bi-app-indicator text-primary me-1"></i> Logo Icon (Square Mark)</label>
                            <p class="text-muted extra-small mb-2">Used in collapsed sidebar, mobile app header, and compact icons. Recommended: 512×512px square PNG/SVG.</p>
                            
                            <input type="file" name="logo_icon" class="form-control bg-white mb-2" accept="image/*">
                            
                            <div class="p-2 bg-white border rounded d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center gap-2">
                                    <img src="{{ $branding->logo_icon_url }}" alt="Logo Icon Preview" style="max-height: 32px; max-width: 32px; object-fit: contain;" class="rounded">
                                    <span class="small text-muted">Current Icon</span>
                                </div>
                                @if($settings->logo_icon)
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="remove_logo_icon" value="1" id="removeLogoIcon">
                                        <label class="form-check-label extra-small text-danger fw-bold" for="removeLogoIcon">Remove</label>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Favicon Upload & Preview -->
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded-3 border h-100">
                            <label class="form-label fw-bold text-dark mb-1"><i class="bi bi-window text-primary me-1"></i> Favicon</label>
                            <p class="text-muted extra-small mb-2">Displayed in browser address bar and bookmark tabs. Recommended: 32×32px or 64×64px ICO/PNG.</p>
                            
                            <input type="file" name="favicon" class="form-control bg-white mb-2" accept="image/*,.ico">
                            
                            <div class="p-2 bg-white border rounded d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center gap-2">
                                    <img src="{{ $branding->favicon_url }}" alt="Favicon Preview" style="max-height: 24px; max-width: 24px; object-fit: contain;" class="rounded">
                                    <span class="small text-muted">Current Favicon</span>
                                </div>
                                @if($settings->favicon)
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="remove_favicon" value="1" id="removeFavicon">
                                        <label class="form-check-label extra-small text-danger fw-bold" for="removeFavicon">Remove</label>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary rounded-pill px-4 py-2 fw-bold shadow-sm">
                        <i class="bi bi-check-circle me-1"></i> Save System Branding
                    </button>
                </div>
            </form>
        </x-ui.card>

        <!-- 2. COMPANY PROFILE & REGISTRATION INFORMATION CARD -->
        <x-ui.card class="p-4 shadow-sm border-0 bg-white mb-4">
            <div class="d-flex align-items-center justify-content-between border-bottom pb-3 mb-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="bg-info-subtle text-info rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                        <i class="bi bi-building-gear fs-5"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold text-dark mb-0 font-heading">Company Profile & Legal Registration</h6>
                        <small class="text-muted">Single source of truth for invoices, receipts, reports, print templates, and legal disclosures</small>
                    </div>
                </div>
                <span class="badge bg-info-subtle text-info border border-info-subtle rounded-pill">Invoices & Reports Header</span>
            </div>

            <form action="{{ route('admin.system.settings.update-company-profile') }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row g-3 mb-4">
                    <!-- Basic Information -->
                    <div class="col-12"><h6 class="fw-bold text-primary border-bottom pb-1 mb-2 extra-small text-uppercase tracking-wider">Basic Legal Profile</h6></div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold text-dark small">Display / Operating Name <span class="text-danger">*</span></label>
                        <input type="text" name="company_name" value="{{ old('company_name', $settings->company_name) }}" class="form-control bg-light" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold text-dark small">Full Legal Registered Name</label>
                        <input type="text" name="legal_name" value="{{ old('legal_name', $settings->legal_name) }}" class="form-control" placeholder="e.g. Grihalaxmi Microfinance Private Limited">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-bold text-dark small">Company Type / Sector</label>
                        <input type="text" name="company_type" value="{{ old('company_type', $settings->company_type) }}" class="form-control" placeholder="e.g. NBFC-MFI / Private Limited">
                    </div>

                    <div class="col-md-8">
                        <label class="form-label fw-bold text-dark small">Company Tagline / Motto</label>
                        <input type="text" name="tagline" value="{{ old('tagline', $settings->tagline) }}" class="form-control" placeholder="e.g. Empowering Financial Inclusion & Growth">
                    </div>

                    <!-- Tax & Registration Details -->
                    <div class="col-12 mt-4"><h6 class="fw-bold text-primary border-bottom pb-1 mb-2 extra-small text-uppercase tracking-wider">Tax & Regulatory Identifiers</h6></div>

                    <div class="col-md-3">
                        <label class="form-label fw-bold text-dark small">GSTIN Number</label>
                        <input type="text" name="gstin" value="{{ old('gstin', $settings->gstin) }}" class="form-control font-monospace text-uppercase" placeholder="19AAAAA0000A1Z5">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-bold text-dark small">PAN Number</label>
                        <input type="text" name="pan" value="{{ old('pan', $settings->pan) }}" class="form-control font-monospace text-uppercase" placeholder="ABCDE1234F">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-bold text-dark small">CIN Number</label>
                        <input type="text" name="cin" value="{{ old('cin', $settings->cin) }}" class="form-control font-monospace text-uppercase" placeholder="U65999WB2026PTC000000">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-bold text-dark small">Registration Reg. No.</label>
                        <input type="text" name="registration_number" value="{{ old('registration_number', $settings->registration_number) }}" class="form-control font-monospace" placeholder="REG-2026-9874">
                    </div>

                    <!-- Contact Details -->
                    <div class="col-12 mt-4"><h6 class="fw-bold text-primary border-bottom pb-1 mb-2 extra-small text-uppercase tracking-wider">Official Contact Information</h6></div>

                    <div class="col-md-3">
                        <label class="form-label fw-bold text-dark small">Primary Phone</label>
                        <input type="text" name="phone" value="{{ old('phone', $settings->phone) }}" class="form-control font-monospace" placeholder="+91 9876543210">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-bold text-dark small">Alternate Phone</label>
                        <input type="text" name="alternate_phone" value="{{ old('alternate_phone', $settings->alternate_phone) }}" class="form-control font-monospace" placeholder="+91 33 22110099">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-bold text-dark small">Official Email</label>
                        <input type="email" name="email" value="{{ old('email', $settings->email) }}" class="form-control" placeholder="info@grihalaxmifinance.com">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-bold text-dark small">Website URL</label>
                        <input type="text" name="website" value="{{ old('website', $settings->website) }}" class="form-control" placeholder="https://grihalaxmifinance.com">
                    </div>

                    <!-- Registered Address -->
                    <div class="col-12 mt-4"><h6 class="fw-bold text-primary border-bottom pb-1 mb-2 extra-small text-uppercase tracking-wider">Registered Head Office Address</h6></div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold text-dark small">Address Line 1</label>
                        <input type="text" name="address_line_1" value="{{ old('address_line_1', $settings->address_line_1 ?: $settings->address) }}" class="form-control" placeholder="Building, Suite / Floor, Street">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold text-dark small">Address Line 2</label>
                        <input type="text" name="address_line_2" value="{{ old('address_line_2', $settings->address_line_2) }}" class="form-control" placeholder="Landmark, Area / Sector">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-bold text-dark small">City</label>
                        <input type="text" name="city" value="{{ old('city', $settings->city) }}" class="form-control" placeholder="Kolkata">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-bold text-dark small">District</label>
                        <input type="text" name="district" value="{{ old('district', $settings->district) }}" class="form-control" placeholder="Kolkata">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-bold text-dark small">State</label>
                        <input type="text" name="state" value="{{ old('state', $settings->state) }}" class="form-control" placeholder="West Bengal">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-bold text-dark small">PIN / Postal Code</label>
                        <input type="text" name="pin_code" value="{{ old('pin_code', $settings->pin_code) }}" class="form-control font-monospace" placeholder="700001">
                    </div>
                </div>

                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-info text-white rounded-pill px-4 py-2 fw-bold shadow-sm">
                        <i class="bi bi-check-circle me-1"></i> Save Company Profile
                    </button>
                </div>
            </form>
        </x-ui.card>

        <!-- 2. LOAN CHARGES & UPFRONT FEES SETTINGS CARD -->
        <x-ui.card class="p-4 shadow-sm border-0 bg-white">
            <div class="d-flex align-items-center justify-content-between border-bottom pb-3 mb-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="bg-primary-subtle text-primary rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                        <i class="bi bi-percent fs-5"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold text-dark mb-0 font-heading">Loan Charges / Fees Settings</h6>
                        <small class="text-muted">Configure default Processing Fee and Insurance Fee rates</small>
                    </div>
                </div>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill">Upfront Policy</span>
            </div>

            <form action="{{ route('admin.system.settings.update-loan-charges') }}" method="POST">
                @csrf
                @method('PUT')

                <div class="alert alert-info border border-info-subtle rounded-3 small mb-4">
                    <i class="bi bi-info-circle-fill me-1 text-info"></i>
                    <strong>Upfront Collection Business Rule:</strong> These charges are collected <strong>UPFRONT</strong> during loan sanction and are <strong>NOT included in the EMI principal amount</strong>.
                </div>

                <div class="row g-4 mb-4">
                    <!-- Processing Fee Config -->
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded-3 border">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <label class="fw-bold text-dark mb-0"><i class="bi bi-receipt-cutoff text-primary me-1"></i> Processing Fee (%)</label>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="loan_processing_fee_enabled" value="1" id="proFeeEnabled" {{ old('loan_processing_fee_enabled', $settings->loan_processing_fee_enabled ?? true) ? 'checked' : '' }}>
                                    <label class="form-check-label small fw-bold text-secondary" for="proFeeEnabled">Enable Fee</label>
                                </div>
                            </div>
                            <div class="input-group">
                                <input type="number" step="0.01" min="0" max="100" name="loan_processing_fee_percentage" value="{{ old('loan_processing_fee_percentage', number_format($settings->loan_processing_fee_percentage ?? 1.00, 2)) }}" class="form-control bg-white fw-bold font-monospace" placeholder="1.00">
                                <span class="input-group-text bg-white fw-bold">%</span>
                            </div>
                            <small class="text-muted d-block mt-2">Percentage charged on loan principal upfront.</small>
                        </div>
                    </div>

                    <!-- Insurance Fee Config -->
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded-3 border">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <label class="fw-bold text-dark mb-0"><i class="bi bi-shield-check text-success me-1"></i> Insurance Fee (%)</label>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="loan_insurance_enabled" value="1" id="insFeeEnabled" {{ old('loan_insurance_enabled', $settings->loan_insurance_enabled ?? true) ? 'checked' : '' }}>
                                    <label class="form-check-label small fw-bold text-secondary" for="insFeeEnabled">Enable Insurance</label>
                                </div>
                            </div>
                            <div class="input-group">
                                <input type="number" step="0.01" min="0" max="100" name="loan_insurance_percentage" value="{{ old('loan_insurance_percentage', number_format($settings->loan_insurance_percentage ?? 1.00, 2)) }}" class="form-control bg-white fw-bold font-monospace" placeholder="1.00">
                                <span class="input-group-text bg-white fw-bold">%</span>
                            </div>
                            <small class="text-muted d-block mt-2">Percentage charged for borrower insurance coverage upfront.</small>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary rounded-pill px-4 py-2 fw-bold shadow-sm">
                        <i class="bi bi-check-circle me-1"></i> Save Loan Fee Settings
                    </button>
                </div>
            </form>
        </x-ui.card>

        <!-- 4. THEME & APPEARANCE CUSTOMIZATION CARD -->
        <x-ui.card class="p-4 shadow-sm border-0 bg-white mb-4">
            <div class="d-flex align-items-center justify-content-between border-bottom pb-3 mb-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="bg-warning-subtle text-warning rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                        <i class="bi bi-palette-fill fs-5" style="color: #d97706;"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold text-dark mb-0 font-heading">Admin Panel Theme & Visual Appearance</h6>
                        <small class="text-muted">Database-backed white-label theme customization, presets, color pickers, and live preview</small>
                    </div>
                </div>
                <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill" style="color: #b45309 !important;">White-Label Visual Theme</span>
            </div>

            <div class="alert alert-light border rounded-3 small mb-4">
                <i class="bi bi-info-circle-fill me-1 text-primary"></i>
                Select a pre-configured color palette or customize individual colors. Changes apply globally across the Admin Portal and Login screens without modifying code or <code>.env</code> files.
            </div>

            <form action="{{ route('admin.system.settings.update-theme') }}" method="POST" id="themeSettingsForm">
                @csrf
                @method('PUT')

                <input type="hidden" name="theme_preset" id="theme_preset_input" value="{{ old('theme_preset', $theme->theme_preset ?? 'navy_gold') }}">

                <!-- Theme Presets Selection -->
                <div class="mb-4">
                    <label class="form-label fw-bold text-dark mb-2"><i class="bi bi-grid text-primary me-1"></i> Quick Theme Presets</label>
                    <div class="row g-2">
                        @foreach($themePresets as $presetKey => $p)
                            <div class="col-6 col-sm-4 col-md-4 col-lg-2">
                                <div class="p-2 border rounded-3 text-center cursor-pointer preset-card-item {{ ($theme->theme_preset ?? 'navy_gold') === $presetKey ? 'border-primary bg-primary-subtle shadow-sm' : 'bg-light' }}"
                                     onclick="applyPreset('{{ $presetKey }}')"
                                     id="preset_card_{{ $presetKey }}"
                                     style="transition: all 0.2s ease;">
                                    <div class="d-flex align-items-center justify-content-center gap-1 mb-1">
                                        <span style="width: 14px; height: 14px; border-radius: 50%; background-color: {{ $p['primary_color'] }}; display: inline-block; border: 1px solid #ccc;"></span>
                                        <span style="width: 14px; height: 14px; border-radius: 50%; background-color: {{ $p['secondary_color'] }}; display: inline-block; border: 1px solid #ccc;"></span>
                                        <span style="width: 14px; height: 14px; border-radius: 50%; background-color: {{ $p['sidebar_color'] }}; display: inline-block; border: 1px solid #ccc;"></span>
                                    </div>
                                    <span class="extra-small fw-bold d-block text-truncate text-dark">{{ $p['name'] }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Custom Color Pickers Grid -->
                <div class="row g-3 mb-4">
                    <div class="col-12"><h6 class="fw-bold text-primary border-bottom pb-1 mb-2 extra-small text-uppercase tracking-wider">Custom Brand Colors</h6></div>

                    <!-- Primary Color -->
                    <div class="col-6 col-md-4">
                        <label class="form-label fw-bold text-dark extra-small mb-1">Primary Color</label>
                        <div class="input-group input-group-sm">
                            <input type="color" class="form-control form-control-color" id="primary_picker" value="{{ old('primary_color', $theme->primary_color ?? '#0C2340') }}" onchange="syncColorInput('primary_picker', 'primary_color')">
                            <input type="text" name="primary_color" id="primary_color" class="form-control font-monospace text-uppercase" value="{{ old('primary_color', $theme->primary_color ?? '#0C2340') }}" oninput="syncColorPicker('primary_color', 'primary_picker')">
                        </div>
                    </div>

                    <!-- Secondary Color -->
                    <div class="col-6 col-md-4">
                        <label class="form-label fw-bold text-dark extra-small mb-1">Secondary / Gold Accent</label>
                        <div class="input-group input-group-sm">
                            <input type="color" class="form-control form-control-color" id="secondary_picker" value="{{ old('secondary_color', $theme->secondary_color ?? '#C5A059') }}" onchange="syncColorInput('secondary_picker', 'secondary_color')">
                            <input type="text" name="secondary_color" id="secondary_color" class="form-control font-monospace text-uppercase" value="{{ old('secondary_color', $theme->secondary_color ?? '#C5A059') }}" oninput="syncColorPicker('secondary_color', 'secondary_picker')">
                        </div>
                    </div>

                    <!-- Accent Color -->
                    <div class="col-6 col-md-4">
                        <label class="form-label fw-bold text-dark extra-small mb-1">Highlight Accent</label>
                        <div class="input-group input-group-sm">
                            <input type="color" class="form-control form-control-color" id="accent_picker" value="{{ old('accent_color', $theme->accent_color ?? '#D4AF37') }}" onchange="syncColorInput('accent_picker', 'accent_color')">
                            <input type="text" name="accent_color" id="accent_color" class="form-control font-monospace text-uppercase" value="{{ old('accent_color', $theme->accent_color ?? '#D4AF37') }}" oninput="syncColorPicker('accent_color', 'accent_picker')">
                        </div>
                    </div>

                    <!-- Sidebar Background -->
                    <div class="col-6 col-md-4">
                        <label class="form-label fw-bold text-dark extra-small mb-1">Sidebar Background</label>
                        <div class="input-group input-group-sm">
                            <input type="color" class="form-control form-control-color" id="sidebar_picker" value="{{ old('sidebar_color', $theme->sidebar_color ?? '#0C2340') }}" onchange="syncColorInput('sidebar_picker', 'sidebar_color')">
                            <input type="text" name="sidebar_color" id="sidebar_color" class="form-control font-monospace text-uppercase" value="{{ old('sidebar_color', $theme->sidebar_color ?? '#0C2340') }}" oninput="syncColorPicker('sidebar_color', 'sidebar_picker')">
                        </div>
                    </div>

                    <!-- Sidebar Text Color -->
                    <div class="col-6 col-md-4">
                        <label class="form-label fw-bold text-dark extra-small mb-1">Sidebar Text Color</label>
                        <div class="input-group input-group-sm">
                            <input type="color" class="form-control form-control-color" id="sidebar_text_picker" value="{{ old('sidebar_text_color', $theme->sidebar_text_color ?? '#F8FAFC') }}" onchange="syncColorInput('sidebar_text_picker', 'sidebar_text_color')">
                            <input type="text" name="sidebar_text_color" id="sidebar_text_color" class="form-control font-monospace text-uppercase" value="{{ old('sidebar_text_color', $theme->sidebar_text_color ?? '#F8FAFC') }}" oninput="syncColorPicker('sidebar_text_color', 'sidebar_text_picker')">
                        </div>
                    </div>

                    <!-- Sidebar Active Link -->
                    <div class="col-6 col-md-4">
                        <label class="form-label fw-bold text-dark extra-small mb-1">Sidebar Active Link</label>
                        <div class="input-group input-group-sm">
                            <input type="color" class="form-control form-control-color" id="sidebar_active_picker" value="{{ old('sidebar_active_color', $theme->sidebar_active_color ?? '#C5A059') }}" onchange="syncColorInput('sidebar_active_picker', 'sidebar_active_color')">
                            <input type="text" name="sidebar_active_color" id="sidebar_active_color" class="form-control font-monospace text-uppercase" value="{{ old('sidebar_active_color', $theme->sidebar_active_color ?? '#C5A059') }}" oninput="syncColorPicker('sidebar_active_color', 'sidebar_active_picker')">
                        </div>
                    </div>

                    <!-- Header Background -->
                    <div class="col-6 col-md-4">
                        <label class="form-label fw-bold text-dark extra-small mb-1">Header Topbar</label>
                        <div class="input-group input-group-sm">
                            <input type="color" class="form-control form-control-color" id="header_picker" value="{{ old('header_color', $theme->header_color ?? '#FFFFFF') }}" onchange="syncColorInput('header_picker', 'header_color')">
                            <input type="text" name="header_color" id="header_color" class="form-control font-monospace text-uppercase" value="{{ old('header_color', $theme->header_color ?? '#FFFFFF') }}" oninput="syncColorPicker('header_color', 'header_picker')">
                        </div>
                    </div>

                    <!-- Header Text -->
                    <div class="col-6 col-md-4">
                        <label class="form-label fw-bold text-dark extra-small mb-1">Header Text Color</label>
                        <div class="input-group input-group-sm">
                            <input type="color" class="form-control form-control-color" id="header_text_picker" value="{{ old('header_text_color', $theme->header_text_color ?? '#0C2340') }}" onchange="syncColorInput('header_text_picker', 'header_text_color')">
                            <input type="text" name="header_text_color" id="header_text_color" class="form-control font-monospace text-uppercase" value="{{ old('header_text_color', $theme->header_text_color ?? '#0C2340') }}" oninput="syncColorPicker('header_text_color', 'header_text_picker')">
                        </div>
                    </div>

                    <!-- Body Background -->
                    <div class="col-6 col-md-4">
                        <label class="form-label fw-bold text-dark extra-small mb-1">Page Canvas Background</label>
                        <div class="input-group input-group-sm">
                            <input type="color" class="form-control form-control-color" id="body_background_picker" value="{{ old('body_background_color', $theme->body_background_color ?? '#F8FAFC') }}" onchange="syncColorInput('body_background_picker', 'body_background_color')">
                            <input type="text" name="body_background_color" id="body_background_color" class="form-control font-monospace text-uppercase" value="{{ old('body_background_color', $theme->body_background_color ?? '#F8FAFC') }}" oninput="syncColorPicker('body_background_color', 'body_background_picker')">
                        </div>
                    </div>

                    <!-- Card Background -->
                    <div class="col-6 col-md-4">
                        <label class="form-label fw-bold text-dark extra-small mb-1">Card Background</label>
                        <div class="input-group input-group-sm">
                            <input type="color" class="form-control form-control-color" id="card_background_picker" value="{{ old('card_background_color', $theme->card_background_color ?? '#FFFFFF') }}" onchange="syncColorInput('card_background_picker', 'card_background_color')">
                            <input type="text" name="card_background_color" id="card_background_color" class="form-control font-monospace text-uppercase" value="{{ old('card_background_color', $theme->card_background_color ?? '#FFFFFF') }}" oninput="syncColorPicker('card_background_color', 'card_background_picker')">
                        </div>
                    </div>

                    <!-- Border Color -->
                    <div class="col-6 col-md-4">
                        <label class="form-label fw-bold text-dark extra-small mb-1">Border Line Color</label>
                        <div class="input-group input-group-sm">
                            <input type="color" class="form-control form-control-color" id="border_picker" value="{{ old('border_color', $theme->border_color ?? '#E2E8F0') }}" onchange="syncColorInput('border_picker', 'border_color')">
                            <input type="text" name="border_color" id="border_color" class="form-control font-monospace text-uppercase" value="{{ old('border_color', $theme->border_color ?? '#E2E8F0') }}" oninput="syncColorPicker('border_color', 'border_picker')">
                        </div>
                    </div>

                    <!-- Radius & Mode -->
                    <div class="col-6 col-md-4">
                        <label class="form-label fw-bold text-dark extra-small mb-1">Corner Border Radius</label>
                        <select name="button_radius" id="button_radius" class="form-select form-select-sm" onchange="updateLivePreview()">
                            <option value="0px" {{ ($theme->button_radius ?? '0.5rem') === '0px' ? 'selected' : '' }}>Square (0px)</option>
                            <option value="0.375rem" {{ ($theme->button_radius ?? '0.5rem') === '0.375rem' ? 'selected' : '' }}>Slight Rounded (6px)</option>
                            <option value="0.5rem" {{ ($theme->button_radius ?? '0.5rem') === '0.5rem' ? 'selected' : '' }}>Medium (8px)</option>
                            <option value="0.75rem" {{ ($theme->button_radius ?? '0.5rem') === '0.75rem' ? 'selected' : '' }}>Rounded (12px)</option>
                            <option value="9999px" {{ ($theme->button_radius ?? '0.5rem') === '9999px' ? 'selected' : '' }}>Full Pill</option>
                        </select>
                    </div>

                    <div class="col-6 col-md-4">
                        <label class="form-label fw-bold text-dark extra-small mb-1">Theme Display Mode</label>
                        <select name="theme_mode" id="theme_mode" class="form-select form-select-sm" onchange="updateLivePreview()">
                            <option value="light" {{ ($theme->theme_mode ?? 'light') === 'light' ? 'selected' : '' }}>Light Mode</option>
                            <option value="dark" {{ ($theme->theme_mode ?? 'light') === 'dark' ? 'selected' : '' }}>Dark Mode</option>
                            <option value="system" {{ ($theme->theme_mode ?? 'light') === 'system' ? 'selected' : '' }}>System Auto</option>
                        </select>
                    </div>
                </div>

                <!-- LIVE INTERACTIVE PREVIEW BOX -->
                <div class="mb-4">
                    <label class="form-label fw-bold text-dark mb-2"><i class="bi bi-eye text-primary me-1"></i> Real-Time Live UI Preview</label>
                    <div class="border rounded-3 p-3 bg-light overflow-hidden shadow-sm position-relative" id="live_preview_box" style="min-height: 220px; transition: all 0.2s ease;">
                        
                        <div class="d-flex rounded border overflow-hidden shadow-sm" style="height: 200px;">
                            <!-- Preview Sidebar -->
                            <div class="p-2 d-flex flex-column justify-content-between" id="prev_sidebar" style="width: 140px; background-color: {{ $theme->sidebar_color }}; color: {{ $theme->sidebar_text_color }};">
                                <div>
                                    <div class="fw-bold extra-small text-truncate mb-2 border-bottom pb-1 opacity-75">{{ $branding->system_name }}</div>
                                    <div class="p-1 rounded mb-1 extra-small fw-bold d-flex align-items-center gap-1" id="prev_active_item" style="color: {{ $theme->sidebar_active_color }}; background: rgba(255,255,255,0.1);">
                                        <i class="bi bi-grid-fill"></i> Dashboard
                                    </div>
                                    <div class="p-1 extra-small opacity-75 d-flex align-items-center gap-1"><i class="bi bi-people"></i> Members</div>
                                    <div class="p-1 extra-small opacity-75 d-flex align-items-center gap-1"><i class="bi bi-bank"></i> Loans</div>
                                </div>
                                <div class="extra-small opacity-50 font-monospace">v2.0 ERP</div>
                            </div>

                            <!-- Preview Main Body -->
                            <div class="flex-fill d-flex flex-column" id="prev_body" style="background-color: {{ $theme->body_background_color }};">
                                <!-- Preview Header -->
                                <div class="p-2 border-bottom d-flex align-items-center justify-content-between" id="prev_header" style="background-color: {{ $theme->header_color }}; color: {{ $theme->header_text_color }}; border-color: {{ $theme->border_color }};">
                                    <span class="fw-bold extra-small"><i class="bi bi-speedometer2 me-1"></i> Dashboard Preview</span>
                                    <span class="badge extra-small text-white" id="prev_badge" style="background-color: {{ $theme->secondary_color }};">Active Admin</span>
                                </div>

                                <!-- Preview Canvas Card -->
                                <div class="p-2 flex-fill">
                                    <div class="p-2 border shadow-sm" id="prev_card" style="background-color: {{ $theme->card_background_color }}; border-color: {{ $theme->border_color }}; border-radius: {{ $theme->button_radius }};">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="extra-small fw-bold text-dark">Sample Module</span>
                                            <button type="button" class="btn btn-sm text-white px-2 py-0 extra-small fw-bold" id="prev_btn_primary" style="background-color: {{ $theme->primary_color }}; border-radius: {{ $theme->button_radius }};">Action</button>
                                        </div>
                                        <div class="progress mb-2" style="height: 6px;">
                                            <div class="progress-bar" id="prev_progress" role="progressbar" style="width: 70%; background-color: {{ $theme->secondary_color }};"></div>
                                        </div>
                                        <div class="extra-small text-muted d-flex justify-content-between">
                                            <span>Collections: 98%</span>
                                            <span class="fw-bold text-success">Healthy</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center">
                    <button type="button" class="btn btn-outline-danger rounded-pill px-3 py-2 extra-small fw-bold" onclick="confirmResetTheme()">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Reset to Default Theme
                    </button>

                    <button type="submit" class="btn btn-warning text-dark rounded-pill px-4 py-2 fw-bold shadow-sm" style="background-color: #f59e0b; border: none;">
                        <i class="bi bi-check-circle me-1"></i> Save Theme Settings
                    </button>
                </div>
            </form>
        </x-ui.card>

        <!-- Form for Resetting Theme -->
        <form action="{{ route('admin.system.settings.reset-theme') }}" method="POST" id="resetThemeForm" class="d-none">
            @csrf
        </form>
    </div>

    @push('scripts')
    <script>
        const themePresetsObj = @json($themePresets);

        function applyPreset(presetKey) {
            const p = themePresetsObj[presetKey];
            if (!p) return;

            document.getElementById('theme_preset_input').value = presetKey;
            
            document.querySelectorAll('.preset-card-item').forEach(el => {
                el.classList.remove('border-primary', 'bg-primary-subtle', 'shadow-sm');
                el.classList.add('bg-light');
            });
            const activeCard = document.getElementById('preset_card_' + presetKey);
            if (activeCard) {
                activeCard.classList.remove('bg-light');
                activeCard.classList.add('border-primary', 'bg-primary-subtle', 'shadow-sm');
            }

            const fieldKeys = [
                'primary_color', 'secondary_color', 'accent_color', 'sidebar_color',
                'sidebar_text_color', 'sidebar_active_color', 'header_color', 'header_text_color',
                'body_background_color', 'card_background_color', 'border_color'
            ];

            fieldKeys.forEach(key => {
                if (p[key]) {
                    const textInput = document.getElementById(key);
                    const pickerInput = document.getElementById(key.replace('_color', '_picker'));
                    if (textInput) textInput.value = p[key].toUpperCase();
                    if (pickerInput) pickerInput.value = p[key];
                }
            });

            if (p['button_radius']) {
                document.getElementById('button_radius').value = p['button_radius'];
            }
            if (p['theme_mode']) {
                document.getElementById('theme_mode').value = p['theme_mode'];
            }

            updateLivePreview();
        }

        function syncColorInput(pickerId, textId) {
            const picker = document.getElementById(pickerId);
            const text = document.getElementById(textId);
            if (picker && text) {
                text.value = picker.value.toUpperCase();
                updateLivePreview();
            }
        }

        function syncColorPicker(textId, pickerId) {
            const text = document.getElementById(textId);
            const picker = document.getElementById(pickerId);
            if (text && picker && /^#(?:[0-9a-fA-F]{3}){1,2}$/.test(text.value)) {
                picker.value = text.value;
                updateLivePreview();
            }
        }

        function updateLivePreview() {
            const primary = document.getElementById('primary_color').value;
            const secondary = document.getElementById('secondary_color').value;
            const sidebar = document.getElementById('sidebar_color').value;
            const sidebarText = document.getElementById('sidebar_text_color').value;
            const sidebarActive = document.getElementById('sidebar_active_color').value;
            const header = document.getElementById('header_color').value;
            const headerText = document.getElementById('header_text_color').value;
            const bodyBg = document.getElementById('body_background_color').value;
            const cardBg = document.getElementById('card_background_color').value;
            const border = document.getElementById('border_color').value;
            const radius = document.getElementById('button_radius').value;

            document.getElementById('prev_sidebar').style.backgroundColor = sidebar;
            document.getElementById('prev_sidebar').style.color = sidebarText;
            document.getElementById('prev_active_item').style.color = sidebarActive;
            
            document.getElementById('prev_body').style.backgroundColor = bodyBg;
            document.getElementById('prev_header').style.backgroundColor = header;
            document.getElementById('prev_header').style.color = headerText;
            document.getElementById('prev_header').style.borderColor = border;

            document.getElementById('prev_card').style.backgroundColor = cardBg;
            document.getElementById('prev_card').style.borderColor = border;
            document.getElementById('prev_card').style.borderRadius = radius;

            document.getElementById('prev_btn_primary').style.backgroundColor = primary;
            document.getElementById('prev_btn_primary').style.borderRadius = radius;

            document.getElementById('prev_badge').style.backgroundColor = secondary;
            document.getElementById('prev_progress').style.backgroundColor = secondary;
        }

        function confirmResetTheme() {
            if (confirm('Are you sure you want to reset the Admin Panel visual theme to default Navy Gold?')) {
                document.getElementById('resetThemeForm').submit();
            }
        }
    </script>
    @endpush

    <!-- 3. QUICK NAVIGATION & BRANDING POLICY SUMMARY -->
    <div class="col-12 col-lg-4">
        <x-ui.card class="p-4 shadow-sm border-0 bg-white mb-4">
            <h6 class="fw-bold text-dark border-bottom pb-2 mb-3"><i class="bi bi-patch-check-fill text-primary me-1"></i> White-Label System Architecture</h6>
            <ul class="list-unstyled small text-muted mb-0">
                <li class="mb-2.5 d-flex gap-2">
                    <i class="bi bi-check-circle-fill text-success flex-shrink-0 mt-0.5"></i>
                    <span><strong>Database Authoritative:</strong> System branding stored in database overrides `.env` and hardcoded fallbacks dynamically.</span>
                </li>
                <li class="mb-2.5 d-flex gap-2">
                    <i class="bi bi-check-circle-fill text-success flex-shrink-0 mt-0.5"></i>
                    <span><strong>Instant Cache Invalidation:</strong> Updating branding immediately reflects without restarting the server or editing config files.</span>
                </li>
                <li class="mb-2.5 d-flex gap-2">
                    <i class="bi bi-check-circle-fill text-success flex-shrink-0 mt-0.5"></i>
                    <span><strong>Universal Propagation:</strong> Applied automatically across Admin Portal, Authentication screens, Reports, Salary Slips, and Website.</span>
                </li>
                <li class="d-flex gap-2">
                    <i class="bi bi-check-circle-fill text-success flex-shrink-0 mt-0.5"></i>
                    <span><strong>Safe File Handling:</strong> File uploads are sanitized and validated against safe image MIME types and size limits.</span>
                </li>
            </ul>
        </x-ui.card>

        <x-ui.card class="p-4 shadow-sm border-0 bg-white">
            <h6 class="fw-bold text-dark border-bottom pb-2 mb-3"><i class="bi bi-shield-lock text-warning me-1"></i> Fee Policy Rules</h6>
            <ul class="list-unstyled small text-muted mb-0">
                <li class="mb-2.5 d-flex gap-2">
                    <i class="bi bi-check-circle-fill text-success flex-shrink-0 mt-0.5"></i>
                    <span><strong>Historical Application Protection:</strong> Changing settings does not alter previously created loan applications.</span>
                </li>
                <li class="mb-2.5 d-flex gap-2">
                    <i class="bi bi-check-circle-fill text-success flex-shrink-0 mt-0.5"></i>
                    <span><strong>Disbursement Gatekeeper:</strong> Loan disbursement remains locked until required upfront charges are paid.</span>
                </li>
                <li class="mb-2.5 d-flex gap-2">
                    <i class="bi bi-check-circle-fill text-success flex-shrink-0 mt-0.5"></i>
                    <span><strong>EMI Exclusion:</strong> Charges are collected upfront and do not inflate the borrower's EMI principal balance.</span>
                </li>
                <li class="d-flex gap-2">
                    <i class="bi bi-check-circle-fill text-success flex-shrink-0 mt-0.5"></i>
                    <span><strong>Accounting Integrity:</strong> Collections are automatically credited to Processing Fee Income (4210) & Insurance Income (4220).</span>
                </li>
            </ul>
        </x-ui.card>
    </div>
</div>
@endsection
