@extends('layouts.public')

@section('title', 'Board of Directors - TG Microfinance ERP')
@section('meta_description', 'Meet the Board of Directors governing TG Microfinance enterprise operations and fiduciary standards.')

@section('content')
<x-ui.page-banner
    title="Board of Directors"
    subtitle="Distinguished financial, legal, and operational governance leaders guiding our institutional strategy."
    badge="Governance"
    :breadcrumbs="['About' => '/about', 'Board of Directors' => '']"
/>

<section class="container-xl py-5">
    <div class="row g-4">
        <div class="col-md-4">
            <x-ui.card class="h-100 text-center p-4">
                <div class="bg-primary-subtle text-primary rounded-circle p-4 mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                    <i class="bi bi-person-badge fs-1"></i>
                </div>
                <h5 class="fw-bold mb-1">Robert Vance</h5>
                <span class="badge bg-primary-subtle text-primary mb-3">Chairman of the Board</span>
                <p class="text-muted small">Over 25 years of commercial banking and micro-credit risk governance experience.</p>
            </x-ui.card>
        </div>
        <div class="col-md-4">
            <x-ui.card class="h-100 text-center p-4">
                <div class="bg-primary-subtle text-primary rounded-circle p-4 mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                    <i class="bi bi-person-badge fs-1"></i>
                </div>
                <h5 class="fw-bold mb-1">Elena Rostova</h5>
                <span class="badge bg-success-subtle text-success mb-3">Independent Director</span>
                <p class="text-muted small">Specialist in microfinance regulatory compliance and financial technology audit.</p>
            </x-ui.card>
        </div>
        <div class="col-md-4">
            <x-ui.card class="h-100 text-center p-4">
                <div class="bg-primary-subtle text-primary rounded-circle p-4 mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                    <i class="bi bi-person-badge fs-1"></i>
                </div>
                <h5 class="fw-bold mb-1">Marcus Sterling</h5>
                <span class="badge bg-info-subtle text-info mb-3">Executive Director</span>
                <p class="text-muted small">Expert in portfolio risk modeling and multi-branch liquidity management.</p>
            </x-ui.card>
        </div>
    </div>
</section>

<x-ui.cta />
@endsection
