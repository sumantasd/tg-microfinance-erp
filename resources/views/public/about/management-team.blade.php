@extends('layouts.public')

@section('title', 'Management Team - TG Microfinance ERP')
@section('meta_description', 'Meet the executive management team overseeing daily microfinance operations and branch execution.')

@section('content')
<x-ui.page-banner
    title="Executive Management Team"
    subtitle="Seasoned microfinance operations, technology, and risk leaders driving daily excellence."
    badge="Executive Leadership"
    :breadcrumbs="['About' => '/about', 'Management Team' => '']"
/>

<section class="container-xl py-5">
    <div class="row g-4">
        <div class="col-md-4">
            <x-ui.card class="h-100 text-center p-4">
                <div class="bg-primary-subtle text-primary rounded-circle p-4 mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                    <i class="bi bi-person-circle fs-1"></i>
                </div>
                <h5 class="fw-bold mb-1">David Chen</h5>
                <span class="badge bg-primary-subtle text-primary mb-3">Chief Executive Officer</span>
                <p class="text-muted small">Driving strategic expansion, technology integration, and institutional growth.</p>
            </x-ui.card>
        </div>
        <div class="col-md-4">
            <x-ui.card class="h-100 text-center p-4">
                <div class="bg-primary-subtle text-primary rounded-circle p-4 mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                    <i class="bi bi-person-circle fs-1"></i>
                </div>
                <h5 class="fw-bold mb-1">Sarah Jenkins</h5>
                <span class="badge bg-success-subtle text-success mb-3">Chief Operations Officer</span>
                <p class="text-muted small">Overseeing field collection networks, branch managers, and customer service teams.</p>
            </x-ui.card>
        </div>
        <div class="col-md-4">
            <x-ui.card class="h-100 text-center p-4">
                <div class="bg-primary-subtle text-primary rounded-circle p-4 mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                    <i class="bi bi-person-circle fs-1"></i>
                </div>
                <h5 class="fw-bold mb-1">Michael O'Connor</h5>
                <span class="badge bg-info-subtle text-info mb-3">Chief Financial Officer</span>
                <p class="text-muted small">Managing General Ledger accounting, vault liquidity, and audited financial statements.</p>
            </x-ui.card>
        </div>
    </div>
</section>

<x-ui.cta />
@endsection
