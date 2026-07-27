@extends('layouts.public')

@section('title', $article->title . ' - ' . ($settings->company_name ?? 'TG Microfinance ERP'))
@section('meta_description', Str::limit($article->short_description ?? strip_tags($article->content), 150))

@section('content')
<x-ui.page-banner
    :title="$article->title"
    :subtitle="$article->short_description ?? 'Corporate News Article'"
    badge="Media Center"
    :breadcrumbs="['Resources' => '/resources/news', 'News' => '/resources/news', $article->title => '']"
/>

<section class="container-xl py-5">
    <div class="row g-4 g-lg-5">
        <div class="col-lg-8">
            <x-ui.card class="p-4 p-md-5 border-0 shadow-sm">
                <div class="d-flex align-items-center gap-3 text-muted small mb-4 pb-3 border-bottom">
                    <span><i class="bi bi-calendar-event text-primary me-1"></i> Published: {{ $article->published_date ? $article->published_date->format('F d, Y') : $article->created_at->format('F d, Y') }}</span>
                    <span><i class="bi bi-building text-primary me-1"></i> {{ $settings->company_name ?? 'TG Microfinance' }}</span>
                </div>

                @if($article->featured_image_url)
                    <div class="mb-4">
                        <img src="{{ $article->featured_image_url }}" alt="{{ $article->title }}" class="img-fluid rounded-4 shadow-sm border" style="width: 100%; max-height: 420px; object-fit: cover;">
                    </div>
                @endif

                @if($article->short_description)
                    <p class="lead fw-semibold text-dark mb-4 p-3 bg-light rounded-3 border-start border-4 border-primary">
                        {{ $article->short_description }}
                    </p>
                @endif

                <div class="article-content text-secondary lh-lg mb-4">
                    {!! $article->content ?? '<p>Full content is currently unavailable.</p>' !!}
                </div>

                <div class="pt-4 border-top d-flex justify-content-between align-items-center">
                    <a href="{{ route('public.resources.news') }}" class="btn btn-outline-secondary rounded-pill btn-sm px-3 fw-bold">
                        <i class="bi bi-arrow-left me-1"></i> Back to News
                    </a>
                    <div class="d-flex gap-2">
                        <a href="https://facebook.com/sharer/sharer.php?u={{ urlencode(url()->current()) }}" target="_blank" class="btn btn-light btn-sm rounded-circle"><i class="bi bi-facebook"></i></a>
                        <a href="https://twitter.com/intent/tweet?url={{ urlencode(url()->current()) }}" target="_blank" class="btn btn-light btn-sm rounded-circle"><i class="bi bi-twitter-x"></i></a>
                        <a href="https://linkedin.com/shareArticle?url={{ urlencode(url()->current()) }}" target="_blank" class="btn btn-light btn-sm rounded-circle"><i class="bi bi-linkedin"></i></a>
                    </div>
                </div>
            </x-ui.card>
        </div>

        <div class="col-lg-4">
            <div class="position-sticky" style="top: 100px;">
                <h5 class="fw-bold text-dark mb-3">Recent News</h5>
                <div class="d-flex flex-column gap-3">
                    @forelse($recentNews as $recent)
                        <x-ui.card class="p-3 shadow-sm tg-hover-lift">
                            <small class="text-primary fw-semibold d-block mb-1">
                                <i class="bi bi-calendar-event me-1"></i> {{ $recent->published_date ? $recent->published_date->format('M d, Y') : $recent->created_at->format('M d, Y') }}
                            </small>
                            <h6 class="fw-bold mb-2">
                                <a href="{{ route('public.resources.news.show', $recent->slug) }}" class="text-dark text-decoration-none hover-primary">
                                    {{ $recent->title }}
                                </a>
                            </h6>
                            <p class="text-muted small mb-0">{{ Str::limit($recent->short_description ?? strip_tags($recent->content), 80) }}</p>
                        </x-ui.card>
                    @empty
                        <p class="text-muted small">No other recent articles.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</section>

<x-ui.cta />
@endsection
