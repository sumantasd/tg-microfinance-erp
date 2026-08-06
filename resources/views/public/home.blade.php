@extends('layouts.public')

@section('title', ($settings->company_name ?? 'TG Microfinance ERP') . ' - Empowering Financial Independence')
@section('meta_description', 'Empowering small businesses, micro-entrepreneurs, and individuals with fast loans, high-yield savings, and enterprise financial services.')

@section('content')

<!-- 1. HERO BANNER SECTION (CMS DYNAMIC SLIDER / HERO) -->
<section class="hero-section position-relative">
    <div class="container-xl">
        <div class="row align-items-center g-4 g-lg-5 py-3 py-md-4">
            <div class="{{ ($settings->calc_enabled ?? true) ? 'col-lg-7' : 'col-12' }}">
                @if(isset($banners) && $banners->count() > 0)
                    @if($banners->count() > 1)
                        <!-- Multi-Banner Carousel -->
                        <div id="cmsHeroCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel" data-bs-interval="6000">
                            <div class="carousel-indicators mb-0 justify-content-start ms-0" style="bottom: -25px;">
                                @foreach($banners as $index => $banner)
                                    <button type="button" data-bs-target="#cmsHeroCarousel" data-bs-slide-to="{{ $index }}" class="{{ $index === 0 ? 'active' : '' }}" aria-current="{{ $index === 0 ? 'true' : 'false' }}" aria-label="Slide {{ $index + 1 }}"></button>
                                @endforeach
                            </div>
                            <div class="carousel-inner">
                                @foreach($banners as $index => $banner)
                                    <div class="carousel-item {{ $index === 0 ? 'active' : '' }}">
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle fs-6 px-3 py-1.5 rounded-pill fw-semibold mb-3 shadow-sm d-inline-flex align-items-center gap-1 text-wrap">
                                            <i class="bi bi-shield-check me-1"></i> {{ $settings->company_name ?? 'TG Microfinance' }}
                                        </span>
                                        <h1 class="hero-title display-4 mb-3 text-white">{{ $banner->title }}</h1>
                                        @if($banner->subtitle)
                                            <p class="lead mb-4 text-light opacity-90">
                                                {{ $banner->subtitle }}
                                            </p>
                                        @endif

                                        @if($banner->image_url)
                                            <div class="mb-4">
                                                <img src="{{ $banner->image_url }}" alt="{{ $banner->title }}" class="img-fluid rounded-4 shadow-lg border border-secondary" style="max-height: 320px; width: 100%; object-fit: cover;">
                                            </div>
                                        @endif

                                        <div class="d-flex flex-wrap gap-3 mb-4">
                                            @if($banner->button_text)
                                                <a href="{{ $banner->button_url ?? url('/apply-loan') }}" class="btn btn-primary btn-lg rounded-pill px-4 py-3 fw-bold shadow-lg d-flex align-items-center gap-2">
                                                    <i class="bi bi-file-earmark-text fs-5"></i> {{ $banner->button_text }}
                                                </a>
                                            @endif
                                            <a href="{{ url('/products/loan') }}" class="btn btn-outline-light btn-lg rounded-pill px-4 py-3 fw-semibold d-flex align-items-center gap-2">
                                                <span>Explore Products</span>
                                                <i class="bi bi-arrow-right"></i>
                                            </a>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <!-- Single Active Banner -->
                        @php $banner = $banners->first(); @endphp
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle fs-6 px-3 py-1.5 rounded-pill fw-semibold mb-3 shadow-sm d-inline-flex align-items-center gap-1 text-wrap">
                            <i class="bi bi-shield-check me-1"></i> {{ $settings->company_name ?? 'TG Microfinance' }}
                        </span>
                        <h1 class="hero-title display-4 mb-3 text-white">{{ $banner->title }}</h1>
                        @if($banner->subtitle)
                            <p class="lead mb-4 text-light opacity-90">
                                {{ $banner->subtitle }}
                            </p>
                        @endif

                        @if($banner->image_url)
                            <div class="mb-4">
                                <img src="{{ $banner->image_url }}" alt="{{ $banner->title }}" class="img-fluid rounded-4 shadow-lg border border-secondary" style="max-height: 320px; width: 100%; object-fit: cover;">
                            </div>
                        @endif

                        <div class="d-flex flex-wrap gap-3">
                            <a href="{{ $banner->button_url ?? url('/apply-loan') }}" class="btn btn-primary btn-lg rounded-pill px-4 py-3 fw-bold shadow-lg d-flex align-items-center gap-2">
                                <i class="bi bi-file-earmark-text fs-5"></i> {{ $banner->button_text ?? 'Apply for Loan' }}
                            </a>
                            <a href="{{ url('/products/loan') }}" class="btn btn-outline-light btn-lg rounded-pill px-4 py-3 fw-semibold d-flex align-items-center gap-2">
                                <span>Explore Loan Products</span>
                                <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                    @endif
                @else
                    <!-- Fallback Default Hero Content -->
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle fs-6 px-3 py-1.5 rounded-pill fw-semibold mb-3 shadow-sm d-inline-flex align-items-center gap-1 text-wrap">
                        <i class="bi bi-shield-check me-1"></i> Certified Enterprise Microfinance Institution
                    </span>
                    <h1 class="hero-title display-4 mb-3 text-white">Empowering Small Businesses & Micro-Entrepreneurs</h1>
                    <p class="lead mb-4 text-light opacity-90">
                        Fast, accessible credit solutions, flexible savings schemes, and digital branch operations designed for community growth and financial independence.
                    </p>
                    <div class="d-flex flex-wrap gap-3">
                        <a href="{{ url('/apply-loan') }}" class="btn btn-primary btn-lg rounded-pill px-4 py-3 fw-bold shadow-lg d-flex align-items-center gap-2">
                            <i class="bi bi-file-earmark-text fs-5"></i> Apply for Loan
                        </a>
                        <a href="{{ url('/products/loan') }}" class="btn btn-outline-light btn-lg rounded-pill px-4 py-3 fw-semibold d-flex align-items-center gap-2">
                            <span>Explore Loan Products</span>
                            <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                @endif
            </div>

            @if($settings->calc_enabled ?? true)
                <!-- DYNAMIC HOMEPAGE LOAN RATE ESTIMATOR CALCULATOR WIDGET (CMS DRIVEN) -->
                <div class="col-lg-5">
                    <div class="card border-0 shadow-lg rounded-4 p-4 text-dark bg-white tg-hover-lift">
                        <div class="d-flex align-items-center gap-3 mb-3 border-bottom pb-3">
                            <div class="bg-primary-subtle text-primary rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                <i class="bi bi-calculator fs-4"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-0 text-dark">{{ $settings->calc_title ?? 'Loan Rate Estimator' }}</h5>
                                <small class="text-muted">{{ $settings->calc_subtitle ?? 'Instant repayment calculation' }}</small>
                            </div>
                        </div>

                        <!-- Loan Principal Amount Input & Range -->
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label small fw-bold text-secondary mb-0">Loan Principal Amount</label>
                                <span class="badge bg-primary-subtle text-primary font-monospace fw-bold fs-6" id="calcAmountBadge">₹50,000</span>
                            </div>
                            <div class="input-group input-group-sm mb-1">
                                <span class="input-group-text bg-light fw-bold text-dark">₹</span>
                                <input type="number" id="calcLoanAmount" class="form-control bg-light fw-bold text-dark" 
                                    value="{{ (int) preg_replace('/[^0-9]/', '', $settings->calc_default_amount ?? '50000') }}" 
                                    min="{{ (int) preg_replace('/[^0-9]/', '', $settings->calc_min_amount ?? '5000') }}" 
                                    max="{{ (int) preg_replace('/[^0-9]/', '', $settings->calc_max_amount ?? '500000') }}" 
                                    step="500">
                            </div>
                            <input type="range" id="calcAmountRange" class="form-range" 
                                value="{{ (int) preg_replace('/[^0-9]/', '', $settings->calc_default_amount ?? '50000') }}" 
                                min="{{ (int) preg_replace('/[^0-9]/', '', $settings->calc_min_amount ?? '5000') }}" 
                                max="{{ (int) preg_replace('/[^0-9]/', '', $settings->calc_max_amount ?? '500000') }}" 
                                step="500">
                            <div class="d-flex justify-content-between small text-muted">
                                <span>Min: ₹{{ number_format((float) preg_replace('/[^0-9.]/', '', $settings->calc_min_amount ?? '5000')) }}</span>
                                <span>Max: ₹{{ number_format((float) preg_replace('/[^0-9.]/', '', $settings->calc_max_amount ?? '500000')) }}</span>
                            </div>
                        </div>

                        <!-- Tenure Period & Repayment Frequency -->
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-bold text-secondary mb-1">Tenure Period</label>
                                <select id="calcTenure" class="form-select form-select-sm bg-light fw-semibold">
                                    @php
                                        $rawTenures = $settings->calc_tenure_options ?? ['6', '12', '18', '24', '36'];
                                        if (is_string($rawTenures)) {
                                            $rawTenures = json_decode($rawTenures, true) ?? ['6', '12', '18', '24', '36'];
                                        }
                                    @endphp
                                    @foreach($rawTenures as $t)
                                        @php $tNum = (int) preg_replace('/[^0-9]/', '', $t); @endphp
                                        <option value="{{ $tNum }}" {{ $tNum == 12 ? 'selected' : '' }}>{{ $tNum }} Months</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-6">
                                <label class="form-label small fw-bold text-secondary mb-1">Repayment Frequency</label>
                                <select id="calcFrequency" class="form-select form-select-sm bg-light fw-semibold">
                                    <option value="monthly" selected>Monthly</option>
                                    <option value="weekly">Weekly</option>
                                    <option value="15_days">15 Days (Fortnightly)</option>
                                    <option value="daily">Daily</option>
                                </select>
                            </div>
                        </div>

                        <!-- Interest Method & Interest Rate -->
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-bold text-secondary mb-1">Interest Method</label>
                                <select id="calcType" class="form-select form-select-sm bg-light fw-semibold">
                                    <option value="reducing_balance" {{ ($settings->calc_type ?? 'reducing_balance') === 'reducing_balance' ? 'selected' : '' }}>Reducing Balance</option>
                                    <option value="flat_rate" {{ ($settings->calc_type ?? '') === 'flat_rate' ? 'selected' : '' }}>Flat Interest Rate</option>
                                </select>
                            </div>

                            <div class="col-6">
                                <label class="form-label small fw-bold text-secondary mb-1">Annual Interest Rate</label>
                                <div class="input-group input-group-sm">
                                    <input type="number" id="calcInterestRate" class="form-control bg-light fw-bold" 
                                        value="{{ preg_replace('/[^0-9.]/', '', $settings->calc_interest_rate ?? '12.5') }}" 
                                        step="0.1" min="0.1" max="100">
                                    <span class="input-group-text bg-light fw-bold">% P.A.</span>
                                </div>
                            </div>
                        </div>

                        <!-- Dynamic Output Summary Box -->
                        <div class="p-3 bg-light rounded-3 mb-3 border">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="fw-bold small text-dark">Installment Amount:</span>
                                <div class="text-end">
                                    <h4 class="fw-bold text-success mb-0 d-inline" id="calcInstallmentAmount">₹4,454</h4>
                                    <span class="small text-muted font-monospace ms-1" id="calcFrequencySuffix">/ month</span>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="small text-muted">Number of Installments:</span>
                                <span class="small fw-bold text-dark font-monospace" id="calcInstallmentsCount">12 Installments</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="small text-muted">Total Interest Payable:</span>
                                <span class="small fw-bold text-primary font-monospace" id="calcTotalInterest">₹3,444</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="small text-muted">Total Payable Amount:</span>
                                <span class="small fw-bold text-dark font-monospace" id="calcTotalPayable">₹53,444</span>
                            </div>
                        </div>

                        <a href="{{ $settings->calc_cta_url ?? url('/apply-loan') }}" class="btn btn-primary w-100 py-2.5 rounded-pill fw-bold shadow-sm">
                            {{ $settings->calc_cta_text ?? 'Proceed with Application' }} <i class="bi bi-chevron-right ms-1"></i>
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>
</section>

