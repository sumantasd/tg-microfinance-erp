@extends('layouts.admin')

@section('title', 'Cashbook — Select Branch')

@section('content')
<div class="container-fluid px-4 py-3">
    <!-- Header Title & Action Buttons -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1 font-heading text-dark">
                <i class="bi bi-journal-bookmark-fill text-success me-2"></i>Cashbook — Select Branch
            </h4>
            <p class="text-muted small mb-0">Select an authorized branch to view and manage its daily physical & digital cashbook register.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            @can('bank_deposit.view')
                <a href="{{ route('admin.bank-deposits.index') }}" class="btn btn-outline-info rounded-pill px-3 shadow-sm font-monospace fw-bold">
                    <i class="bi bi-bank me-1"></i> Bank Deposits
                </a>
            @endcan
        </div>
    </div>

    <!-- Search & Filter Toolbar -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
        <div class="card-body p-3">
            <form action="{{ route('admin.cash-book.index') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-md-8 col-lg-6">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Search branch by name, code, city, or address..." class="form-control bg-light border-0">
                    </div>
                </div>
                <div class="col-md-4 col-lg-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary rounded-3 px-3 fw-bold flex-grow-1">
                        <i class="bi bi-funnel me-1"></i> Filter Branches
                    </button>
                    @if(!empty($search))
                        <a href="{{ route('admin.cash-book.index') }}" class="btn btn-light rounded-3 px-3 text-secondary" title="Reset Search">
                            <i class="bi bi-x-circle me-1"></i> Clear
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Branch Selection Grid -->
    <div class="row g-3">
        @forelse($branches as $b)
            <div class="col-md-6 col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 h-100 p-3 bg-white">
                    <div class="card-body p-1 d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                <div>
                                    <h5 class="fw-bold text-dark mb-0 font-heading">{{ $b->name }}</h5>
                                    <small class="text-muted d-block mt-0.5">
                                        <i class="bi bi-building me-1 text-secondary"></i>{{ $b->company->name ?? 'Grihalaxmi Finance' }}
                                    </small>
                                </div>
                                <span class="badge bg-primary-subtle text-primary font-monospace rounded-pill px-2.5 py-1" style="font-size: 0.75rem;">
                                    <i class="bi bi-tag-fill me-1"></i>{{ $b->code }}
                                </span>
                            </div>

                            <div class="mb-3">
                                <small class="text-muted d-block">
                                    <i class="bi bi-geo-alt-fill me-1 text-danger opacity-75"></i>
                                    {{ $b->city ? ($b->city . ($b->state ? ', ' . $b->state : '')) : ($b->address ?? 'Main Branch') }}
                                </small>
                            </div>

                            <!-- Current Cash Balance Box -->
                            <div class="bg-light border-0 rounded-3 p-3 mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="text-uppercase small text-muted font-monospace fw-semibold" style="font-size: 0.68rem; letter-spacing: 0.5px;">Current Cash Balance</span>
                                    <span class="badge bg-success-subtle text-success rounded-pill font-monospace" style="font-size: 0.65rem;">LIVE</span>
                                </div>
                                <h3 class="fw-bold font-monospace text-success mb-0">
                                    ₹{{ number_format($branchBalances[$b->id] ?? 0, 2) }}
                                </h3>
                            </div>
                        </div>

                        <!-- Action Button -->
                        <div>
                            <a href="{{ route('admin.cash-book.branch', $b->id) }}" class="btn btn-primary rounded-pill w-100 fw-bold shadow-sm d-flex align-items-center justify-content-center gap-2 py-2">
                                <i class="bi bi-journal-check fs-6"></i>
                                <span>View Cashbook</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-4 bg-white p-5 text-center">
                    <i class="bi bi-building-x fs-1 d-block mb-3 text-secondary opacity-50"></i>
                    <h5 class="fw-bold text-dark mb-1">No Authorized Active Branches Found</h5>
                    <p class="text-muted small mb-0">There are no active branches matching your search criteria or assigned authorization scoping.</p>
                </div>
            </div>
        @endforelse
    </div>
</div>
@endsection
