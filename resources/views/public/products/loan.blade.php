@extends('layouts.public')

@section('title', 'Loan Products - TG Microfinance ERP')
@section('meta_description', 'Explore micro-loans, SME loans, group loans, and agricultural credit products offered by TG Microfinance.')

@section('content')
<x-ui.page-banner
    title="Microfinance Loan Products"
    subtitle="Flexible, accessible credit schemes designed to fund business growth, equipment purchase, and working capital."
    badge="Credit Solutions"
    :breadcrumbs="['Products' => '/products/loan', 'Loan Products' => '']"
/>

<section class="container-xl py-5">
    <div class="row g-4">
        <div class="col-md-4">
            <x-ui.card class="h-100 p-4 border-top border-4 border-primary">
                <div class="bg-primary-subtle text-primary rounded-circle p-3 mb-3 d-inline-flex align-items-center justify-content-center" style="width: 54px; height: 54px;">
                    <i class="bi bi-briefcase fs-3"></i>
                </div>
                <h5 class="fw-bold">Micro-Enterprise Loan</h5>
                <p class="text-muted small">Targeted at small shop owners, artisans, and sole proprietors needing quick inventory capital.</p>
                <ul class="list-unstyled small text-muted mb-4">
                    <li class="mb-1"><i class="bi bi-check text-success me-1"></i> Amount: $500 – $5,000</li>
                    <li class="mb-1"><i class="bi bi-check text-success me-1"></i> Tenure: 6 to 18 Months</li>
                    <li class="mb-1"><i class="bi bi-check text-success me-1"></i> Flexible Weekly Repayments</li>
                </ul>
                <a href="{{ url('/apply-loan') }}" class="btn btn-outline-primary w-100 rounded-pill btn-sm fw-bold">Apply for Micro Loan</a>
            </x-ui.card>
        </div>

        <div class="col-md-4">
            <x-ui.card class="h-100 p-4 border-top border-4 border-success">
                <div class="bg-success-subtle text-success rounded-circle p-3 mb-3 d-inline-flex align-items-center justify-content-center" style="width: 54px; height: 54px;">
                    <i class="bi bi-people fs-3"></i>
                </div>
                <h5 class="fw-bold">Group Solidarity Loan</h5>
                <p class="text-muted small">Community group lending model empowering self-help groups with cross-guaranteed credit.</p>
                <ul class="list-unstyled small text-muted mb-4">
                    <li class="mb-1"><i class="bi bi-check text-success me-1"></i> Amount: $200 – $2,000 / member</li>
                    <li class="mb-1"><i class="bi bi-check text-success me-1"></i> Tenure: 12 Months</li>
                    <li class="mb-1"><i class="bi bi-check text-success me-1"></i> No Individual Collateral</li>
                </ul>
                <a href="{{ url('/apply-loan') }}" class="btn btn-outline-success w-100 rounded-pill btn-sm fw-bold">Apply for Group Loan</a>
            </x-ui.card>
        </div>

        <div class="col-md-4">
            <x-ui.card class="h-100 p-4 border-top border-4 border-info">
                <div class="bg-info-subtle text-info rounded-circle p-3 mb-3 d-inline-flex align-items-center justify-content-center" style="width: 54px; height: 54px;">
                    <i class="bi bi-building fs-3"></i>
                </div>
                <h5 class="fw-bold">SME Expansion Loan</h5>
                <p class="text-muted small">Larger credit facilities for growing businesses investing in machinery and facility upgrades.</p>
                <ul class="list-unstyled small text-muted mb-4">
                    <li class="mb-1"><i class="bi bi-check text-success me-1"></i> Amount: $5,000 – $25,000</li>
                    <li class="mb-1"><i class="bi bi-check text-success me-1"></i> Tenure: 12 to 36 Months</li>
                    <li class="mb-1"><i class="bi bi-check text-success me-1"></i> Custom Repayment Schedule</li>
                </ul>
                <a href="{{ url('/apply-loan') }}" class="btn btn-outline-info w-100 rounded-pill btn-sm fw-bold">Apply for SME Loan</a>
            </x-ui.card>
        </div>
    </div>
</section>

<x-ui.cta />
@endsection
