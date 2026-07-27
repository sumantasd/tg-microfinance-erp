@extends('layouts.public')

@section('title', 'Management Team - TG Microfinance ERP')
@section('meta_description', 'Meet the executive management team overseeing daily microfinance operations and branch execution.')

@section('content')
<x-ui.page-banner
    title="Executive Management Team"
    subtitle="Seasoned microfinance operations, technology, and risk leaders driving daily excellence."
    badge="Executive Leadership"
    :breadcrumbs="['About' => '/about', 'Management Team' => '']"
/>

<section class="container-xl py-5">
    <div class="row g-4">
        @forelse($managementMembers as $member)
            <div class="col-md-4">
                <x-ui.card class="h-100 text-center p-4 tg-hover-lift">
                    @if($member->photo_url)
                        <img src="{{ $member->photo_url }}" alt="{{ $member->name }}" class="rounded-circle border shadow-sm mx-auto mb-3" style="width: 90px; height: 90px; object-fit: cover;">
                    @else
                        <div class="bg-success-subtle text-success rounded-circle p-4 mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                            <i class="bi bi-person-circle fs-1"></i>
                        </div>
                    @endif
                    <h5 class="fw-bold mb-1">{{ $member->name }}</h5>
                    <span class="badge bg-success-subtle text-success mb-3">{{ $member->designation }}</span>
                    <p class="text-muted small mb-3">{{ $member->bio }}</p>
                    @if(isset($member->social_links['linkedin']) && $member->social_links['linkedin'])
                        <a href="{{ $member->social_links['linkedin'] }}" target="_blank" rel="noopener" class="btn btn-outline-success btn-sm rounded-circle"><i class="bi bi-linkedin"></i></a>
                    @endif
                </x-ui.card>
            </div>
        @empty
            <div class="col-12 text-center py-5 text-muted">
                <i class="bi bi-people fs-1 text-success mb-2 d-block opacity-50"></i>
                <p class="mb-0">No management team members currently listed.</p>
            </div>
        @endforelse
    </div>
</section>

<x-ui.cta />
@endsection
