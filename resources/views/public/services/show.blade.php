@extends('layouts.public')

@section('title', ($service->meta_title ?? $service->title . ' - TG Microfinance ERP'))
@section('meta_description', ($service->meta_description ?? $service->short_description))

@section('content')
<x-ui.page-banner
    title="{{ $service->title }}"
    subtitle="{{ $service->short_description }}"
    badge="Corporate Service"
    :breadcrumbs="['Services' => '/services', $service->title => '']"
/>

<section class="container-xl py-5">
    <div class="row g-4">
        <div class="col-lg-8">
            <x-ui.card class="p-4 p-md-5 mb-4">
                @if($service->banner_image_url)
                    <img src="{{ $service->banner_image_url }}" alt="{{ $service->title }}" class="img-fluid rounded-4 mb-4 border shadow-sm" style="width: 100%; max-height: 340px; object-fit: cover;">
                @endif
                <div class="content-body text-secondary lh-lg">
                    {!! $service->content !!}
                </div>
            </x-ui.card>
        </div>

        <div class="col-lg-4">
            <x-ui.card class="p-4 border-0 shadow-sm bg-light">
                <h5 class="fw-bold text-dark mb-3"><i class="bi bi-headset me-2 text-primary"></i>Service Inquiry</h5>
                <p class="text-muted small mb-3">Speak to our field officers or customer support team for service details.</p>
                <a href="{{ url('/contact') }}" class="btn btn-primary w-100 rounded-pill fw-bold shadow-sm">
                    Contact Us Now <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </x-ui.card>
        </div>
    </div>
</section>

<x-ui.cta />
@endsection
