@extends('layouts.public')

@section('title', 'Corporate News & Announcements - ' . ($settings->company_name ?? 'TG Microfinance ERP'))
@section('meta_description', 'Latest news, financial updates, and community press releases from ' . ($settings->company_name ?? 'TG Microfinance'))

@section('content')
<x-ui.page-banner
    title="Corporate News & Press Releases"
    subtitle="Stay updated with our latest operational announcements, financial audits, and strategic partnership disclosures."
    badge="Media Center"
    :breadcrumbs="['Resources' => '/resources/news', 'News' => '']"
/>

<section class="container-xl py-5">
    <div class="row g-4">
        @forelse($newsList as $item)
            <div class="col-md-6 col-lg-4">
                <x-ui.card class="p-0 overflow-hidden h-100 tg-hover-lift">
                    @if($item->featured_image_url)
                        <img src="{{ $item->featured_image_url }}" alt="{{ $item->title }}" class="img-fluid border-bottom" style="width: 100%; height: 200px; object-fit: cover;">
                    @else
                        <div class="bg-light p-4 text-center border-bottom">
                            <i class="bi bi-newspaper fs-1 text-primary opacity-50"></i>
                        </div>
                    @endif
                    <div class="p-4 d-flex flex-column justify-content-between h-100">
                        <div>
                            <small class="text-primary fw-bold d-block mb-2">
                                <i class="bi bi-calendar-event me-1"></i> {{ $item->published_date ? $item->published_date->format('F d, Y') : $item->created_at->format('F d, Y') }}
                            </small>
                            <h5 class="fw-bold mb-2 text-dark">{{ $item->title }}</h5>
                            <p class="text-muted small mb-3">
                                {{ Str::limit($item->short_description ?? strip_tags($item->content), 120) }}
                            </p>
                        </div>
                        <div>
                            <a href="{{ route('public.resources.news.show', $item->slug) }}" class="btn btn-outline-primary btn-sm rounded-pill px-3 fw-bold">
                                Read Full Story <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                </x-ui.card>
            </div>
        @empty
            <div class="col-12 text-center py-5">
                <div class="bg-light rounded-4 p-5 max-w-md mx-auto border">
                    <i class="bi bi-newspaper fs-1 text-muted opacity-50 mb-3 d-block"></i>
                    <h5 class="fw-bold text-dark">No Articles Published Yet</h5>
                    <p class="text-muted small mb-0">Check back soon for latest corporate announcements and financial updates.</p>
                </div>
            </div>
        @endforelse
    </div>

    <div class="mt-4">
        {{ $newsList->links() }}
    </div>
</section>

<x-ui.cta />
@endsection
