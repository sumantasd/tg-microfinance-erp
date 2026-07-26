@extends('layouts.public')

@section('title', 'Branch Network & Locator - TG Microfinance ERP')
@section('meta_description', 'Locate TG Microfinance branch offices, counter opening hours, and contact details nationwide.')

@section('content')
<x-ui.page-banner
    title="Branch Network & Locator"
    subtitle="Locate your nearest TG Microfinance branch office for counter disbursements, deposits, and officer guidance."
    badge="Nationwide Network"
    :breadcrumbs="['Branches' => '']"
/>

<section class="container-xl py-5">
    <div class="row g-4">
        <div class="col-md-4">
            <x-ui.card class="p-4 border-start border-4 border-primary h-100">
                <span class="badge bg-primary-subtle text-primary mb-2" style="width: fit-content;">Head Office</span>
                <h5 class="fw-bold mb-1">Central Head Office Branch</h5>
                <p class="text-muted small mb-2"><i class="bi bi-geo-alt text-primary me-1"></i> 100 Financial Avenue, Suite 500</p>
                <p class="text-muted small mb-2"><i class="bi bi-telephone text-primary me-1"></i> +1 (800) 555-0199</p>
                <p class="text-muted small mb-0"><i class="bi bi-clock text-primary me-1"></i> Mon - Fri: 8:00 AM - 5:00 PM</p>
            </x-ui.card>
        </div>

        <div class="col-md-4">
            <x-ui.card class="p-4 border-start border-4 border-success h-100">
                <span class="badge bg-success-subtle text-success mb-2" style="width: fit-content;">Metro Branch</span>
                <h5 class="fw-bold mb-1">Commercial Market Branch</h5>
                <p class="text-muted small mb-2"><i class="bi bi-geo-alt text-success me-1"></i> 45 Market Square Plaza</p>
                <p class="text-muted small mb-2"><i class="bi bi-telephone text-success me-1"></i> +1 (800) 555-0210</p>
                <p class="text-muted small mb-0"><i class="bi bi-clock text-success me-1"></i> Mon - Sat: 8:30 AM - 4:30 PM</p>
            </x-ui.card>
        </div>

        <div class="col-md-4">
            <x-ui.card class="p-4 border-start border-4 border-info h-100">
                <span class="badge bg-info-subtle text-info mb-2" style="width: fit-content;">Regional Branch</span>
                <h5 class="fw-bold mb-1">Eastern Agricultural Branch</h5>
                <p class="text-muted small mb-2"><i class="bi bi-geo-alt text-info me-1"></i> 88 Rural Hub Highway</p>
                <p class="text-muted small mb-2"><i class="bi bi-telephone text-info me-1"></i> +1 (800) 555-0344</p>
                <p class="text-muted small mb-0"><i class="bi bi-clock text-info me-1"></i> Mon - Fri: 8:00 AM - 4:00 PM</p>
            </x-ui.card>
        </div>
    </div>
</section>

<x-ui.cta />
@endsection
