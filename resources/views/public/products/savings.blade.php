@extends('layouts.public')

@section('title', 'Savings Products - ' . ($settings->company_name ?? 'TG Microfinance ERP'))
@section('meta_description', 'High-yield savings accounts, fixed deposits, and group passbook accounts with ' . ($settings->company_name ?? 'TG Microfinance'))

@section('content')
<x-ui.page-banner
    title="High-Yield Savings Schemes"
    subtitle="Secure your financial future with competitive compound interest savings accounts and zero-fee passbooks."
    badge="Deposit Products"
    :breadcrumbs="['Products' => '/products/loan', 'Savings Products' => '']"
/>

<section class="container-xl py-5">
    <div class="row g-4">
        @forelse($products as $product)
            <div class="col-md-4">
                <x-ui.card class="h-100 p-4 border-top border-4 border-{{ $product->badge_color ?? 'success' }} tg-hover-lift">
                    @if($product->image_url)
                        <div class="mb-3">
                            <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="img-fluid rounded-3 border" style="width: 100%; max-height: 160px; object-fit: cover;">
                        </div>
                    @else
                        <div class="bg-{{ $product->badge_color ?? 'success' }}-subtle text-{{ $product->badge_color ?? 'success' }} rounded-circle p-3 mb-3 d-inline-flex align-items-center justify-content-center" style="width: 54px; height: 54px;">
                            <i class="bi {{ $product->icon ?? 'bi-wallet2' }} fs-3"></i>
                        </div>
                    @endif

                    <h5 class="fw-bold">{{ $product->name }}</h5>
                    @if($product->description)
                        <p class="text-muted small mb-3">{{ $product->description }}</p>
                    @endif

                    <ul class="list-unstyled small text-muted mb-4">
                        @if($product->interest_rate)
                            <li class="mb-1"><i class="bi bi-check text-success me-1"></i> Interest: {{ $product->interest_rate }}</li>
                        @endif
                        @if($product->min_balance)
                            <li class="mb-1"><i class="bi bi-check text-success me-1"></i> Opening Balance: ${{ $product->min_balance }}</li>
                        @endif
                        @if($product->tenure)
                            <li class="mb-1"><i class="bi bi-check text-success me-1"></i> Tenure: {{ $product->tenure }}</li>
                        @endif
                        @if(is_array($product->features))
                            @foreach($product->features as $feature)
                                <li class="mb-1"><i class="bi bi-check text-success me-1"></i> {{ $feature }}</li>
                            @endforeach
                        @endif
                    </ul>

                    <a href="{{ url('/contact') }}" class="btn btn-outline-{{ $product->badge_color ?? 'success' }} w-100 rounded-pill btn-sm fw-bold">Open {{ $product->name }}</a>
                </x-ui.card>
            </div>
        @empty
            <!-- Fallback Static Savings Products if DB empty -->
            <div class="col-md-4">
                <x-ui.card class="h-100 p-4">
                    <div class="bg-success-subtle text-success rounded-circle p-3 mb-3 d-inline-flex align-items-center justify-content-center" style="width: 54px; height: 54px;">
                        <i class="bi bi-wallet2 fs-3"></i>
                    </div>
                    <h5 class="fw-bold">Regular Savings Account</h5>
                    <p class="text-muted small">Daily access savings account with competitive monthly interest credits and zero maintenance fees.</p>
                    <ul class="list-unstyled small text-muted mb-4">
                        <li class="mb-1"><i class="bi bi-check text-success me-1"></i> Interest: 4.5% P.A.</li>
                        <li class="mb-1"><i class="bi bi-check text-success me-1"></i> Opening Balance: $10</li>
                        <li class="mb-1"><i class="bi bi-check text-success me-1"></i> Passbook Included</li>
                    </ul>
                    <a href="{{ url('/contact') }}" class="btn btn-outline-success w-100 rounded-pill btn-sm fw-bold">Open Account</a>
                </x-ui.card>
            </div>

            <div class="col-md-4">
                <x-ui.card class="h-100 p-4">
                    <div class="bg-primary-subtle text-primary rounded-circle p-3 mb-3 d-inline-flex align-items-center justify-content-center" style="width: 54px; height: 54px;">
                        <i class="bi bi-bank fs-3"></i>
                    </div>
                    <h5 class="fw-bold">Fixed Term Deposit</h5>
                    <p class="text-muted small">Lock in high interest returns with guaranteed fixed tenure options from 3 to 24 months.</p>
                    <ul class="list-unstyled small text-muted mb-4">
                        <li class="mb-1"><i class="bi bi-check text-success me-1"></i> Interest: Up to 8.5% P.A.</li>
                        <li class="mb-1"><i class="bi bi-check text-success me-1"></i> Tenure: 3, 6, 12, 24 Months</li>
                        <li class="mb-1"><i class="bi bi-check text-success me-1"></i> Guaranteed Returns</li>
                    </ul>
                    <a href="{{ url('/contact') }}" class="btn btn-outline-primary w-100 rounded-pill btn-sm fw-bold">Open Account</a>
                </x-ui.card>
            </div>

            <div class="col-md-4">
                <x-ui.card class="h-100 p-4">
                    <div class="bg-info-subtle text-info rounded-circle p-3 mb-3 d-inline-flex align-items-center justify-content-center" style="width: 54px; height: 54px;">
                        <i class="bi bi-people fs-3"></i>
                    </div>
                    <h5 class="fw-bold">Group Savings Account</h5>
                    <p class="text-muted small">Designed for self-help groups and cooperative associations managing pooled member funds.</p>
                    <ul class="list-unstyled small text-muted mb-4">
                        <li class="mb-1"><i class="bi bi-check text-success me-1"></i> Interest: 6.0% P.A.</li>
                        <li class="mb-1"><i class="bi bi-check text-success me-1"></i> Multi-signatory Verification</li>
                        <li class="mb-1"><i class="bi bi-check text-success me-1"></i> Joint Member Ledgers</li>
                    </ul>
                    <a href="{{ url('/contact') }}" class="btn btn-outline-info w-100 rounded-pill btn-sm fw-bold">Open Account</a>
                </x-ui.card>
            </div>
        @endforelse
    </div>
</section>

<x-ui.cta />
@endsection
