@extends('layouts.public')

@section('title', 'Careers & Job Openings - TG Microfinance ERP')
@section('meta_description', 'Join our team of loan officers, branch managers, collection officers, and accountants at TG Microfinance.')

@section('content')
<x-ui.page-banner
    title="Career Opportunities"
    subtitle="Join a purpose-driven team dedicated to expanding financial access and empowering micro-entrepreneurs."
    badge="Join Our Team"
    :breadcrumbs="['Resources' => '/resources/career', 'Career' => '']"
/>

<section class="container-xl py-5">
    <h4 class="fw-bold mb-4">Open Positions</h4>
    <div class="row g-4 mb-5">
        <div class="col-md-6">
            <x-ui.card class="p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="fw-bold mb-0">Loan Officer</h5>
                    <span class="badge bg-primary-subtle text-primary">Full-Time</span>
                </div>
                <small class="text-muted d-block mb-3"><i class="bi bi-geo-alt me-1"></i> Multiple Branch Locations</small>
                <p class="text-muted small">Responsible for customer onboarding, loan application appraisal, and monitoring portfolio repayments.</p>
                <a href="{{ url('/contact') }}" class="btn btn-outline-primary btn-sm rounded-pill font-semibold">Apply for Position</a>
            </x-ui.card>
        </div>

        <div class="col-md-6">
            <x-ui.card class="p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="fw-bold mb-0">Collection Officer</h5>
                    <span class="badge bg-success-subtle text-success">Full-Time</span>
                </div>
                <small class="text-muted d-block mb-3"><i class="bi bi-geo-alt me-1"></i> Field Route Operations</small>
                <p class="text-muted small">Conducting doorstep collection visits, issuing mobile ERP receipts, and maintaining daily route logs.</p>
                <a href="{{ url('/contact') }}" class="btn btn-outline-success btn-sm rounded-pill font-semibold">Apply for Position</a>
            </x-ui.card>
        </div>
    </div>
</section>

<x-ui.cta />
@endsection
