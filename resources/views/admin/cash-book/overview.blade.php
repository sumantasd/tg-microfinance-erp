@extends('layouts.admin')

@section('title', $branch->name . ' Cashbook Overview')

@section('content')
<div class="container-fluid px-4 py-3">
    <!-- Top Action Bar -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3">
        <div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <a href="{{ route('admin.cash-book.index') }}" class="btn btn-outline-secondary rounded-pill px-3 btn-sm fw-bold me-1">
                    <i class="bi bi-arrow-left me-1"></i> Back to Branch Selection
                </a>
                <h4 class="fw-bold mb-0 font-heading text-dark">
                    CASHBOOK OVERVIEW — <span class="text-primary">{{ strtoupper($branch->name) }} ({{ $branch->code }})</span>
                </h4>
            </div>
            <p class="text-muted small mb-0 mt-1">
                Company: <strong>{{ $company->name ?? 'Grihalaxmi Finance' }}</strong> | 
                Location: <strong>{{ $branch->city ?? ($branch->address ?? 'Headquarters') }}</strong>
            </p>
        </div>

        <div class="d-flex align-items-center gap-2">
            @if($currentCashBook)
                <a href="{{ route('admin.cash-book.show', $currentCashBook->id) }}" class="btn btn-primary rounded-pill px-3 btn-sm fw-bold shadow-sm">
                    <i class="bi bi-journal-text me-1"></i> Open Today's Register ({{ $currentCashBook->date->format('d/m/Y') }})
                </a>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-3 small py-2 px-3 mb-3" role="alert">
            <i class="bi bi-check-circle-fill me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show rounded-3 small py-2 px-3 mb-3" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-1"></i> {{ session('error') }}
            <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Today's Cashbook Register Card -->
    @if($currentCashBook)
        <div class="card border-0 shadow-sm rounded-3 mb-4 bg-white overflow-hidden border-start border-4 border-primary">
            <div class="card-body p-3">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill font-monospace">
                                TODAY'S REGISTER
                            </span>
                            <h5 class="fw-bold font-monospace mb-0 text-dark">
                                {{ $currentCashBook->date->format('l, d F Y') }}
                            </h5>
                            @if($currentCashBook->status === 'open')
                                <span class="badge bg-info-subtle text-info border border-info-subtle rounded-pill px-2.5">
                                    <i class="bi bi-unlock-fill me-1"></i> OPEN
                                </span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill px-2.5">
                                    <i class="bi bi-lock-fill me-1"></i> CLOSED
                                </span>
                            @endif
                        </div>
                        <p class="text-muted small mb-0 font-monospace">
                            Responsible: {{ $currentCashBook->responsibleStaff->name ?? 'System Staff' }}
                        </p>
                    </div>

                    <div class="d-flex flex-wrap align-items-center gap-3">
                        <div class="text-md-end">
                            <span class="text-muted small font-monospace d-block">Opening Balance</span>
                            <strong class="font-monospace fs-6">₹{{ number_format($currentCashBook->opening_balance, 2) }}</strong>
                        </div>
                        <div class="vr d-none d-md-block" style="height: 30px;"></div>
                        <div class="text-md-end">
                            <span class="text-muted small font-monospace d-block">Total Receipts</span>
                            <strong class="font-monospace fs-6 text-success">₹{{ number_format($currentCashBook->total_cash_received, 2) }}</strong>
                        </div>
                        <div class="vr d-none d-md-block" style="height: 30px;"></div>
                        <div class="text-md-end">
                            <span class="text-muted small font-monospace d-block">Total Payments</span>
                            <strong class="font-monospace fs-6 text-danger">₹{{ number_format($currentCashBook->total_cash_payment, 2) }}</strong>
                        </div>
                        <div class="vr d-none d-md-block" style="height: 30px;"></div>
                        <div class="text-md-end">
                            <span class="text-muted small font-monospace d-block">Closing Cash</span>
                            <strong class="font-monospace fs-6 text-primary">₹{{ number_format($currentCashBook->closing_cash, 2) }}</strong>
                        </div>
                        <div class="ms-md-2">
                            <a href="{{ route('admin.cash-book.show', $currentCashBook->id) }}" class="btn btn-outline-primary rounded-pill btn-sm px-3 fw-bold">
                                View Register <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Filters & Historical Registers Section -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white py-3 px-4 border-bottom d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <h5 class="fw-bold font-heading mb-0 text-dark">
                <i class="bi bi-journal-bookmark me-2 text-primary"></i>Historical Cashbook Registers
            </h5>

            <!-- Filter Controls -->
            <form action="{{ route('admin.cash-book.branch', $branch->id) }}" method="GET" class="d-flex flex-wrap align-items-center gap-2">
                <div class="input-group input-group-sm" style="width: 170px;">
                    <span class="input-group-text bg-light text-muted font-monospace"><i class="bi bi-calendar3"></i></span>
                    <input type="date" name="date" value="{{ request('date') }}" class="form-control font-monospace" placeholder="Filter Date">
                </div>

                <select name="status" class="form-select form-select-sm font-monospace" style="width: 140px;">
                    <option value="">All Statuses</option>
                    <option value="open" {{ request('status') === 'open' ? 'selected' : '' }}>Open Registers</option>
                    <option value="closed" {{ request('status') === 'closed' ? 'selected' : '' }}>Closed Registers</option>
                </select>

                <button type="submit" class="btn btn-sm btn-secondary rounded-pill px-3 font-monospace">
                    <i class="bi bi-filter me-1"></i> Filter
                </button>

                @if(request()->hasAny(['date', 'status']))
                    <a href="{{ route('admin.cash-book.branch', $branch->id) }}" class="btn btn-sm btn-outline-danger rounded-pill px-3 font-monospace">
                        <i class="bi bi-x-circle me-1"></i> Reset
                    </a>
                @endif
            </form>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 font-monospace" style="font-size: 0.85rem;">
                    <thead class="bg-light text-uppercase text-secondary">
                        <tr>
                            <th class="ps-4" style="width: 12%;">DATE</th>
                            <th style="width: 18%;">RESPONSIBLE STAFF</th>
                            <th class="text-end" style="width: 13%;">OPENING CASH</th>
                            <th class="text-end" style="width: 13%;">RECEIVED TOTAL</th>
                            <th class="text-end" style="width: 13%;">PAYMENT TOTAL</th>
                            <th class="text-end" style="width: 13%;">CLOSING CASH</th>
                            <th class="text-center" style="width: 10%;">STATUS</th>
                            <th class="text-end pe-4" style="width: 8%;">ACTION</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($cashBooks as $cb)
                            <tr class="{{ $cb->date->isToday() ? 'table-primary bg-opacity-10' : '' }}">
                                <td class="ps-4 fw-bold text-dark">
                                    {{ $cb->date->format('d/m/Y') }}
                                    @if($cb->date->isToday())
                                        <span class="badge bg-primary text-white ms-1" style="font-size: 0.65rem;">TODAY</span>
                                    @endif
                                </td>
                                <td class="text-dark">{{ $cb->responsibleStaff->name ?? 'System Staff' }}</td>
                                <td class="text-end fw-semibold">₹{{ number_format($cb->opening_balance, 2) }}</td>
                                <td class="text-end text-success fw-bold">₹{{ number_format($cb->total_cash_received, 2) }}</td>
                                <td class="text-end text-danger fw-bold">₹{{ number_format($cb->total_cash_payment, 2) }}</td>
                                <td class="text-end text-primary fw-bold">₹{{ number_format($cb->closing_cash, 2) }}</td>
                                <td class="text-center">
                                    @if($cb->status === 'open')
                                        <span class="badge bg-info-subtle text-info border border-info-subtle rounded-pill px-2">
                                            <i class="bi bi-unlock-fill me-1"></i> OPEN
                                        </span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill px-2">
                                            <i class="bi bi-lock-fill me-1"></i> CLOSED
                                        </span>
                                    @endif
                                </td>
                                <td class="text-end pe-4">
                                    <div class="d-flex justify-content-end align-items-center gap-1">
                                        <a href="{{ route('admin.cash-book.show', $cb->id) }}" class="btn btn-sm btn-outline-primary rounded-pill px-2.5 py-1" title="View Detailed Register">
                                            <i class="bi bi-eye-fill"></i> View Register
                                        </a>
                                        <a href="{{ route('admin.cash-book.print', $cb->id) }}" target="_blank" class="btn btn-sm btn-outline-secondary rounded-pill px-2 py-1" title="Print Register">
                                            <i class="bi bi-printer"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="bi bi-journal-x fs-2 d-block mb-2 text-secondary"></i>
                                    No Cashbook registers found for this branch matching your filter criteria.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($cashBooks->hasPages())
            <div class="card-footer bg-white py-3 px-4 border-top">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-muted small">
                        Showing {{ $cashBooks->firstItem() }} to {{ $cashBooks->lastItem() }} of {{ $cashBooks->total() }} records
                    </span>
                    <div>
                        {{ $cashBooks->withQueryString()->links() }}
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
