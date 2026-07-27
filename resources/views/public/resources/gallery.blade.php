@extends('layouts.public')

@section('title', 'Media Gallery - ' . ($settings->company_name ?? 'TG Microfinance ERP'))
@section('meta_description', 'Explore photos from community workshops, branch openings, and microfinance member success stories.')

@section('content')
<x-ui.page-banner
    title="Media & Photo Gallery"
    subtitle="Highlights of our community outreach programs, branch inaugurations, and member empowerment events."
    badge="Media Library"
    :breadcrumbs="['Resources' => '/resources/gallery', 'Gallery' => '']"
/>

<section class="container-xl py-5">
    @if(isset($categories) && $categories->count() > 0)
        <div class="d-flex flex-wrap gap-2 justify-content-center mb-4">
            <a href="{{ route('public.resources.gallery') }}" class="btn btn-sm rounded-pill px-3 fw-bold {{ !request('category') ? 'btn-primary' : 'btn-outline-secondary' }}">
                All Photos
            </a>
            @foreach($categories as $cat)
                <a href="{{ route('public.resources.gallery', ['category' => $cat]) }}" class="btn btn-sm rounded-pill px-3 fw-bold {{ request('category') === $cat ? 'btn-primary' : 'btn-outline-secondary' }}">
                    {{ $cat }}
                </a>
            @endforeach
        </div>
    @endif

    <div class="row g-4">
        @forelse($galleries as $item)
            <div class="col-md-6 col-lg-4">
                <x-ui.card class="p-0 overflow-hidden h-100 tg-hover-lift">
                    @if($item->image_url)
                        <img src="{{ $item->image_url }}" alt="{{ $item->title }}" class="img-fluid border-bottom" style="width: 100%; height: 240px; object-fit: cover;">
                    @else
                        <div class="bg-dark text-white p-5 text-center">
                            <i class="bi bi-image fs-1 text-muted opacity-50"></i>
                        </div>
                    @endif
                    <div class="p-3">
                        @if($item->category)
                            <span class="badge bg-primary-subtle text-primary mb-1">{{ $item->category }}</span>
                        @endif
                        <h6 class="fw-bold mb-0 text-dark">{{ $item->title }}</h6>
                    </div>
                </x-ui.card>
            </div>
        @empty
            <div class="col-12 text-center py-5">
                <div class="bg-light rounded-4 p-5 max-w-md mx-auto border">
                    <i class="bi bi-images fs-1 text-muted opacity-50 mb-3 d-block"></i>
                    <h5 class="fw-bold text-dark">No Gallery Photos Found</h5>
                    <p class="text-muted small mb-0">Check back soon for community events and outreach event pictures.</p>
                </div>
            </div>
        @endforelse
    </div>
</section>

<x-ui.cta />
@endsection
