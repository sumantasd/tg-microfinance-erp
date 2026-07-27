@extends('layouts.public')

@section('title', 'Forms & Downloads - ' . ($settings->company_name ?? 'TG Microfinance ERP'))
@section('meta_description', 'Download official loan application forms, passbook request documents, and financial disclosure reports.')

@section('content')
<x-ui.page-banner
    title="Forms & Document Downloads"
    subtitle="Access official printable loan application forms, client disclosure statements, and annual reports."
    badge="Document Center"
    :breadcrumbs="['Resources' => '/resources/downloads', 'Downloads' => '']"
/>

<section class="container-xl py-5">
    @if(session('error'))
        <div class="alert alert-danger mb-4">
            <i class="bi bi-exclamation-circle me-1"></i>{{ session('error') }}
        </div>
    @endif

    <div class="row g-4">
        @forelse($downloads as $item)
            <div class="col-md-6">
                <x-ui.card class="p-4 d-flex flex-row align-items-center justify-content-between h-100 tg-hover-lift">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-primary-subtle text-primary rounded-circle p-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 52px; height: 52px;">
                            <i class="bi bi-file-earmark-pdf fs-3"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-1 text-dark">{{ $item->title }}</h6>
                            @if($item->description)
                                <p class="text-muted small mb-0">{{ $item->description }}</p>
                            @else
                                <small class="text-muted">{{ $item->file_extension }} Document File</small>
                            @endif
                        </div>
                    </div>
                    <a href="{{ route('public.resources.downloads.file', $item->id) }}" class="btn btn-outline-primary btn-sm rounded-pill px-3 fw-bold ms-2 flex-shrink-0">
                        <i class="bi bi-download me-1"></i> Download
                    </a>
                </x-ui.card>
            </div>
        @empty
            <div class="col-12 text-center py-5">
                <div class="bg-light rounded-4 p-5 max-w-md mx-auto border">
                    <i class="bi bi-file-earmark-arrow-down fs-1 text-muted opacity-50 mb-3 d-block"></i>
                    <h5 class="fw-bold text-dark">No Download Files Available</h5>
                    <p class="text-muted small mb-0">Check back soon for downloadable loan forms and disclosures.</p>
                </div>
            </div>
        @endforelse
    </div>
</section>

<x-ui.cta />
@endsection
