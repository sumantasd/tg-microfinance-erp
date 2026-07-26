@extends('layouts.public')

@section('title', 'Corporate News & Announcements - TG Microfinance ERP')
@section('meta_description', 'Latest news, financial updates, and community press releases from TG Microfinance.')

@section('content')
<x-ui.page-banner
    title="Corporate News & Press Releases"
    subtitle="Stay updated with our latest operational announcements, financial audits, and strategic partnership disclosures."
    badge="Media Center"
    :breadcrumbs="['Resources' => '/resources/news', 'News' => '']"
/>

<section class="container-xl py-5">
    <div class="row g-4">
        <div class="col-md-6">
            <x-ui.card class="p-4 h-100">
                <span class="badge bg-primary-subtle text-primary mb-2" style="width: fit-content;">Press Release</span>
                <h5 class="fw-bold mb-2">TG Microfinance Expands Branch Network to 85 Locations</h5>
                <small class="text-muted d-block mb-3"><i class="bi bi-calendar me-1"></i> October 14, 2025</small>
                <p class="text-muted small mb-0">Our new branch operations in the Eastern Region deliver accessible micro-credit counters and field collection services to underserved agricultural markets.</p>
            </x-ui.card>
        </div>
        <div class="col-md-6">
            <x-ui.card class="p-4 h-100">
                <span class="badge bg-success-subtle text-success mb-2" style="width: fit-content;">Financial Audit</span>
                <h5 class="fw-bold mb-2">Annual Financial Audit Confirms Outstanding Portfolio Health</h5>
                <small class="text-muted d-block mb-3"><i class="bi bi-calendar me-1"></i> September 28, 2025</small>
                <p class="text-muted small mb-0">Independent external audit results highlight a 99.2% repayment efficiency rate and robust general ledger controls across all branch vaults.</p>
            </x-ui.card>
        </div>
    </div>
</section>

<x-ui.cta />
@endsection
