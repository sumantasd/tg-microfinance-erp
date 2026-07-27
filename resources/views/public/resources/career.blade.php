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
        @forelse($jobs as $job)
            <div class="col-md-6">
                <x-ui.card class="p-4 h-100 tg-hover-lift">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h5 class="fw-bold mb-0 text-dark">{{ $job->title }}</h5>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle">{{ $job->job_type }}</span>
                    </div>
                    <div class="d-flex flex-wrap gap-3 small text-muted mb-3">
                        <span><i class="bi bi-geo-alt me-1 text-primary"></i>{{ $job->location }}</span>
                        @if($job->deadline)
                            <span><i class="bi bi-clock me-1 text-warning"></i>Deadline: {{ $job->deadline->format('M d, Y') }}</span>
                        @endif
                    </div>
                    <p class="text-muted small mb-3">{{ $job->short_description }}</p>
                    @if($job->requirements)
                        <div class="p-3 bg-light rounded-3 mb-3 small text-secondary">
                            <strong>Requirements:</strong>
                            <div>{!! nl2br(e($job->requirements)) !!}</div>
                        </div>
                    @endif
                    <div class="d-flex justify-content-between align-items-center border-top pt-3">
                        <small class="text-muted"><i class="bi bi-envelope me-1"></i>{{ $job->application_email ?? 'hr@tgmicrofinance.org' }}</small>
                        <a href="mailto:{{ $job->application_email ?? 'hr@tgmicrofinance.org' }}?subject={{ urlencode('Application for ' . $job->title) }}" class="btn btn-outline-primary btn-sm rounded-pill font-semibold">
                            {{ $job->apply_button_text ?? 'Apply for Position' }}
                        </a>
                    </div>
                </x-ui.card>
            </div>
        @empty
            <div class="col-12 text-center py-5 text-muted">
                <i class="bi bi-briefcase fs-1 text-primary mb-2 d-block opacity-50"></i>
                <p class="mb-0">There are currently no active job openings available. Please check back soon!</p>
            </div>
        @endforelse
    </div>
</section>

<x-ui.cta />
@endsection
