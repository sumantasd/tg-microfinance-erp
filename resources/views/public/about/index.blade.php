@extends('layouts.public')

@section('title', 'About Us - TG Microfinance ERP')
@section('meta_description', 'Learn about TG Microfinance, our corporate history, leadership team, and social impact mission.')

@section('content')
<x-ui.page-banner
    title="About TG Microfinance"
    subtitle="Empowering micro-entrepreneurs and underserved communities with financial literacy, transparent credit, and sustainable growth."
    badge="Corporate Overview"
    :breadcrumbs="['About Us' => '']"
/>

<section class="container-xl py-5">
    <div class="row g-4 align-items-center">
        <div class="col-lg-6">
            <h2 class="fw-bold mb-3">Pioneering Inclusive Micro-Banking Solutions</h2>
            <p class="lead text-muted">Founded with a mission to bridge the financial inclusion gap, TG Microfinance provides flexible credit and group savings products to over 50,000 active members nationwide.</p>
            <p class="text-muted">Our enterprise operations combine field-level officer support with digital branch technology, ensuring fast loan processing, secure vault management, and strict regulatory compliance.</p>
        </div>
        <div class="col-lg-6">
            <x-ui.card class="p-4 bg-light">
                <h5 class="fw-bold mb-3"><i class="bi bi-award text-primary me-2"></i>Corporate Highlights</h5>
                <ul class="list-unstyled mb-0">
                    <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>85+ Nationwide Branch Locations</li>
                    <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Over $120 Million Disbursed in Micro-loans</li>
                    <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Double-entry Audited Financial Accounting</li>
                    <li class="mb-0"><i class="bi bi-check-circle-fill text-success me-2"></i>Certified Member Protection Standards</li>
                </ul>
            </x-ui.card>
        </div>
    </div>
</section>

<x-ui.cta />
@endsection
