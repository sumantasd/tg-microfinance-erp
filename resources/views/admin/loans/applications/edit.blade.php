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
            </div>            <!-- Product Items Section if product loan -->
            @if($loanApplication->loan_type === 'product')
                <div class="col-12 mt-4" id="productItemsContainer">
                    <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                        <div>
                            <h5 class="fw-bold text-dark mb-0"><i class="bi bi-cart-plus text-primary me-2"></i>Product Loan Line Items</h5>
                            <small class="text-muted">Select Product Category, Product Brand, and Search Product sequentially for each line item.</small>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary fw-bold rounded-pill px-3" id="btnAddProductRow">
                            <i class="bi bi-plus-lg me-1"></i> Add Product Item
                        </button>
                    </div>

                    <div id="productRowsContainer">
                        @foreach($loanApplication->products as $idx => $item)
                            @php
                                $catId = $item->product->category_id ?? '';
                                $brandId = $item->product->brand_id ?? '';
                            @endphp
                            <div class="row g-2 mb-3 product-item-row border rounded p-2.5 bg-light-subtle align-items-center">
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold text-muted mb-1">Product Category <span class="text-danger">*</span></label>
                                    <select name="products[{{ $idx }}][category_id]" class="form-select form-select-sm category-select" required>
                                        <option value="">Select Category</option>
                                        @foreach($categories as $cat)
                                            <option value="{{ $cat->id }}" {{ $catId == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold text-muted mb-1">Product Brand <span class="text-danger">*</span></label>
                                    <select name="products[{{ $idx }}][brand_id]" class="form-select form-select-sm brand-select" required>
                                        <option value="">Select Brand</option>
                                        @foreach($brands as $b)
                                            <option value="{{ $b->id }}" {{ $brandId == $b->id ? 'selected' : '' }}>{{ $b->name }} ({{ $b->code }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4 position-relative">
                                    <label class="form-label small fw-bold text-muted mb-1">Select Product <span class="text-danger">*</span></label>
                                    <input type="hidden" name="products[{{ $idx }}][product_id]" class="product-id-input" value="{{ $item->product_id }}" required>
                                    <input type="hidden" class="product-unit-price-input" value="{{ $item->unit_price_snapshot }}">
                                    <div class="input-group input-group-sm">
                                        <input type="text" class="form-control form-control-sm product-search-input" value="{{ $item->product_name_snapshot }} (SKU: {{ $item->product_sku_snapshot }}) - ₹{{ number_format($item->unit_price_snapshot, 2) }}" placeholder="Type to search product..." autocomplete="off">
                                        <button type="button" class="btn btn-outline-secondary btn-clear-product" title="Clear product selection">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    </div>
                                    <div class="dropdown-menu w-100 shadow-lg p-0 product-search-results border mt-1" style="max-height: 250px; overflow-y: auto; z-index: 1050;">
                                    </div>
                                </div>
                                <div class="col-md-1">
                                    <label class="form-label small fw-bold text-muted mb-1">Qty <span class="text-danger">*</span></label>
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
                const brandSelect = row.querySelector('.brand-select');
                const prodIdInput = row.querySelector('.product-id-input');
                const prodPriceInput = row.querySelector('.product-unit-price-input');
                const searchInput = row.querySelector('.product-search-input');
                const clearBtn = row.querySelector('.btn-clear-product');
                const resultsContainer = row.querySelector('.product-search-results');
                const removeBtn = row.querySelector('.btn-remove-product');

                let searchDebounce = null;

                function clearProductSelection() {
                    if (prodIdInput) prodIdInput.value = '';
                    if (prodPriceInput) prodPriceInput.value = '0';
                    if (searchInput) {
                        searchInput.value = '';
                    }
                    if (clearBtn) clearBtn.classList.add('d-none');
                    if (resultsContainer) {
                        resultsContainer.innerHTML = '';
                        resultsContainer.classList.remove('show');
                    }
                }

                if (catSelect) {
                    catSelect.addEventListener('change', function () {
                        const catId = this.value;
                        clearProductSelection();

                        if (!catId) {
                            brandSelect.innerHTML = '<option value="">Select category first</option>';
                            brandSelect.disabled = true;
                            searchInput.disabled = true;
                            searchInput.placeholder = 'Select category and brand first';
                            return;
                        }

                        brandSelect.disabled = true;
                        brandSelect.innerHTML = '<option value="">Loading brands...</option>';

                        fetch(`{{ route('admin.loan-application.ajax.brands-by-category') }}?category_id=${catId}`)
                            .then(res => res.json())
                            .then(brands => {
                                brandSelect.innerHTML = '<option value="">Select Product Brand</option>';
                                brands.forEach(b => {
                                    const opt = document.createElement('option');
                                    opt.value = b.id;
                                    opt.textContent = `${b.name} (${b.code})`;
                                    brandSelect.appendChild(opt);
                                });
                                brandSelect.disabled = false;
                            })
                            .catch(err => {
                                console.error('Error fetching brands:', err);
                                brandSelect.innerHTML = '<option value="">Error loading brands</option>';
                            });
                    });
                }

                if (brandSelect) {
                    brandSelect.addEventListener('change', function () {
                        const brandId = this.value;
                        clearProductSelection();

                        if (!brandId) {
                            searchInput.disabled = true;
                            searchInput.placeholder = 'Select category and brand first';
                            return;
                        }

                        searchInput.disabled = false;
                        searchInput.placeholder = 'Type to search product (Name, SKU, Model)...';
                        searchInput.focus();
                    });
                }

                if (searchInput) {
                    function performSearch() {
                        const catId = catSelect ? catSelect.value : '';
                        const brandId = brandSelect ? brandSelect.value : '';
                        const query = searchInput.value.trim();

                        if (!catId || !brandId) return;

                        fetch(`{{ route('admin.loan-application.ajax.search-products') }}?category_id=${catId}&brand_id=${brandId}&q=${encodeURIComponent(query)}`)
                            .then(res => res.json())
                            .then(products => {
                                resultsContainer.innerHTML = '';
                                if (products.length === 0) {
                                    resultsContainer.innerHTML = '<div class="p-3 text-muted small text-center"><i class="bi bi-info-circle me-1"></i>No products found matching criteria.</div>';
                                } else {
                                    products.forEach(p => {
                                        const item = document.createElement('a');
                                        item.href = '#';
                                        item.className = 'dropdown-item py-2 px-3 border-bottom text-wrap';
                                        item.innerHTML = `
                                            <div class="fw-bold text-dark mb-0">${p.name} <span class="badge bg-light text-dark font-monospace">${p.sku}</span></div>
                                            <div class="small text-muted">Model: ${p.model_number} | Price: ₹${parseFloat(p.unit_price).toFixed(2)}</div>
                                        `;
                                        item.addEventListener('click', function (e) {
                                            e.preventDefault();
                                            if (prodIdInput) prodIdInput.value = p.id;
                                            if (prodPriceInput) prodPriceInput.value = p.unit_price;
                                            searchInput.value = `${p.name} (SKU: ${p.sku}) - ₹${parseFloat(p.unit_price).toFixed(2)}`;
                                            if (clearBtn) clearBtn.classList.remove('d-none');
                                            resultsContainer.classList.remove('show');
                                        });
                                        resultsContainer.appendChild(item);
                                    });
                                }
                                resultsContainer.classList.add('show');
                            });
                    }

                    searchInput.addEventListener('input', function () {
                        clearTimeout(searchDebounce);
                        searchDebounce = setTimeout(performSearch, 250);
                    });

                    searchInput.addEventListener('focus', function () {
                        if (!searchInput.disabled && catSelect.value && brandSelect.value) {
                            performSearch();
                        }
                    });
                }

                if (clearBtn) {
                    clearBtn.addEventListener('click', function (e) {
                        e.preventDefault();
                        clearProductSelection();
                        if (!brandSelect.disabled && brandSelect.value) {
                            searchInput.focus();
                        }
                    });
                }

                document.addEventListener('click', function (e) {
                    if (resultsContainer && !row.contains(e.target)) {
                        resultsContainer.classList.remove('show');
                    }
                });

                if (removeBtn) {
                    removeBtn.addEventListener('click', function () {
                        const allRows = productRowsContainer.querySelectorAll('.product-item-row');
                        if (allRows.length > 1) {
                            row.remove();
                            updateRemoveButtons();
                        }
                    });
                }
            }

            function updateRemoveButtons() {
                const allRows = productRowsContainer.querySelectorAll('.product-item-row');
                allRows.forEach((r) => {
                    const btn = r.querySelector('.btn-remove-product');
                    if (btn) {
                        if (allRows.length === 1) {
                            btn.classList.add('disabled');
                        } else {
                            btn.classList.remove('disabled');
                        }
                    }
                });
            }

            const initialRows = productRowsContainer.querySelectorAll('.product-item-row');
            initialRows.forEach(row => setupProductRow(row));

            if (btnAddProductRow) {
                btnAddProductRow.addEventListener('click', function () {
                    productRowIndex++;
                    const firstRow = productRowsContainer.querySelector('.product-item-row');
                    const newRow = firstRow.cloneNode(true);

                    const catSelect = newRow.querySelector('.category-select');
                    catSelect.name = `products[${productRowIndex}][category_id]`;
                    catSelect.selectedIndex = 0;

                    const brandSelect = newRow.querySelector('.brand-select');
                    brandSelect.name = `products[${productRowIndex}][brand_id]`;
                    brandSelect.innerHTML = '<option value="">Select category first</option>';
                    brandSelect.disabled = true;

                    const prodIdInput = newRow.querySelector('.product-id-input');
                    prodIdInput.name = `products[${productRowIndex}][product_id]`;
                    prodIdInput.value = '';

                    const prodPriceInput = newRow.querySelector('.product-unit-price-input');
                    prodPriceInput.value = '0';

                    const searchInput = newRow.querySelector('.product-search-input');
                    searchInput.value = '';
                    searchInput.disabled = true;
                    searchInput.placeholder = 'Select category and brand first';

                    const clearBtn = newRow.querySelector('.btn-clear-product');
                    clearBtn.classList.add('d-none');

                    const resultsContainer = newRow.querySelector('.product-search-results');
                    resultsContainer.innerHTML = '';
                    resultsContainer.classList.remove('show');

                    const qtyInput = newRow.querySelector('.product-qty');
                    qtyInput.name = `products[${productRowIndex}][quantity]`;
                    qtyInput.value = 1;

                    productRowsContainer.appendChild(newRow);
                    setupProductRow(newRow);
                    updateRemoveButtons();
                });
            }
        }
    });
</script>
@endpush
@endsection
