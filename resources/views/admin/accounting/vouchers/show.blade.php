@extends('layouts.admin')

@section('title', 'Voucher: ' . $voucher->voucher_number . ' - Grihalaxmi Finance ERP')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
    <div>
        <div class="d-flex align-items-center gap-2">
            <h4 class="fw-bold text-dark mb-0 font-monospace">
                <i class="bi bi-file-earmark-ruled text-primary me-2"></i>{{ $voucher->voucher_number }}
            </h4>
            @if($voucher->is_reversal)
                <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Reversal Voucher</span>
            @endif
            <span class="badge bg-success-subtle text-success border border-success-subtle text-uppercase">{{ $voucher->status }}</span>
        </div>
        <p class="text-muted small mb-0 mt-1">General Ledger Double-Entry Posting Sheet</p>
    </div>
    <div class="mt-3 mt-md-0 d-flex gap-2 flex-wrap">
        <a href="{{ route('admin.accounting.vouchers.index') }}" class="btn btn-outline-secondary rounded-pill px-3">
            <i class="bi bi-arrow-left me-1"></i> Back to Register
        </a>
        <button type="button" onclick="window.print();" class="btn btn-outline-primary rounded-pill px-3">
            <i class="bi bi-printer me-1"></i> Print Voucher
        </button>
        @if($voucher->status === 'posted' && !$voucher->is_reversal && $voucher->reversalVouchers->isEmpty())
            <button type="button" class="btn btn-outline-danger rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#reversalModal">
                <i class="bi bi-arrow-counterclockwise me-1"></i> Reverse Voucher
            </button>
        @endif
        <a href="{{ route('admin.accounting.vouchers.create') }}" class="btn btn-primary text-white fw-bold rounded-pill px-4 shadow-sm">
            <i class="bi bi-plus-circle me-1"></i> New Voucher
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<!-- Reversal Linkage Banners -->
@if($voucher->is_reversal && $voucher->reversedVoucher)
    <div class="alert alert-warning border shadow-sm d-flex align-items-center mb-4 py-2.5 px-3">
        <i class="bi bi-info-circle-fill fs-5 text-warning me-3"></i>
        <div>
            <strong>Notice:</strong> This is a reversal voucher neutralizing
            <a href="{{ route('admin.accounting.vouchers.show', $voucher->reversedVoucher->id) }}" class="fw-bold font-monospace text-dark text-decoration-underline">
                {{ $voucher->reversedVoucher->voucher_number }}
            </a>.
            <div class="small text-muted">Reason: {{ $voucher->reversal_reason ?: 'Correction of transaction' }}</div>
        </div>
    </div>
@endif

@if($voucher->reversalVouchers->isNotEmpty())
    <div class="alert alert-danger border shadow-sm d-flex align-items-center mb-4 py-2.5 px-3">
        <i class="bi bi-exclamation-triangle-fill fs-5 text-danger me-3"></i>
        <div>
            <strong>Reversed:</strong> This voucher has been neutralized by reversal voucher
            <a href="{{ route('admin.accounting.vouchers.show', $voucher->reversalVouchers->first()->id) }}" class="fw-bold font-monospace text-dark text-decoration-underline">
                {{ $voucher->reversalVouchers->first()->voucher_number }}
            </a>.
            <div class="small text-muted">Reason: {{ $voucher->reversalVouchers->first()->reversal_reason }}</div>
        </div>
    </div>
@endif

<!-- Voucher Metadata Sheet -->
<x-ui.card class="shadow-sm border-0 p-4 mb-4">
    <div class="row g-3">
        <div class="col-md-3">
            <div class="small text-muted fw-bold text-uppercase">Voucher Type</div>
            <div class="fs-6 fw-bold text-dark text-uppercase mt-0.5">{{ $voucher->voucher_type }}</div>
        </div>

        <div class="col-md-3">
            <div class="small text-muted fw-bold text-uppercase">Posting Date</div>
            <div class="fs-6 fw-bold text-dark mt-0.5">{{ $voucher->voucher_date->format('d F, Y') }}</div>
        </div>

        <div class="col-md-3">
            <div class="small text-muted fw-bold text-uppercase">Branch Office</div>
            <div class="fs-6 fw-bold text-dark mt-0.5">{{ $voucher->branch->name ?? 'Head Office' }} ({{ $voucher->branch->code ?? 'HO' }})</div>
        </div>

        <div class="col-md-3">
            <div class="small text-muted fw-bold text-uppercase">Financial Year</div>
            <div class="fs-6 fw-bold text-dark mt-0.5">{{ $voucher->financialYear->title ?? 'N/A' }}</div>
        </div>

        <div class="col-12 border-top pt-3">
            <div class="small text-muted fw-bold text-uppercase">Narration / Memo</div>
            <div class="text-dark fs-6 mt-1">{{ $voucher->narration ?: 'No narration provided.' }}</div>
        </div>

        @if($voucher->reference_type)
            <div class="col-12 pt-1">
                <div class="small text-muted">
                    <i class="bi bi-link-45deg me-1"></i>Source Module Reference: <span class="badge bg-light text-dark border">{{ $voucher->reference_type }} #{{ $voucher->reference_id }}</span>
                </div>
            </div>
        @endif
    </div>