<!-- 2. ABOUT COMPANY (CMS HOMEPAGE SECTION) -->
@php
    $aboutSection = $sectionsKeyed['about_summary'] ?? null;
@endphp
<section class="container-xl py-5">
    <div class="row align-items-center g-4 g-lg-5">
        <div class="col-lg-6">
            <span class="text-uppercase small fw-bold text-primary tracking-wider mb-2 d-block">
                {{ $aboutSection->subtitle ?? 'About ' . ($settings->company_name ?? 'TG Microfinance') }}
            </span>
            <h2 class="display-6 fw-bold text-dark mb-3">
                {{ $aboutSection->title ?? 'Pioneering Financial Inclusion for Over 15 Years' }}
            </h2>
            <p class="text-muted lead mb-4">
                {{ $aboutSection->description ?? 'TG Microfinance is a regulated microfinance institution providing tailored financial capital, group savings schemes, and doorstep field banking to micro-borrowers and underserved business communities.' }}
            </p>

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <x-ui.card class="p-3 bg-light border-0 h-100">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi {{ $aboutSection->mission_icon ?? 'bi-bullseye' }} text-primary fs-4"></i>
                            <h6 class="fw-bold mb-0">{{ $aboutSection->mission_title ?? 'Our Mission' }}</h6>
                        </div>
                        <p class="text-muted small mb-0">{{ $aboutSection->mission_description ?? 'To deliver transparent, accessible credit that fosters self-reliance and community wealth creation.' }}</p>
                    </x-ui.card>
                </div>
                <div class="col-md-6">
                    <x-ui.card class="p-3 bg-light border-0 h-100">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi {{ $aboutSection->vision_icon ?? 'bi-eye' }} text-success fs-4"></i>
                            <h6 class="fw-bold mb-0">{{ $aboutSection->vision_title ?? 'Our Vision' }}</h6>
                        </div>
                        <p class="text-muted small mb-0">{{ $aboutSection->vision_description ?? 'To be the most trusted digital microfinance institution recognized for client protection and impact.' }}</p>
                    </x-ui.card>
                </div>
            </div>

            <a href="{{ $aboutSection->button_url ?? url('/about') }}" class="btn btn-outline-primary rounded-pill px-4 py-2 fw-bold d-inline-flex align-items-center gap-2">
                <span>{{ $aboutSection->button_text ?? 'Read Full Corporate Profile' }}</span>
                <i class="bi bi-arrow-right"></i>
            </a>
        </div>

        <div class="col-lg-6">
            <div class="position-relative">
                @if(isset($aboutSection) && $aboutSection->image_url)
                    <div class="mb-4">
                        <img src="{{ $aboutSection->image_url }}" alt="{{ $aboutSection->title }}" class="img-fluid rounded-4 shadow-lg border" style="width: 100%; max-height: 240px; object-fit: cover;">
                    </div>
                @endif

                <x-ui.card class="p-4 p-md-5 border-0 shadow-lg rounded-4 bg-dark text-white">
                    <div class="d-flex align-items-center gap-3 mb-4">
                        <div class="bg-primary text-white rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 56px; height: 56px;">
                            <i class="bi {{ $aboutSection->governance_icon ?? 'bi-bank2' }} fs-3"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-0 text-white">{{ $aboutSection->governance_title ?? 'Institutional Governance' }}</h5>
                            <small class="text-light opacity-75">{{ $aboutSection->governance_subtitle ?? 'Regulated Micro-Finance ERP' }}</small>
                        </div>
                    </div>
                    <p class="text-white opacity-90 small mb-3">{{ $aboutSection->governance_description ?? 'Operating under central banking regulation and double-entry accounting integrity.' }}</p>
                    
                    @php
                        $govBullets = $aboutSection->governance_bullets ?? [
                            'Double-entry general ledger audited financial accounting',
                            'Field officer GPS biometric KYC identification',
                            'Central vault limit controls and instant digital receipts'
                        ];
                        if (is_string($govBullets)) {
                            $govBullets = json_decode($govBullets, true) ?? [];
                        }
                    @endphp

                    @if(!empty($govBullets) && is_array($govBullets))
                        <ul class="list-unstyled mb-0 d-flex flex-column gap-3">
                            @foreach($govBullets as $bPoint)
                                <li class="d-flex align-items-center gap-3">
                                    <i class="bi bi-check-circle-fill text-primary fs-5"></i>
                                    <span>{{ $bPoint }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </x-ui.card>
            </div>
        </div>
    </div>
</section>

<!-- 3. LOAN PRODUCTS PREVIEW (CMS DRIVEN) -->
<section class="container-xl py-5">
    @php
        $productsSection = $sectionsKeyed['products_overview'] ?? null;
    @endphp
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end mb-5">
        <div>
            <span class="text-uppercase small fw-bold text-primary tracking-wider mb-2 d-block">
                {{ $productsSection->subtitle ?? 'Credit Facilities' }}
            </span>
            <h2 class="fw-bold text-dark mb-0">
                {{ $productsSection->title ?? 'Tailored Micro-Loan Products' }}
            </h2>
        </div>
        <a href="{{ $productsSection->button_url ?? url('/products/loan') }}" class="btn btn-outline-primary rounded-pill px-3.5 py-2 small fw-bold mt-3 mt-md-0">
            {{ $productsSection->button_text ?? 'View All Loan Schemes' }} <i class="bi bi-arrow-right ms-1"></i>
        </a>
    </div>

    <div class="row g-4">
        @forelse($loanProducts as $product)
            <div class="col-md-4">
                <x-ui.card class="h-100 p-4 border-top border-4 border-{{ $product->badge_color ?? 'primary' }} tg-hover-lift">
                    @if($product->image_url)
                        <div class="mb-3">
                            <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="img-fluid rounded-3 border" style="width: 100%; max-height: 150px; object-fit: cover;">
                        </div>
                    @else
                        <div class="bg-{{ $product->badge_color ?? 'primary' }}-subtle text-{{ $product->badge_color ?? 'primary' }} rounded-circle p-3 mb-3 d-inline-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
                            <i class="bi {{ $product->icon ?? 'bi-briefcase' }} fs-3"></i>
                        </div>
                    @endif
                    <h5 class="fw-bold">{{ $product->name }}</h5>
                    <p class="text-muted small mb-3">{{ Str::limit($product->description, 100) }}</p>
                    <ul class="list-unstyled small text-muted mb-4">
                        @if($product->min_amount || $product->max_amount)
                            <li class="mb-1"><i class="bi bi-check text-success me-1"></i> Amount: {{ $product->min_amount && $product->max_amount ? '$'.$product->min_amount.' – $'.$product->max_amount : ($product->min_amount ? 'Min $'.$product->min_amount : 'Up to $'.$product->max_amount) }}</li>
                        @endif
                        @if($product->interest_rate)
                            <li class="mb-1"><i class="bi bi-check text-success me-1"></i> Rate: {{ $product->interest_rate }}</li>
                        @endif
                        @if($product->tenure)
                            <li class="mb-1"><i class="bi bi-check text-success me-1"></i> Tenure: {{ $product->tenure }}</li>
                        @endif
                    </ul>
                    <a href="{{ url('/apply-loan') }}" class="btn btn-{{ $product->badge_color ?? 'primary' }} text-white w-100 rounded-pill btn-sm fw-bold">Apply Now</a>
                </x-ui.card>
            </div>
        @empty
            <div class="col-md-4">
                <x-ui.card class="h-100 p-4 border-top border-4 border-primary tg-hover-lift">
                    <div class="bg-primary-subtle text-primary rounded-circle p-3 mb-3 d-inline-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
                        <i class="bi bi-briefcase fs-3"></i>
                    </div>
                    <h5 class="fw-bold">Micro-Enterprise Loan</h5>
                    <p class="text-muted small">Fast working capital for small shop owners and trade vendors needing inventory funds.</p>
                    <ul class="list-unstyled small text-muted mb-4">
                        <li class="mb-1"><i class="bi bi-check text-success me-1"></i> Amount: $500 – $5,000</li>
                        <li class="mb-1"><i class="bi bi-check text-success me-1"></i> Rate: 12.5% P.A.</li>
                    </ul>
                    <a href="{{ url('/apply-loan') }}" class="btn btn-primary w-100 rounded-pill btn-sm fw-bold">Apply Now</a>
                </x-ui.card>
            </div>
        @endforelse
    </div>
</section>

<!-- 4. SAVINGS PRODUCTS PREVIEW (CMS DRIVEN) -->
<section class="bg-light py-5 border-top border-bottom">
    <div class="container-xl">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end mb-5">
            <div>
                <span class="text-uppercase small fw-bold text-primary tracking-wider mb-2 d-block">Deposit Accounts</span>
                <h2 class="fw-bold text-dark mb-0">High-Yield Savings Schemes</h2>
            </div>
            <a href="{{ url('/products/savings') }}" class="btn btn-outline-primary rounded-pill px-3.5 py-2 small fw-bold mt-3 mt-md-0">
                View All Savings Schemes <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>

        <div class="row g-4">
            @forelse($savingsProducts as $spProduct)
                <div class="col-md-4">
                    <x-ui.card class="h-100 p-4 border-top border-4 border-{{ $spProduct->badge_color ?? 'success' }} tg-hover-lift">
                        @if($spProduct->image_url)
                            <div class="mb-3">
                                <img src="{{ $spProduct->image_url }}" alt="{{ $spProduct->name }}" class="img-fluid rounded-3 border" style="width: 100%; max-height: 150px; object-fit: cover;">
                            </div>
                        @else
                            <div class="bg-{{ $spProduct->badge_color ?? 'success' }}-subtle text-{{ $spProduct->badge_color ?? 'success' }} rounded-circle p-3 mb-3 d-inline-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
                                <i class="bi {{ $spProduct->icon ?? 'bi-wallet2' }} fs-3"></i>
                            </div>
                        @endif
                        <h5 class="fw-bold">{{ $spProduct->name }}</h5>
                        <p class="text-muted small mb-3">{{ Str::limit($spProduct->description, 100) }}</p>
                        <ul class="list-unstyled small text-muted mb-4">
                            @if($spProduct->interest_rate)
                                <li class="mb-1"><i class="bi bi-check text-success me-1"></i> Interest: {{ $spProduct->interest_rate }}</li>
                            @endif
                            @if($spProduct->min_balance)
                                <li class="mb-1"><i class="bi bi-check text-success me-1"></i> Opening Deposit: ${{ $spProduct->min_balance }}</li>
                            @endif
                            @if($spProduct->tenure)
                                <li class="mb-1"><i class="bi bi-check text-success me-1"></i> Tenure: {{ $spProduct->tenure }}</li>
                            @endif
                        </ul>
                        <a href="{{ url('/contact') }}" class="btn btn-outline-{{ $spProduct->badge_color ?? 'success' }} w-100 rounded-pill btn-sm fw-bold">Open Account</a>
                    </x-ui.card>
                </div>
            @empty
                <div class="col-md-4">
                    <x-ui.card class="h-100 p-4 tg-hover-lift">
                        <div class="bg-success-subtle text-success rounded-circle p-3 mb-3 d-inline-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
                            <i class="bi bi-wallet2 fs-3"></i>
                        </div>
                        <h5 class="fw-bold">Regular Savings Account</h5>
                        <p class="text-muted small">Everyday savings with monthly compound interest credits and zero account maintenance fees.</p>
                        <a href="{{ url('/contact') }}" class="btn btn-outline-success w-100 rounded-pill btn-sm fw-bold">Open Account</a>
                    </x-ui.card>
                </div>
            @endforelse
        </div>
    </div>
</section>

<!-- 5. WHY MICRO-BORROWERS CHOOSE US (CMS DRIVEN WHY CHOOSE US) -->
<section class="bg-white py-5 border-top border-bottom">
    <div class="container-xl">
        <div class="text-center mx-auto mb-5" style="max-width: 700px;">
            <span class="text-uppercase small fw-bold text-primary tracking-wider mb-2 d-block">Institutional Strengths</span>
            <h2 class="fw-bold text-dark">Why Micro-Borrowers Choose Us</h2>
            <p class="text-muted">Enterprise-grade security, rapid processing turnarounds, and client protection standards.</p>
        </div>

        <div class="row g-4">
            @forelse($whyChooseUsItems as $item)
                <div class="col-md-4">
                    <x-ui.card class="h-100 tg-hover-lift text-center p-4">
                        <div class="bg-{{ $item->badge_color ?? 'primary' }}-subtle text-{{ $item->badge_color ?? 'primary' }} rounded-circle p-3 mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                            <i class="bi {{ $item->icon ?? 'bi-shield-check' }} fs-3"></i>
                        </div>
                        <h5 class="fw-bold">{{ $item->title }}</h5>
                        <p class="text-muted small mb-0">{{ $item->description }}</p>
                    </x-ui.card>
                </div>
            @empty
                <div class="col-md-4">
                    <x-ui.card class="h-100 tg-hover-lift text-center p-4">
                        <div class="bg-primary-subtle text-primary rounded-circle p-3 mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                            <i class="bi bi-shield-lock fs-3"></i>
                        </div>
                        <h5 class="fw-bold">Bank-Grade Security</h5>
                        <p class="text-muted small mb-0">Encrypted user sessions, role-based access control, and complete audit trail logging.</p>
                    </x-ui.card>
                </div>
            @endforelse
        </div>
    </div>
</section>

<!-- 6. LATEST NEWS PREVIEW (CMS DRIVEN) -->
@if(isset($latestNews) && $latestNews->count() > 0)
<section class="container-xl py-5">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end mb-4">
        <div>
            <span class="text-uppercase small fw-bold text-primary tracking-wider mb-1 d-block">Media Center</span>
            <h2 class="fw-bold text-dark mb-0">Latest News & Press Releases</h2>
        </div>
        <a href="{{ route('public.resources.news') }}" class="btn btn-outline-primary rounded-pill px-3.5 py-2 small fw-bold mt-3 mt-md-0">
            View All News <i class="bi bi-arrow-right ms-1"></i>
        </a>
    </div>

    <div class="row g-4">
        @foreach($latestNews as $newsItem)
            <div class="col-md-4">
                <x-ui.card class="p-0 overflow-hidden h-100 tg-hover-lift">
                    @if($newsItem->featured_image_url)
                        <img src="{{ $newsItem->featured_image_url }}" alt="{{ $newsItem->title }}" class="img-fluid border-bottom" style="width: 100%; height: 180px; object-fit: cover;">
                    @else
                        <div class="bg-light p-4 text-center border-bottom">
                            <i class="bi bi-newspaper fs-1 text-primary opacity-50"></i>
                        </div>
                    @endif
                    <div class="p-4 d-flex flex-column justify-content-between h-100">
                        <div>
                            <small class="text-primary fw-bold d-block mb-2">
                                <i class="bi bi-calendar-event me-1"></i> {{ $newsItem->published_date ? $newsItem->published_date->format('M d, Y') : $newsItem->created_at->format('M d, Y') }}
                            </small>
                            <h6 class="fw-bold mb-2 text-dark">{{ $newsItem->title }}</h6>
                            <p class="text-muted small mb-3">
                                {{ Str::limit($newsItem->short_description ?? strip_tags($newsItem->content), 90) }}
                            </p>
                        </div>
                        <div>
                            <a href="{{ route('public.resources.news.show', $newsItem->slug) }}" class="btn btn-outline-primary btn-sm rounded-pill px-3 fw-bold">
                                Read Story <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                </x-ui.card>
            </div>
        @endforeach
    </div>
</section>
@endif

<!-- 7. GALLERY PREVIEW (CMS DRIVEN) -->
@if(isset($galleryItems) && $galleryItems->count() > 0)
<section class="bg-light py-5 border-top border-bottom">
    <div class="container-xl">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end mb-4">
            <div>
                <span class="text-uppercase small fw-bold text-primary tracking-wider mb-1 d-block">Outreach & Events</span>
                <h2 class="fw-bold text-dark mb-0">Community Photo Gallery</h2>
            </div>
            <a href="{{ route('public.resources.gallery') }}" class="btn btn-outline-primary rounded-pill px-3.5 py-2 small fw-bold mt-3 mt-md-0">
                View Full Gallery <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>

        <div class="row g-3">
            @foreach($galleryItems as $gPhoto)
                <div class="col-6 col-md-4 col-lg-2">
                    <x-ui.card class="p-0 overflow-hidden h-100 tg-hover-lift">
                        @if($gPhoto->image_url)
                            <img src="{{ $gPhoto->image_url }}" alt="{{ $gPhoto->title }}" class="img-fluid" style="width: 100%; height: 130px; object-fit: cover;">
                        @else
                            <div class="bg-dark text-white p-3 text-center d-flex align-items-center justify-content-center" style="height: 130px;">
                                <i class="bi bi-image fs-3 text-muted"></i>
                            </div>
                        @endif
                        <div class="p-2 text-center bg-white border-top">
                            <small class="fw-semibold text-dark text-truncate d-block" style="font-size: 0.75rem;">{{ $gPhoto->title }}</small>
                        </div>
                    </x-ui.card>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif

<!-- 8. FAQ PREVIEW (CMS DRIVEN ACCORDION) -->
@if(isset($faqs) && $faqs->count() > 0)
<section class="container-xl py-5">
    <div class="text-center mx-auto mb-4" style="max-width: 700px;">
        <span class="text-uppercase small fw-bold text-primary tracking-wider mb-1 d-block">Help & Support</span>
        <h2 class="fw-bold text-dark">Frequently Asked Questions</h2>
        <p class="text-muted small">Quick answers to common borrowing and savings account questions.</p>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="accordion accordion-flush bg-white rounded-4 p-4 shadow-sm border" id="homeFaqAccordion">
                @foreach($faqs as $index => $faqItem)
                    <div class="accordion-item {{ $index < $faqs->count() - 1 ? 'border-bottom' : '' }}">
                        <h2 class="accordion-header" id="homeFaqHeading{{ $faqItem->id }}">
                            <button class="accordion-button {{ $index === 0 ? '' : 'collapsed' }} fw-bold text-dark shadow-none py-3" type="button" data-bs-toggle="collapse" data-bs-target="#homeCollapseFaq{{ $faqItem->id }}" aria-expanded="{{ $index === 0 ? 'true' : 'false' }}">
                                <i class="bi bi-question-circle text-primary me-2 fs-5"></i> {{ $faqItem->question }}
                            </button>
                        </h2>
                        <div id="homeCollapseFaq{{ $faqItem->id }}" class="accordion-collapse collapse {{ $index === 0 ? 'show' : '' }}" data-bs-parent="#homeFaqAccordion">
                            <div class="accordion-body text-secondary lh-relaxed pb-4 ps-4">
                                {!! nl2br(e($faqItem->answer)) !!}
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="text-center mt-3">
                <a href="{{ route('public.resources.faq') }}" class="btn btn-link text-decoration-none fw-bold text-primary">
                    View All FAQs <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>
        </div>
    </div>
</section>
@endif

<!-- 9. HEAD OFFICE & LOCATION SECTION (CMS DRIVEN) -->
@php
    $hqSection = $sectionsKeyed['headquarters_branch'] ?? null;
@endphp
<section class="bg-light border-top border-bottom py-5">
    <div class="container-xl">
        <div class="text-center mx-auto mb-5" style="max-width: 700px;">
            <span class="text-uppercase small fw-bold text-primary tracking-wider mb-2 d-block">{{ $hqSection->subtitle ?? 'Physical Presence' }}</span>
            <h2 class="fw-bold text-dark">{{ $hqSection->title ?? $settings->location_heading ?? 'Headquarters & Branch Network' }}</h2>
            <p class="text-muted">{{ $hqSection->description ?? $settings->location_description ?? 'Visit any of our branch offices for counter disbursements, deposits, and officer guidance.' }}</p>
        </div>

        <div class="row g-4">
            <div class="col-md-6">
                <x-ui.card class="p-4 border-start border-4 border-primary h-100">
                    <span class="badge bg-primary-subtle text-primary mb-2" style="width: fit-content;">Head Office</span>
                    <h6 class="fw-bold mb-2">{{ $hqSection->head_office_title ?? $settings->company_name ?? 'TG Microfinance Headquarters' }}</h6>
                    <p class="text-muted small mb-2"><i class="bi bi-geo-alt text-primary me-2"></i> {{ $hqSection->address ?? $settings->address ?? '100 Financial Avenue, Suite 500' }}</p>
                    <p class="text-muted small mb-2"><i class="bi bi-telephone text-primary me-2"></i> {{ $hqSection->phone ?? $settings->phone ?? '+91 (800) 555-0199' }}</p>
                    <p class="text-muted small mb-0"><i class="bi bi-envelope text-primary me-2"></i> {{ $hqSection->email ?? $settings->email ?? 'info@tgmicrofinance.org' }}</p>
                </x-ui.card>
            </div>

            <div class="col-md-6">
                <x-ui.card class="p-4 border-start border-4 border-success h-100">
                    <span class="badge bg-success-subtle text-success mb-2" style="width: fit-content;">Customer Support</span>
                    <h6 class="fw-bold mb-2">{{ $hqSection->support_box_title ?? $settings->support_box_title ?? 'Direct Inquiries & Assistance' }}</h6>
                    <p class="text-muted small mb-3">{{ $hqSection->support_box_description ?? $settings->support_box_desc ?? 'Our team is available to guide you through loan applications and account setups.' }}</p>
                    <a href="{{ $hqSection->support_button_url ?? $settings->support_box_button_url ?? url('/contact') }}" class="btn btn-outline-success rounded-pill btn-sm fw-bold px-3">
                        {{ $hqSection->support_button_text ?? $settings->support_box_button_text ?? 'Contact Support Team' }}
                    </a>
                </x-ui.card>
            </div>
        </div>
    </div>
</section>

<!-- 10. FINAL CTA (CMS DRIVEN HOMEPAGE CTA) -->
@php
    $ctaSection = $sectionsKeyed['homepage_cta'] ?? null;
@endphp
<x-ui.cta
    title="{{ $ctaSection->cta_heading ?? 'Ready to Apply for Micro-Credit?' }}"
    subtitle="{{ $ctaSection->cta_description ?? 'Submit your initial loan request online in minutes, or visit your nearest branch counter today.' }}"
    primaryText="{{ $ctaSection->cta_button1_text ?? 'Apply for Loan Now' }}"
    primaryUrl="{{ $ctaSection->cta_button1_url ?? '/apply-loan' }}"
    secondaryText="{{ $ctaSection->cta_button2_text ?? 'Contact Customer Support' }}"
    secondaryUrl="{{ $ctaSection->cta_button2_url ?? '/contact' }}"
/>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const loanAmountInput = document.getElementById('calcLoanAmount');
    const loanAmountRange = document.getElementById('calcAmountRange');
    const loanAmountBadge = document.getElementById('calcAmountBadge');
    const tenureSelect = document.getElementById('calcTenure');
    const frequencySelect = document.getElementById('calcFrequency');
    const calcTypeSelect = document.getElementById('calcType');
    const interestRateInput = document.getElementById('calcInterestRate');

    const installmentAmountEl = document.getElementById('calcInstallmentAmount');
    const frequencySuffixEl = document.getElementById('calcFrequencySuffix');
    const installmentsCountEl = document.getElementById('calcInstallmentsCount');
    const totalInterestEl = document.getElementById('calcTotalInterest');
    const totalPayableEl = document.getElementById('calcTotalPayable');

    const roundingType = "{{ $settings->calc_rounding_type ?? 'nearest_integer' }}";

    function formatINR(val) {
        return '₹' + Math.round(val).toLocaleString('en-IN');
    }

    function applyRounding(val) {
        if (roundingType === 'round_up') return Math.ceil(val);
        if (roundingType === 'round_down') return Math.floor(val);
        if (roundingType === 'none') return parseFloat(val.toFixed(2));
        return Math.round(val);
    }

    function calculateEMI() {
        if (!loanAmountInput) return;

        let principal = parseFloat(loanAmountInput.value) || 0;
        let tenureMonths = parseInt(tenureSelect.value) || 12;
        let annualRatePercent = parseFloat(interestRateInput.value) || 12.5;
        let annualRate = annualRatePercent / 100;

        let frequency = frequencySelect.value;
        let calcType = calcTypeSelect.value;

        let numberOfInstallments = 12;
        let periodRate = 0;
        let frequencySuffix = '/ month';

        if (frequency === 'monthly') {
            numberOfInstallments = tenureMonths;
            periodRate = annualRate / 12;
            frequencySuffix = '/ month';
        } else if (frequency === 'weekly') {
            numberOfInstallments = Math.round(tenureMonths * (52 / 12));
            periodRate = annualRate / 52;
            frequencySuffix = '/ week';
        } else if (frequency === '15_days') {
            numberOfInstallments = tenureMonths * 2;
            periodRate = annualRate / 24;
            frequencySuffix = '/ fortnight';
        } else if (frequency === 'daily') {
            numberOfInstallments = Math.round(tenureMonths * (365 / 12));
            periodRate = annualRate / 365;
            frequencySuffix = '/ day';
        }

        let installmentAmount = 0;
        let totalInterest = 0;
        let totalPayable = 0;

        if (calcType === 'flat_rate') {
            totalInterest = principal * annualRate * (tenureMonths / 12);
            totalPayable = principal + totalInterest;
            installmentAmount = totalPayable / numberOfInstallments;
        } else {
            // Reducing Balance Method (Equated Periodical Installment)
            if (periodRate > 0) {
                let compoundFactor = Math.pow(1 + periodRate, numberOfInstallments);
                installmentAmount = principal * periodRate * (compoundFactor / (compoundFactor - 1));
                totalPayable = installmentAmount * numberOfInstallments;
                totalInterest = totalPayable - principal;
            } else {
                installmentAmount = principal / numberOfInstallments;
                totalPayable = principal;
                totalInterest = 0;
            }
        }

        let roundedInstallment = applyRounding(installmentAmount);
        let roundedTotalInterest = applyRounding(totalInterest);
        let roundedTotalPayable = applyRounding(totalPayable);

        // Update DOM elements
        loanAmountBadge.textContent = '₹' + Math.round(principal).toLocaleString('en-IN');
        installmentAmountEl.textContent = formatINR(roundedInstallment);
        frequencySuffixEl.textContent = frequencySuffix;
        installmentsCountEl.textContent = numberOfInstallments + ' Installments';
        totalInterestEl.textContent = formatINR(roundedTotalInterest);
        totalPayableEl.textContent = formatINR(roundedTotalPayable);
    }

    if (loanAmountInput && loanAmountRange) {
        loanAmountInput.addEventListener('input', function() {
            loanAmountRange.value = this.value;
            calculateEMI();
        });

        loanAmountRange.addEventListener('input', function() {
            loanAmountInput.value = this.value;
            calculateEMI();
        });

        if (tenureSelect) tenureSelect.addEventListener('change', calculateEMI);
        if (frequencySelect) frequencySelect.addEventListener('change', calculateEMI);
        if (calcTypeSelect) calcTypeSelect.addEventListener('change', calculateEMI);
        if (interestRateInput) interestRateInput.addEventListener('input', calculateEMI);

        // Run calculation on load
        calculateEMI();
    }
});
</script>

@endsection
