@extends('layouts.public')

@section('title', 'Our Mission - TG Microfinance ERP')
@section('meta_description', 'Discover TG Microfinance mission statement and commitment to micro-enterprise development.')

@section('content')
<x-ui.page-banner
    title="Our Mission Statement"
    subtitle="Driving sustainable economic mobility for low-income households and micro-enterprises through accessible financial services."
    badge="Corporate Identity"
    :breadcrumbs="['About' => '/about', 'Mission' => '']"
/>

<section class="container-xl py-5">
    <div class="row g-4">
        <div class="col-lg-8">
            <x-ui.card class="p-4 p-md-5 mb-4 border-start border-4 border-primary">
                <h3 class="fw-bold text-dark mb-3"><i class="bi bi-bullseye text-primary me-2"></i>Mission Focus</h3>
                <p class="lead text-muted">To deliver transparent, affordable, and rapid financial services to micro-borrowers, enabling self-reliance and community wealth creation while maintaining strict institutional sustainability.</p>
            </x-ui.card>
            <div class="row g-4">
                <div class="col-md-6">
                    <x-ui.card class="p-4 h-100">
                        <h5 class="fw-bold"><i class="bi bi-shield-check text-success me-2"></i>Responsible Credit</h5>
                        <p class="text-muted small mb-0">Fair interest rates, transparent fee structures, and prevention of over-indebtedness for all members.</p>
                    </x-ui.card>
                </div>
                <div class="col-md-6">
                    <x-ui.card class="p-4 h-100">
                        <h5 class="fw-bold"><i class="bi bi-people text-info me-2"></i>Gender Inclusion</h5>
                        <p class="text-muted small mb-0">Prioritizing women entrepreneurs and group-based micro-loans to foster family financial security.</p>
                    </x-ui.card>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <x-ui.card class="p-4 bg-light">
                <h6 class="fw-bold text-dark mb-3">Quick Navigation</h6>
                <div class="nav flex-column gap-2">
                    <a href="{{ url('/about') }}" class="btn btn-outline-secondary btn-sm text-start"><i class="bi bi-chevron-right me-1"></i> About Overview</a>
                    <a href="{{ url('/about/vision') }}" class="btn btn-outline-secondary btn-sm text-start"><i class="bi bi-chevron-right me-1"></i> Our Vision</a>
                    <a href="{{ url('/about/board-of-directors') }}" class="btn btn-outline-secondary btn-sm text-start"><i class="bi bi-chevron-right me-1"></i> Board of Directors</a>
                    <a href="{{ url('/about/management-team') }}" class="btn btn-outline-secondary btn-sm text-start"><i class="bi bi-chevron-right me-1"></i> Management Team</a>
                </div>
            </x-ui.card>
        </div>
    </div>
</section>

<x-ui.cta />
@endsection