</x-ui.card>

<!-- Ledger Entries Table -->
<x-ui.card class="shadow-sm border-0 p-0 mb-4">
    <div class="p-3 border-bottom bg-light">
        <h6 class="fw-bold text-dark mb-0"><i class="bi bi-table text-primary me-2"></i>Ledger Distribution Lines</h6>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered align-middle mb-0">
            <thead class="table-light small text-uppercase">
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 35%;">Ledger Account</th>
                    <th style="width: 15%;">Classification</th>
                    <th style="width: 25%;">Line Description</th>
                    <th style="width: 10%;" class="text-end">Debit (₹)</th>
                    <th style="width: 10%;" class="text-end">Credit (₹)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($voucher->entries as $idx => $line)
                    <tr>
                        <td class="text-muted small">{{ $idx + 1 }}</td>
                        <td>
                            <span class="font-monospace fw-bold text-dark">{{ $line->account->account_code }}</span> — 
                            <span class="fw-semibold text-dark">{{ $line->account->account_name }}</span>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border px-2 py-0.5 text-uppercase" style="font-size: 0.75rem;">
                                {{ $line->account->account_type }}
                            </span>
                        </td>
                        <td class="small text-muted">
                            {{ $line->description ?: '—' }}
                        </td>
                        <td class="font-monospace fw-bold text-dark text-end">
                            {{ $line->debit > 0 ? '₹' . number_format($line->debit, 2) : '—' }}
                        </td>
                        <td class="font-monospace fw-bold text-dark text-end">
                            {{ $line->credit > 0 ? '₹' . number_format($line->credit, 2) : '—' }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot class="table-light font-monospace fw-bold fs-6">
                <tr>
                    <td colspan="4" class="text-end text-dark uppercase">Total Distribution:</td>
                    <td class="text-end text-primary">₹{{ number_format($voucher->total_debit, 2) }}</td>
                    <td class="text-end text-primary">₹{{ number_format($voucher->total_credit, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="p-3 bg-light-subtle border-top d-flex justify-content-between align-items-center text-muted small">
        <div>
            <i class="bi bi-shield-check text-success me-1"></i> Double-entry mathematical equality strictly verified ($\sum Dr = \sum Cr$).
        </div>
        <div>
            Created by <strong>{{ $voucher->creator->name ?? 'System' }}</strong> on {{ $voucher->created_at->format('d M Y, h:i A') }}
        </div>
    </div>
</x-ui.card>

<!-- Reversal Modal -->
@if($voucher->status === 'posted' && !$voucher->is_reversal && $voucher->reversalVouchers->isEmpty())
    <div class="modal fade" id="reversalModal" tabindex="-1" aria-labelledby="reversalModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form action="{{ route('admin.accounting.vouchers.reverse', $voucher->id) }}" method="POST" class="modal-content">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-danger" id="reversalModalLabel">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Reverse Voucher: {{ $voucher->voucher_number }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted mb-3">
                        Posted financial vouchers cannot be altered or deleted. A reversal voucher will be generated that automatically swaps all Debits and Credits to neutralize this entry in the General Ledger.
                    </p>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Reversal Date <span class="text-danger">*</span></label>
                        <input type="date" name="reversal_date" class="form-control" value="{{ now()->toDateString() }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Reason for Reversal <span class="text-danger">*</span></label>
                        <textarea name="reversal_reason" class="form-control" rows="3" placeholder="State error or justification for reversing this voucher..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger fw-bold px-3">
                        <i class="bi bi-check-circle me-1"></i> Confirm & Post Reversal
                    </button>
                </div>
            </form>
        </div>
    </div>
@endif
@endsection
