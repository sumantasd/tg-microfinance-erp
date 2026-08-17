@extends('layouts.admin')

@section('title', 'Edit Loan Application - Grihalaxmi Finance ERP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">
            <i class="bi bi-pencil-square text-primary me-2"></i>Edit Draft Application - {{ $loanApplication->application_number }}
        </h4>
        <p class="text-muted small mb-0">Status: <span class="badge bg-secondary text-white">{{ $loanApplication->status }}</span></p>
    </div>
    <a href="{{ route('admin.loan-application.show', $loanApplication->id) }}" class="btn btn-outline-secondary rounded-pill px-3">
        <i class="bi bi-arrow-left me-1"></i> Back to Details
    </a>
</div>

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
        <h6 class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-2"></i> Please correct the following errors:</h6>
        <ul class="mb-0 small ps-3">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<x-ui.card class="shadow-sm border-0 p-4">
    <form action="{{ route('admin.loan-application.update', $loanApplication->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="row g-3">
            <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">1. Loan Scheme & Classification</h5>

            <div class="col-md-6">
                <label class="form-label fw-bold small">Branch Location</label>
                <input type="text" class="form-control bg-light" value="{{ $loanApplication->branch->name }} ({{ $loanApplication->branch->code }})" disabled>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold small">Loan Scheme Master <span class="text-danger">*</span></label>
                <select name="loan_scheme_id" id="loanSchemeSelect" class="form-select @error('loan_scheme_id') is-invalid @enderror" required>
                    @foreach($schemes as $s)
                        @php
                            $freqLabel = match($s->repayment_frequency) {
                                'bi_weekly' => '15 Days',
                                'weekly' => 'Weekly',
                                default => 'Monthly'
                            };
                        @endphp
                        <option value="{{ $s->id }}" 
                                {{ old('loan_scheme_id', $loanApplication->loan_scheme_id) == $s->id ? 'selected' : '' }}
                                data-tenure="{{ $s->min_tenure_months }}"
                                data-frequency="{{ $s->repayment_frequency }}"
                                data-frequency-label="{{ $freqLabel }}">
                            {{ $s->name }} ({{ $freqLabel }} — {{ $s->min_tenure_months }} Months)
                        </option>
                    @endforeach
                </select>
                @error('loan_scheme_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <!-- Product Items Section if product loan -->
            @if($loanApplication->loan_type === 'product')
                <div class="col-12 mt-4" id="productItemsContainer">
                    <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                        <div>
                            <h5 class="fw-bold text-dark mb-0"><i class="bi bi-cart-plus text-primary me-2"></i>Product Loan Line Items</h5>
                            <small class="text-muted">Select Product Category first, then choose product items from that category.</small>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary fw-bold rounded-pill px-3" id="btnAddProductRow">
                            <i class="bi bi-plus-lg me-1"></i> Add Product Item
                        </button>
                    </div>

                    <div id="productRowsContainer">
                        @foreach($loanApplication->products as $idx => $item)
                            <div class="row g-2 mb-3 product-item-row border rounded p-2.5 bg-light-subtle align-items-center">
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-muted mb-1">Step 1: Category</label>
                                    <select class="form-select form-select-sm category-select">
                                        <option value="">Select Category</option>
                                        @foreach($categories as $cat)
                                            <option value="{{ $cat->id }}" data-name="{{ $cat->name }}" {{ ($item->product->category_id == $cat->id || $item->product->category == $cat->name) ? 'selected' : '' }}>{{ $cat->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label small fw-bold text-muted mb-1">Step 2: Product <span class="text-danger">*</span></label>
                                    <select name="products[{{ $idx }}][product_id]" class="form-select form-select-sm product-select" required>
                                        <option value="{{ $item->product_id }}" data-price="{{ $item->unit_price_snapshot }}" selected>
                                            {{ $item->product_name_snapshot }} (SKU: {{ $item->product_sku_snapshot }}) - ₹{{ number_format($item->unit_price_snapshot, 2) }}
                                        </option>
                                    </select>
                                    <input type="hidden" name="products[{{ $idx }}][category_id]" class="row-category-id" value="{{ $item->product->category_id ?? '' }}">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small fw-bold text-muted mb-1">Quantity <span class="text-danger">*</span></label>
                                    <input type="number" name="products[{{ $idx }}][quantity]" class="form-control form-control-sm product-qty" placeholder="Qty" min="1" value="{{ $item->quantity }}" required>
                                </div>
                                <div class="col-md-1 d-flex align-items-end justify-content-center">
                                    <button type="button" class="btn btn-sm btn-outline-danger btn-remove-product {{ count($loanApplication->products) <= 1 ? 'disabled' : '' }}" title="Remove row">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <h5 class="fw-bold text-dark border-bottom pb-2 mt-4 mb-3">2. Requested Amount & Terms</h5>

            <div class="col-md-4">
                <label class="form-label fw-bold small">Total Requested Amount (₹) <span class="text-danger">*</span></label>
                <input type="number" step="0.01" name="requested_amount" id="totalRequestedAmount" class="form-control @error('requested_amount') is-invalid @enderror" value="{{ old('requested_amount', $loanApplication->requested_amount) }}" required>
                @error('requested_amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-4">
                <label class="form-label fw-bold small">Payment Frequency <span class="text-danger">*</span></label>
                <select name="repayment_frequency" id="repaymentFrequencySelect" class="form-select @error('repayment_frequency') is-invalid @enderror">
                    <option value="weekly" {{ old('repayment_frequency', $loanApplication->repayment_frequency) === 'weekly' ? 'selected' : '' }}>Weekly</option>
                    <option value="bi_weekly" {{ old('repayment_frequency', $loanApplication->repayment_frequency) === 'bi_weekly' ? 'selected' : '' }}>15 Days</option>
                    <option value="monthly" {{ old('repayment_frequency', $loanApplication->repayment_frequency) === 'monthly' ? 'selected' : '' }}>Monthly</option>
                </select>
                @error('repayment_frequency') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-4">
                <label class="form-label fw-bold small">Tenure <span class="text-muted small">(from Scheme)</span></label>
                <div class="input-group">
                    <input type="text" id="tenureMonthsDisplay" class="form-control bg-light font-monospace fw-bold text-dark" readonly value="{{ old('tenure_months', $loanApplication->tenure_months) }} Months">
                    <span class="input-group-text bg-light"><i class="bi bi-lock-fill text-muted"></i></span>
                </div>
                <input type="hidden" name="tenure_months" id="tenureMonthsInput" value="{{ old('tenure_months', $loanApplication->tenure_months) }}">
                @error('tenure_months') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
            </div>

            <div class="col-12 mt-3">
                <label class="form-label fw-bold small">Loan Purpose</label>
                <input type="text" name="purpose" class="form-control @error('purpose') is-invalid @enderror" value="{{ old('purpose', $loanApplication->purpose) }}">
                @error('purpose') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-12 mt-3">
                <label class="form-label fw-bold small">Remarks</label>
                <textarea name="remarks" class="form-control @error('remarks') is-invalid @enderror" rows="2">{{ old('remarks', $loanApplication->remarks) }}</textarea>
                @error('remarks') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-12 d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                <a href="{{ route('admin.loan-application.show', $loanApplication->id) }}" class="btn btn-light border text-secondary fw-bold px-4">Cancel</a>
                <button type="submit" class="btn btn-primary fw-bold px-4"><i class="bi bi-save me-1"></i> Update Draft Application</button>
            </div>
        </div>
    </form>
</x-ui.card>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const loanSchemeSelect = document.getElementById('loanSchemeSelect');
        const repaymentFrequencySelect = document.getElementById('repaymentFrequencySelect');
        const tenureMonthsDisplay = document.getElementById('tenureMonthsDisplay');
        const tenureMonthsInput = document.getElementById('tenureMonthsInput');
        const productRowsContainer = document.getElementById('productRowsContainer');
        const btnAddProductRow = document.getElementById('btnAddProductRow');

        const productsCatalog = @json($products);
        let productRowIndex = {{ $loanApplication->products ? count($loanApplication->products) : 0 }};

        function handleSchemeChange() {
            const selectedOpt = loanSchemeSelect.options[loanSchemeSelect.selectedIndex];
            if (selectedOpt && selectedOpt.value) {
                const tenure = selectedOpt.dataset.tenure || '12';
                const freq = selectedOpt.dataset.frequency || 'monthly';
                
                tenureMonthsDisplay.value = tenure + ' Months';
                tenureMonthsInput.value = tenure;
                
                if (freq) {
                    repaymentFrequencySelect.value = freq;
                }
            }
        }

        loanSchemeSelect.addEventListener('change', handleSchemeChange);

        if (productRowsContainer) {
            function setupProductRow(row) {
                const catSelect = row.querySelector('.category-select');
                const prodSelect = row.querySelector('.product-select');
                const catIdHidden = row.querySelector('.row-category-id');
                const removeBtn = row.querySelector('.btn-remove-product');

                if (!catSelect || !prodSelect) return;

                catSelect.addEventListener('change', function () {
                    const selectedCatId = this.value;
                    const selectedCatName = this.options[this.selectedIndex] ? this.options[this.selectedIndex].dataset.name : '';

                    if (catIdHidden) {
                        catIdHidden.value = selectedCatId;
                    }

                    prodSelect.innerHTML = '<option value="">Select Product</option>';

                    if (!selectedCatId) {
                        prodSelect.disabled = true;
                        prodSelect.innerHTML = '<option value="">First select category</option>';
                        return;
                    }

                    const filteredProducts = productsCatalog.filter(p => {
                        return (p.category_id && p.category_id == selectedCatId) || (p.category && p.category === selectedCatName);
                    });

                    if (filteredProducts.length === 0) {
                        prodSelect.disabled = true;
                        prodSelect.innerHTML = '<option value="">No products found in this category</option>';
                    } else {
                        prodSelect.disabled = false;
                        filteredProducts.forEach(p => {
                            const opt = document.createElement('option');
                            opt.value = p.id;
                            opt.dataset.price = p.unit_price;
                            opt.textContent = `${p.name} (SKU: ${p.sku}) - ₹${parseFloat(p.unit_price).toFixed(2)}`;
                            prodSelect.appendChild(opt);
                        });
                    }
                });

                if (removeBtn) {
                    removeBtn.addEventListener('click', function () {
                        const allRows = productRowsContainer.querySelectorAll('.product-item-row');
                        if (allRows.length > 1) {
                            row.remove();
                        }
                    });
                }
            }

            const initialRows = productRowsContainer.querySelectorAll('.product-item-row');
            initialRows.forEach(row => setupProductRow(row));

            if (btnAddProductRow) {
                btnAddProductRow.addEventListener('click', function () {
                    productRowIndex++;
                    const firstRow = productRowsContainer.querySelector('.product-item-row');
                    const newRow = firstRow.cloneNode(true);

                    const catSelect = newRow.querySelector('.category-select');
                    catSelect.selectedIndex = 0;

                    const prodSelect = newRow.querySelector('.product-select');
                    prodSelect.name = `products[${productRowIndex}][product_id]`;
                    prodSelect.innerHTML = '<option value="">First select category</option>';
                    prodSelect.disabled = true;

                    const catIdHidden = newRow.querySelector('.row-category-id');
                    if (catIdHidden) {
                        catIdHidden.name = `products[${productRowIndex}][category_id]`;
                        catIdHidden.value = '';
                    }

                    const qtyInput = newRow.querySelector('.product-qty');
                    qtyInput.name = `products[${productRowIndex}][quantity]`;
                    qtyInput.value = 1;

                    const removeBtn = newRow.querySelector('.btn-remove-product');
                    removeBtn.classList.remove('disabled');

                    productRowsContainer.appendChild(newRow);
                    setupProductRow(newRow);
                });
            }
        }
    });
</script>
@endpush
@endsection
