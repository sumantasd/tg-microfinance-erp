@extends('layouts.public')

@section('title', 'Our Vision - TG Microfinance ERP')
@section('meta_description', 'Learn about TG Microfinance long-term strategic vision for micro-financial technology.')

@section('content')
<x-ui.page-banner
    title="Our Strategic Vision"
    subtitle="To be the premier digital microfinance institution recognized for operational excellence, innovation, and client protection."
    badge="Future Roadmap"
    :breadcrumbs="['About' => '/about', 'Vision' => '']"
/>

<section class="container-xl py-5">
    <div class="row g-4">
        <div class="col-lg-8">
            <x-ui.card class="p-4 p-md-5 mb-4 border-start border-4 border-success">
                <h3 class="fw-bold text-dark mb-3"><i class="bi bi-eye text-success me-2"></i>Vision Statement</h3>
                <p class="lead text-muted">To build an interconnected digital financial ecosystem where every aspiring micro-entrepreneur has frictionless access to capital, savings tools, and enterprise growth opportunities.</p>
            </x-ui.card>

            <div class="row g-4">
                <div class="col-md-6">
                    <x-ui.card class="p-4 h-100">
                        <h5 class="fw-bold"><i class="bi bi-laptop text-primary me-2"></i>Digital Field Operations</h5>
                        <p class="text-muted small mb-0">Equipping field officers with mobile ERP collection tools for real-time receipt posting.</p>
                    </x-ui.card>
                </div>
                <div class="col-md-6">
                    <x-ui.card class="p-4 h-100">
                        <h5 class="fw-bold"><i class="bi bi-graph-up text-warning me-2"></i>Portfolio Growth</h5>
                        <p class="text-muted small mb-0">Expanding branch coverage to 200+ rural and urban centers while maintaining low Portfolio At Risk (PAR).</p>
                    </x-ui.card>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <x-ui.card class="p-4 bg-light">
                <h6 class="fw-bold text-dark mb-3">Quick Navigation</h6>
                <div class="nav flex-column gap-2">
                    <a href="{{ url('/about') }}" class="btn btn-outline-secondary btn-sm text-start"><i class="bi bi-chevron-right me-1"></i> About Overview</a>
                    <a href="{{ url('/about/mission') }}" class="btn btn-outline-secondary btn-sm text-start"><i class="bi bi-chevron-right me-1"></i> Our Mission</a>
                    <a href="{{ url('/about/board-of-directors') }}" class="btn btn-outline-secondary btn-sm text-start"><i class="bi bi-chevron-right me-1"></i> Board of Directors</a>
                    <a href="{{ url('/about/management-team') }}" class="btn btn-outline-secondary btn-sm text-start"><i class="bi bi-chevron-right me-1"></i> Management Team</a>
                </div>
            </x-ui.card>
        </div>
    </div>
</section>

<x-ui.cta />
@endsection
