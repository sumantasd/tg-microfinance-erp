@extends('layouts.public')

@section('title', 'Forms & Downloads - TG Microfinance ERP')
@section('meta_description', 'Download official loan application forms, passbook request documents, and financial disclosure reports.')

@section('content')
<x-ui.page-banner
    title="Forms & Document Downloads"
    subtitle="Access official printable loan application forms, client disclosure statements, and annual reports."
    badge="Document Center"
    :breadcrumbs="['Resources' => '/resources/downloads', 'Downloads' => '']"
/>

<section class="container-xl py-5">
    <div class="row g-4">
        <div class="col-md-6">
            <x-ui.card class="p-4 d-flex flex-row align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-primary-subtle text-primary rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                        <i class="bi bi-file-earmark-pdf fs-4"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-0">Individual Loan Application Form</h6>
                        <small class="text-muted">PDF Document (Version 2.4 - 1.2 MB)</small>
                    </div>
                </div>
                <a href="#" class="btn btn-outline-primary btn-sm rounded-pill"><i class="bi bi-download me-1"></i> Download</a>
            </x-ui.card>
        </div>

        <div class="col-md-6">
            <x-ui.card class="p-4 d-flex flex-row align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-success-subtle text-success rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                        <i class="bi bi-file-earmark-pdf fs-4"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-0">Group Savings Registration Kit</h6>
                        <small class="text-muted">PDF Document (Version 1.8 - 2.0 MB)</small>
                    </div>
                </div>
                <a href="#" class="btn btn-outline-success btn-sm rounded-pill"><i class="bi bi-download me-1"></i> Download</a>
            </x-ui.card>
        </div>
    </div>
</section>

<x-ui.cta />
@endsection
