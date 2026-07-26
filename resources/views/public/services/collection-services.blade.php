@extends('layouts.public')

@section('title', 'Collection Services - TG Microfinance ERP')
@section('meta_description', 'Doorstep collection services and automated field route posting with TG Microfinance.')

@section('content')
<x-ui.page-banner
    title="Field Collection Services"
    subtitle="Convenient doorstep daily and weekly collection services managed by authorized branch collection officers."
    badge="Doorstep Banking"
    :breadcrumbs="['Services' => '/services/digital-banking', 'Collection Services' => '']"
/>

<section class="container-xl py-5">
    <div class="row g-4 align-items-center">
        <div class="col-lg-7">
            <h3 class="fw-bold mb-3">Doorstep Repayment & Deposit Collections</h3>
            <p class="text-muted lead">To save micro-borrowers travel time and business disruption, our field collection officers perform scheduled collection visits directly at market stalls, group meetings, and enterprise premises.</p>
            <div class="row g-3 mt-2">
                <div class="col-md-6">
                    <x-ui.card class="p-3 bg-light">
                        <h6 class="fw-bold mb-1"><i class="bi bi-clock-history text-primary me-2"></i>Scheduled Route Sheets</h6>
                        <small class="text-muted">Pre-planned route sheets ensure transparent daily collection schedules.</small>
                    </x-ui.card>
                </div>
                <div class="col-md-6">
                    <x-ui.card class="p-3 bg-light">
                        <h6 class="fw-bold mb-1"><i class="bi bi-receipt text-success me-2"></i>Instant Digital Proof</h6>
                        <small class="text-muted">Every transaction generates an electronic receipt reconciled at branch end of day.</small>
                    </x-ui.card>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <x-ui.card class="p-4 border-start border-4 border-primary">
                <h5 class="fw-bold mb-3 text-dark"><i class="bi bi-journal-check text-primary me-2"></i>Collection Operations</h5>
                <p class="text-muted small">All field collection postings are cryptographically logged with GPS coordinates and officer IDs to eliminate collection variance and safeguard client deposits.</p>
            </x-ui.card>
        </div>
    </div>
</section>

<x-ui.cta />
@endsection
