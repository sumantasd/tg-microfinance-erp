@extends('layouts.admin')

@section('title', 'Search Results - Grihalaxmi Finance ERP')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1"><i class="bi bi-search text-primary me-2"></i>Global Search Results</h4>
        <p class="text-muted small mb-0">
            @if(strlen($query) > 0)
                Showing {{ $totalCount }} matching record(s) for query: <strong class="text-dark">"{{ $query }}"</strong>
            @else
                Enter a search term to find records across the ERP.
            @endif
        </p>
    </div>
    <div class="mt-3 mt-md-0">
        <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary rounded-pill px-3 btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
        </a>
    </div>
</div>

<!-- Search Input Card -->
<x-ui.card class="p-3 shadow-sm border-0 mb-4 bg-white">
    <form method="GET" action="{{ route('admin.search') }}" class="row g-2 align-items-center">
        <div class="col-md-10">
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" name="q" class="form-control border-start-0 ps-0" placeholder="Search by customer name, code, phone, loan number, product SKU, employee ID..." value="{{ $query }}" autofocus>
            </div>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary w-100 fw-bold"><i class="bi bi-search me-1"></i> Search</button>
        </div>
    </form>
</x-ui.card>

@if(strlen($query) > 0)
    @if($totalCount > 0)
        <div class="row g-4">
            @foreach($categories as $categoryName => $items)
                <div class="col-12">
                    <x-ui.card class="shadow-sm border-0 overflow-hidden">
                        <div class="card-header bg-light border-0 py-2.5 px-3.5 d-flex justify-content-between align-items-center">
                            <h6 class="fw-bold text-dark mb-0 font-heading">
                                <i class="bi {{ $items[0]['icon'] ?? 'bi-folder' }} text-primary me-2"></i>{{ $categoryName }}
                            </h6>
                            <span class="badge bg-primary-subtle text-primary rounded-pill">{{ count($items) }} found</span>
                        </div>
                        <div class="list-group list-group-flush">
                            @foreach($items as $item)
                                <a href="{{ $item['url'] }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center p-3">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="bg-light text-primary rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; flex-shrink: 0;">
                                            <i class="bi {{ $item['icon'] }} fs-5"></i>
                                        </div>
                                        <div>
                                            <strong class="d-block text-dark">{{ $item['title'] }}</strong>
                                            <small class="text-muted">{{ $item['subtitle'] }}</small>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        @if(isset($item['badge']))
                                            <span class="badge {{ $item['badge_class'] ?? 'bg-secondary' }} px-2.5 py-1">{{ $item['badge'] }}</span>
                                        @endif
                                        <i class="bi bi-chevron-right text-muted small"></i>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </x-ui.card>
                </div>
            @endforeach
        </div>
    @else
        <x-ui.card class="shadow-sm border-0 text-center py-5">
            <i class="bi bi-search fs-1 text-muted d-block mb-2"></i>
            <h5 class="fw-bold text-dark">No matching records found.</h5>
            <p class="text-muted small mb-0">We couldn't find anything matching "<strong>{{ $query }}</strong>". Try searching with different keywords, customer codes, phone numbers, or account numbers.</p>
        </x-ui.card>
    @endif
@else
    <x-ui.card class="shadow-sm border-0 text-center py-5">
        <i class="bi bi-compass fs-1 text-primary d-block mb-2"></i>
        <h5 class="fw-bold text-dark">Unified Microfinance Global Search</h5>
        <p class="text-muted small mb-0">Search instantly across Customers, Customer Groups, Loan Accounts, Credit Applications, Loan Schemes, Branch Inventory Products, and Staff Employees.</p>
    </x-ui.card>
@endif

@endsection
