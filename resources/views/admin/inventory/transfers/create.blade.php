@extends('layouts.admin')

@section('title', 'Create Branch Transfer - Grihalaxmi Finance ERP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">
            <i class="bi bi-arrow-left-right text-warning me-2"></i>Create Branch-to-Branch Stock Transfer
        </h4>
        <p class="text-muted small mb-0">Select source branch, destination branch, products, and quantities to initiate stock movement.</p>
    </div>
    <a href="{{ route('admin.inventory-transfer.index') }}" class="btn btn-outline-secondary rounded-pill px-3">
        <i class="bi bi-arrow-left me-1"></i> Back to Transfers
    </a>
</div>

<x-ui.card class="shadow-sm border-0 p-4">
    <form action="{{ route('admin.inventory-transfer.store') }}" method="POST">
        @csrf
        <div class="row g-3">
            <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">1. Branch Locations</h5>

            <div class="col-md-6">
                <label class="form-label fw-bold small">Source Branch (Sending) <span class="text-danger">*</span></label>
                <select name="source_branch_id" class="form-select @error('source_branch_id') is-invalid @enderror" required>
                    <option value="">Select Source Branch</option>
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}" {{ old('source_branch_id', auth()->user()->branch_id) == $b->id ? 'selected' : '' }}>{{ $b->name }} ({{ $b->code }})</option>
                    @endforeach
                </select>
                @error('source_branch_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold small">Destination Branch (Receiving) <span class="text-danger">*</span></label>
                <select name="destination_branch_id" class="form-select @error('destination_branch_id') is-invalid @enderror" required>
                    <option value="">Select Destination Branch</option>
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}" {{ old('destination_branch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }} ({{ $b->code }})</option>
                    @endforeach
                </select>
                @error('destination_branch_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <h5 class="fw-bold text-dark border-bottom pb-2 mt-4 mb-3">2. Product Line Items</h5>

            <div class="col-12" id="transferItemsContainer">
                <div class="row g-2 mb-2 transfer-item-row">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Select Product Catalog Item <span class="text-danger">*</span></label>
                        <select name="items[0][product_id]" class="form-select" required>
                            <option value="">Choose Product</option>
                            @foreach($products as $p)
                                <option value="{{ $p->id }}">{{ $p->name }} (SKU: {{ $p->sku }}) - ₹{{ number_format($p->unit_price, 2) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Quantity <span class="text-danger">*</span></label>
                        <input type="number" name="items[0][quantity]" class="form-control" placeholder="e.g. 10" min="1" required>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="button" class="btn btn-outline-danger w-100 disabled"><i class="bi bi-trash"></i></button>
                    </div>
                </div>
            </div>

            <div class="col-12 mt-2">
                <button type="button" class="btn btn-sm btn-outline-primary fw-bold" id="btnAddRow">
                    <i class="bi bi-plus-circle me-1"></i> Add Another Product Item
                </button>
            </div>

            <div class="col-12 mt-4">
                <label class="form-label fw-bold small">Remarks / Purpose</label>
                <textarea name="remarks" class="form-control @error('remarks') is-invalid @enderror" rows="2" placeholder="e.g. Stock balancing request for upcoming Product Loan distribution">{{ old('remarks') }}</textarea>
                @error('remarks') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-12 d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                <a href="{{ route('admin.inventory-transfer.index') }}" class="btn btn-light border text-secondary fw-bold px-4">Cancel</a>
                <button type="submit" class="btn btn-warning text-dark fw-bold px-4"><i class="bi bi-check-circle me-1"></i> Save & Initiate Transfer</button>
            </div>
        </div>
    </form>
</x-ui.card>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        let rowIdx = 1;
        const container = document.getElementById('transferItemsContainer');
        const btnAdd = document.getElementById('btnAddRow');

        const productOptions = `@foreach($products as $p)<option value="{{ $p->id }}">{{ addslashes($p->name) }} (SKU: {{ $p->sku }}) - ₹{{ number_format($p->unit_price, 2) }}</option>@endforeach`;

        btnAdd.addEventListener('click', function () {
            const rowDiv = document.createElement('div');
            rowDiv.className = 'row g-2 mb-2 transfer-item-row';
            rowDiv.innerHTML = `
                <div class="col-md-6">
                    <select name="items[\${rowIdx}][product_id]" class="form-select" required>
                        <option value="">Choose Product</option>
                        \${productOptions}
                    </select>
                </div>
                <div class="col-md-4">
                    <input type="number" name="items[\${rowIdx}][quantity]" class="form-control" placeholder="e.g. 10" min="1" required>
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-outline-danger w-100 btn-remove-row"><i class="bi bi-trash"></i></button>
                </div>
            `;
            container.appendChild(rowDiv);
            rowIdx++;

            rowDiv.querySelector('.btn-remove-row').addEventListener('click', function () {
                rowDiv.remove();
            });
        });
    });
</script>
@endpush
@endsection
