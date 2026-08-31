@extends('layouts.admin')

@section('title', 'Daily Cash Book Registers')

@section('content')
<div class="container-fluid px-4 py-3">
    <!-- Header Title & Filter Actions -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1 font-heading text-dark">
                <i class="bi bi-journal-check text-success me-2"></i>Daily Cash Book Registers
            </h4>
            <p class="text-muted small mb-0">Manage daily branch physical & digital cash book reconciliation</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            @if($currentCashBook)
                <a href="{{ route('admin.cash-book.show', $currentCashBook->id) }}" class="btn btn-primary rounded-pill px-3 shadow-sm font-monospace fw-bold">
                    <i class="bi bi-book-half me-1"></i> Open Register ({{ Carbon\Carbon::parse($selectedDate)->format('d M Y') }})
                </a>
            @endif
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body p-3">
            <form action="{{ route('admin.cash-book.index') }}" method="GET" class="row g-3 align-items-end">
                <div class="col-md-4 col-lg-3">
                    <label class="form-label small fw-bold text-secondary">Branch</label>
                    <select name="branch_id" class="form-select bg-light border-0">
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}" {{ $selectedBranchId == $b->id ? 'selected' : '' }}>{{ $b->name }} ({{ $b->code }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 col-lg-3">
                    <label class="form-label small fw-bold text-secondary">Register Date</label>
                    <input type="date" name="date" value="{{ $selectedDate }}" class="form-control bg-light border-0">
                </div>
                <div class="col-md-3 col-lg-2">
                    <label class="form-label small fw-bold text-secondary">Status</label>
                    <select name="status" class="form-select bg-light border-0">
                        <option value="">All Statuses</option>
                        <option value="open" {{ request('status') == 'open' ? 'selected' : '' }}>Open</option>
                        <option value="closed" {{ request('status') == 'closed' ? 'selected' : '' }}>Closed</option>
                    </select>
                </div>
                <div class="col-md-2 col-lg-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary rounded-3 w-100 fw-bold">
                        <i class="bi bi-filter me-1"></i> Filter
                    </button>
                    <a href="{{ route('admin.cash-book.index') }}" class="btn btn-light rounded-3 px-3">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Cash Book Listing Table -->
    <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-uppercase small text-muted font-monospace">
                    <tr>
                        <th class="ps-4">Date</th>
                        <th>Branch</th>
                        <th>Opening Cash</th>
                        <th>Received Total</th>
                        <th>Payment Total</th>
                        <th>Closing Cash</th>
                        <th>Physical Total</th>
                        <th>Difference</th>
                        <th>Reconciliation</th>
                        <th>Status</th>
                        <th class="pe-4 text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($cashBooks as $cb)
                        <tr>
                            <td class="ps-4 fw-bold font-monospace">
                                <a href="{{ route('admin.cash-book.show', $cb->id) }}" class="text-primary text-decoration-none">
                                    {{ $cb->date->format('d M Y') }}
                                </a>
                            </td>
                            <td>
                                <span class="fw-semibold text-dark">{{ $cb->branch->name }}</span>
                            </td>
                            <td class="font-monospace">₹{{ number_format($cb->opening_balance, 2) }}</td>
                            <td class="font-monospace text-success fw-semibold">₹{{ number_format($cb->total_cash_received, 2) }}</td>
                            <td class="font-monospace text-danger fw-semibold">₹{{ number_format($cb->total_cash_payment, 2) }}</td>
                            <td class="font-monospace fw-bold text-dark">₹{{ number_format($cb->closing_cash, 2) }}</td>
                            <td class="font-monospace">₹{{ number_format($cb->physical_cash, 2) }}</td>
                            <td class="font-monospace {{ $cb->cash_difference < 0 ? 'text-danger fw-bold' : ($cb->cash_difference > 0 ? 'text-warning fw-bold' : 'text-muted') }}">
                                ₹{{ number_format($cb->cash_difference, 2) }}
                            </td>
                            <td>
                                @if($cb->reconciled_status === 'balanced')
                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5">
                                        <i class="bi bi-check-circle-fill me-1"></i> Balanced
                                    </span>
                                @elseif($cb->reconciled_status === 'cash_short')
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2.5">
                                        <i class="bi bi-exclamation-triangle-fill me-1"></i> Shortage
                                    </span>
                                @else
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill px-2.5">
                                        <i class="bi bi-plus-circle-fill me-1"></i> Excess
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if($cb->status === 'open')
                                    <span class="badge bg-info-subtle text-info border border-info-subtle rounded-pill px-2.5">
                                        <i class="bi bi-unlock-fill me-1"></i> OPEN
                                    </span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill px-2.5">
                                        <i class="bi bi-lock-fill me-1"></i> CLOSED
                                    </span>
                                @endif
                            </td>
                            <td class="pe-4 text-end">
                                <a href="{{ route('admin.cash-book.show', $cb->id) }}" class="btn btn-sm btn-outline-primary rounded-pill px-3 me-1">
                                    <i class="bi bi-eye-fill me-1"></i> View Register
                                </a>
                                <a href="{{ route('admin.cash-book.print', $cb->id) }}" target="_blank" class="btn btn-sm btn-light rounded-circle text-muted" title="Print Register">
                                    <i class="bi bi-printer"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="text-center py-5 text-muted">
                                <i class="bi bi-journal-x fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                No cash book register entries found for selected criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($cashBooks->hasPages())
            <div class="card-footer bg-light border-0 py-3">
                {{ $cashBooks->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
