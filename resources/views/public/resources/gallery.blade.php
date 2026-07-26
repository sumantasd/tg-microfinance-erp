@extends('layouts.public')

@section('title', 'Media Gallery - TG Microfinance ERP')
@section('meta_description', 'Explore photos from community workshops, branch openings, and microfinance member success stories.')

@section('content')
<x-ui.page-banner
    title="Media & Photo Gallery"
    subtitle="Highlights of our community outreach programs, branch inaugurations, and member empowerment events."
    badge="Media Library"
    :breadcrumbs="['Resources' => '/resources/gallery', 'Gallery' => '']"
/>

<section class="container-xl py-5">
    <div class="row g-4">
        <div class="col-md-4">
            <x-ui.card class="p-0 overflow-hidden h-100">
                <div class="bg-dark text-white p-5 text-center">
                    <i class="bi bi-image fs-1 text-muted opacity-50"></i>
                    <p class="small text-muted mb-0 mt-2">[ Future CMS Image Asset ]</p>
                </div>
                <div class="p-4">
                    <h6 class="fw-bold mb-1">Annual Member Summit</h6>
                    <small class="text-muted">Celebrating micro-entrepreneur success stories.</small>
                </div>
            </x-ui.card>
        </div>
        <div class="col-md-4">
            <x-ui.card class="p-0 overflow-hidden h-100">
                <div class="bg-dark text-white p-5 text-center">
                    <i class="bi bi-image fs-1 text-muted opacity-50"></i>
                    <p class="small text-muted mb-0 mt-2">[ Future CMS Image Asset ]</p>
                </div>
                <div class="p-4">
                    <h6 class="fw-bold mb-1">Financial Literacy Workshop</h6>
                    <small class="text-muted">Training self-help group members on bookkeeping.</small>
                </div>
            </x-ui.card>
        </div>
        <div class="col-md-4">
            <x-ui.card class="p-0 overflow-hidden h-100">
                <div class="bg-dark text-white p-5 text-center">
                    <i class="bi bi-image fs-1 text-muted opacity-50"></i>
                    <p class="small text-muted mb-0 mt-2">[ Future CMS Image Asset ]</p>
                </div>
                <div class="p-4">
                    <h6 class="fw-bold mb-1">New Branch Opening Ceremony</h6>
                    <small class="text-muted">Expanding service reach to rural commercial hubs.</small>
                </div>
            </x-ui.card>
        </div>
    </div>
</section>

<x-ui.cta />
@endsection
