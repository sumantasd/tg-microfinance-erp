@extends('layouts.public')

@section('title', 'Corporate Services - TG Microfinance ERP')
@section('meta_description', 'Explore our comprehensive range of microfinance services, doorstep field collections, digital banking, and financial coaching.')

@section('content')
<x-ui.page-banner
    title="Corporate & Field Services"
    subtitle="Comprehensive credit management, doorstep field collection, and digital banking services."
    badge="Services"
    :breadcrumbs="['Services' => '/services']"
/>

<section class="container-xl py-5">
    <div class="row g-4">
        @forelse($services as $item)
            <div class="col-md-4">
                <x-ui.card class="h-100 p-4 tg-hover-lift">
                    @if($item->banner_image_url)
                        <img src="{{ $item->banner_image_url }}" alt="{{ $item->title }}" class="img-fluid rounded-3 mb-3 border" style="width: 100%; height: 160px; object-fit: cover;">
                    @else
                        <div class="bg-primary-subtle text-primary rounded-circle p-3 mb-3 d-inline-flex align-items-center justify-content-center" style="width: 56px; height: 56px;">
                            <i class="bi {{ $item->icon ?? 'bi-gear' }} fs-2"></i>
                        </div>
                    @endif
                    <h5 class="fw-bold mb-2 text-dark">{{ $item->title }}</h5>
                    <p class="text-muted small mb-4">{{ $item->short_description }}</p>
                    <a href="{{ route('public.services.show', $item->slug) }}" class="btn btn-outline-primary btn-sm rounded-pill font-semibold">
                        Learn More <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </x-ui.card>
            </div>
        @empty
            <div class="col-12 text-center py-5 text-muted">
                <i class="bi bi-gear fs-1 text-primary mb-2 d-block opacity-50"></i>
                <p class="mb-0">No corporate services currently listed.</p>
            </div>
        @endforelse
    </div>
</section>

<x-ui.cta />
@endsection
