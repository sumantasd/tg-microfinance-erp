@extends('layouts.admin')

@section('title', 'Create Loan Application - Grihalaxmi Finance ERP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">
            <i class="bi bi-file-earmark-plus text-success me-2"></i>Create Loan Application
        </h4>
        <p class="text-muted small mb-0">Apply for individual/group cash or product loans under active finance schemes.</p>
    </div>
    <a href="{{ route('admin.loan-application.index') }}" class="btn btn-outline-secondary rounded-pill px-3">
        <i class="bi bi-arrow-left me-1"></i> Back to Directory
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
    <form action="{{ route('admin.loan-application.store') }}" method="POST" id="loanApplicationForm">
        @csrf
        <div class="row g-3">
            <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">1. Loan & Borrower Classification</h5>

            <div class="col-md-6">
                <label class="form-label fw-bold small">Branch Location <span class="text-danger">*</span></label>
                <select name="branch_id" class="form-select @error('branch_id') is-invalid @enderror" required>
                    <option value="">Select Branch</option>
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}" {{ old('branch_id', auth()->user()->branch_id) == $b->id ? 'selected' : '' }}>{{ $b->name }} ({{ $b->code }})</option>
                    @endforeach
                </select>
                @error('branch_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-3">
                <label class="form-label fw-bold small">Loan Type <span class="text-danger">*</span></label>
                <select name="loan_type" id="loanTypeSelect" class="form-select @error('loan_type') is-invalid @enderror" required>
                    <option value="cash" {{ old('loan_type', 'cash') === 'cash' ? 'selected' : '' }}>Cash Loan</option>
                    <option value="product" {{ old('loan_type') === 'product' ? 'selected' : '' }}>Product Loan</option>
                </select>
                @error('loan_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-3">
                <label class="form-label fw-bold small">Borrower Type <span class="text-danger">*</span></label>
                <select name="borrower_type" id="borrowerTypeSelect" class="form-select @error('borrower_type') is-invalid @enderror" required>
                    <option value="individual" {{ old('borrower_type', 'individual') === 'individual' ? 'selected' : '' }}>Individual Borrower</option>
                    <option value="group" {{ old('borrower_type') === 'group' ? 'selected' : '' }}>Group (JLG / SHG)</option>
                </select>
                @error('borrower_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <!-- Borrower Selector: Individual -->
            <div class="col-md-6" id="individualBorrowerContainer">
                <label class="form-label fw-bold small">Select Individual Customer <span class="text-danger">*</span></label>
                <select name="customer_id" id="individualCustomerSelect" class="form-select @error('customer_id') is-invalid @enderror">
                    <option value="">Choose Customer</option>
                    @foreach($customers as $c)
                        <option value="{{ $c->id }}" {{ old('customer_id') == $c->id ? 'selected' : '' }}>{{ $c->full_name }} ({{ $c->customer_code }}) - Phone: {{ $c->mobile_number }}</option>
                    @endforeach
                </select>
                @error('customer_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <!-- Borrower Selector: Group -->
            <div class="col-md-6 d-none" id="groupBorrowerContainer">
                <label class="form-label fw-bold small">Select Customer Group <span class="text-danger">*</span></label>
                <select name="customer_group_id" id="groupSelect" class="form-select @error('customer_group_id') is-invalid @enderror">
                    <option value="">Choose Customer Group (JLG/SHG)</option>
                    @foreach($groups as $g)
                        <option value="{{ $g->id }}" {{ old('customer_group_id') == $g->id ? 'selected' : '' }} data-members='@json($g->members)'>{{ $g->name }} ({{ $g->group_code }}) - {{ $g->members->count() }} Members</option>
                    @endforeach
                </select>
                @error('customer_group_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold small">Loan Scheme Master <span class="text-danger">*</span></label>
                <select name="loan_scheme_id" id="loanSchemeSelect" class="form-select @error('loan_scheme_id') is-invalid @enderror" required>
                    <option value="">Select Finance Loan Scheme</option>
                    @foreach($schemes as $s)
                        @php
                            $freqLabel = match($s->repayment_frequency) {
                                'bi_weekly' => '15 Days',
                                'weekly' => 'Weekly',
                                default => 'Monthly'
                            };
                        @endphp
                        <option value="{{ $s->id }}" 
                                {{ old('loan_scheme_id') == $s->id ? 'selected' : '' }}
                                data-tenure="{{ $s->min_tenure_months }}"
                                data-frequency="{{ $s->repayment_frequency }}"
                                data-frequency-label="{{ $freqLabel }}"
                                data-min-amount="{{ $s->min_amount }}"
                                data-max-amount="{{ $s->max_amount }}"
                                data-loan-type="{{ $s->loan_type }}">
                            {{ $s->name }} ({{ $freqLabel }} — {{ $s->min_tenure_months }} Months)
                        </option>
                    @endforeach
                </select>
                @error('loan_scheme_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <!-- Product Loan Items Section (Category -> Brand -> Search Product) -->
            <div class="col-12 mt-4 d-none" id="productItemsContainer">
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
                    <div class="row g-2 mb-3 product-item-row border rounded p-2.5 bg-light-subtle align-items-center">
                        <div class="col-md-3">
                            <label class="form-label small fw-bold text-muted mb-1">Product Category <span class="text-danger">*</span></label>
                            <select name="products[0][category_id]" class="form-select form-select-sm category-select" required>
                                <option value="">Select Category</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold text-muted mb-1">Product Brand <span class="text-danger">*</span></label>
                            <select name="products[0][brand_id]" class="form-select form-select-sm brand-select" disabled required>
                                <option value="">Select category first</option>
                            </select>
                        </div>
                        <div class="col-md-4 position-relative">
                            <label class="form-label small fw-bold text-muted mb-1">Select Product <span class="text-danger">*</span></label>
                            <input type="hidden" name="products[0][product_id]" class="product-id-input" value="" required>
                            <input type="hidden" class="product-unit-price-input" value="0">
                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control form-control-sm product-search-input" placeholder="Select category and brand first" disabled autocomplete="off">
                                <button type="button" class="btn btn-outline-secondary btn-clear-product d-none" title="Clear product selection">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                            <div class="dropdown-menu w-100 shadow-lg p-0 product-search-results border mt-1" style="max-height: 250px; overflow-y: auto; z-index: 1050;">
                            </div>
                        </div>
                        <div class="col-md-1">
                            <label class="form-label small fw-bold text-muted mb-1">Qty <span class="text-danger">*</span></label>
                            <input type="number" name="products[0][quantity]" class="form-control form-control-sm product-qty" placeholder="Qty" min="1" value="1" required>
                        </div>
                        <div class="col-md-1 d-flex align-items-end justify-content-center">
                            <button type="button" class="btn btn-sm btn-outline-danger btn-remove-product disabled" title="Remove row">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="alert alert-primary shadow-sm border mt-3 d-none" id="productFinancialSummaryBox">
                    <div class="row align-items-center text-center text-md-start">
                        <div class="col-md-4 mb-2 mb-md-0">
                            <span class="text-muted small d-block text-uppercase fw-bold">Gross Product Price</span>
                            <strong class="fs-5 font-monospace text-dark" id="summaryGrossPrice">₹0.00</strong>
                        </div>
                        <div class="col-md-4 mb-2 mb-md-0">
                            <span class="text-muted small d-block text-uppercase fw-bold">Requested Financed Amount</span>
                            <strong class="fs-5 font-monospace text-primary" id="summaryFinancedAmount">₹0.00</strong>
                        </div>
                        <div class="col-md-4">
                            <span class="text-muted small d-block text-uppercase fw-bold">Estimated Down Payment</span>
                            <strong class="fs-5 font-monospace text-success" id="summaryDownPayment">₹0.00</strong>
                        </div>
                    </div>
                    <div class="small text-muted mt-2 border-top pt-2">
                        <i class="bi bi-info-circle me-1"></i> <strong>Financial Rule:</strong> Financed Principal = Product Price - Down Payment. EMI and interest calculate strictly on the Financed Principal.
                    </div>
                </div>
            </div>

            <h5 class="fw-bold text-dark border-bottom pb-2 mt-4 mb-3">2. Requested Amount & Terms</h5>

            <div class="col-md-3">
                <label class="form-label fw-bold small">Application Date <span class="text-danger">*</span></label>
                <input type="date" name="application_date" class="form-control @error('application_date') is-invalid @enderror" value="{{ old('application_date', date('Y-m-d')) }}" required>
                @error('application_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-3">
                <label class="form-label fw-bold small">Requested Amount (₹) <span class="text-danger">*</span></label>
                <input type="number" step="0.01" name="requested_amount" id="totalRequestedAmount" class="form-control @error('requested_amount') is-invalid @enderror" value="{{ old('requested_amount') }}" placeholder="e.g. 50000.00" required>
                @error('requested_amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-3">
                <label class="form-label fw-bold small">Payment Frequency <span class="text-danger">*</span></label>
                <select name="repayment_frequency" id="repaymentFrequencySelect" class="form-select @error('repayment_frequency') is-invalid @enderror">
                    <option value="weekly" {{ old('repayment_frequency') === 'weekly' ? 'selected' : '' }}>Weekly</option>
                    <option value="bi_weekly" {{ old('repayment_frequency', 'bi_weekly') === 'bi_weekly' ? 'selected' : '' }}>15 Days</option>
                    <option value="monthly" {{ old('repayment_frequency') === 'monthly' ? 'selected' : '' }}>Monthly</option>
                </select>
                @error('repayment_frequency') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-3">
                <label class="form-label fw-bold small">Tenure <span class="text-muted small">(from Scheme)</span></label>
                <div class="input-group">
                    <input type="text" id="tenureMonthsDisplay" class="form-control bg-light font-monospace fw-bold text-dark" readonly value="{{ old('tenure_months', 12) }} Months">
                    <span class="input-group-text bg-light"><i class="bi bi-lock-fill text-muted"></i></span>
                </div>
                <input type="hidden" name="tenure_months" id="tenureMonthsInput" value="{{ old('tenure_months', 12) }}">
                @error('tenure_months') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
            </div>

            <div class="col-12 mt-3">
                <label class="form-label fw-bold small">Loan Purpose</label>
                <input type="text" name="purpose" class="form-control @error('purpose') is-invalid @enderror" value="{{ old('purpose') }}" placeholder="e.g. Purchase of sewing machinery / Business expansion">
                @error('purpose') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <!-- Group Member Allocation Section (Conditional) -->
            <div class="col-12 mt-4 d-none" id="groupMemberAllocationContainer">
                <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">Group Member Allocation breakdown</h5>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm align-middle">
                        <thead class="bg-light">
                            <tr>
                                <th>Member Name & Code</th>
                                <th style="width: 250px;">Allocated Amount (₹)</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody id="groupMemberRows">
                            <!-- Populated dynamically via JS when group is selected -->
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="col-12 d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                <a href="{{ route('admin.loan-application.index') }}" class="btn btn-light border text-secondary fw-bold px-4">Cancel</a>
                <button type="submit" class="btn btn-success fw-bold px-4"><i class="bi bi-check-circle me-1"></i> Save Draft Application</button>
            </div>
        </div>
    </form>
</x-ui.card>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const loanTypeSelect = document.getElementById('loanTypeSelect');
        const borrowerTypeSelect = document.getElementById('borrowerTypeSelect');
        const loanSchemeSelect = document.getElementById('loanSchemeSelect');
        const repaymentFrequencySelect = document.getElementById('repaymentFrequencySelect');
        const tenureMonthsDisplay = document.getElementById('tenureMonthsDisplay');
        const tenureMonthsInput = document.getElementById('tenureMonthsInput');
        const indContainer = document.getElementById('individualBorrowerContainer');
        const grpContainer = document.getElementById('groupBorrowerContainer');
        const grpMemberContainer = document.getElementById('groupMemberAllocationContainer');
        const productContainer = document.getElementById('productItemsContainer');
        const groupSelect = document.getElementById('groupSelect');
        const groupMemberRows = document.getElementById('groupMemberRows');
        const indSelect = document.getElementById('individualCustomerSelect');
        const productRowsContainer = document.getElementById('productRowsContainer');
        const btnAddProductRow = document.getElementById('btnAddProductRow');
        const totalRequestedAmount = document.getElementById('totalRequestedAmount');

        // Full Product Catalog JSON for category-first cascading dropdowns
        const productsCatalog = @json($products);

        let productRowIndex = 0;

        // Auto-populate Tenure and Repayment Frequency from Scheme
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
        if (loanSchemeSelect.selectedIndex > 0) {
            handleSchemeChange();
        }

        function toggleVisibility() {
            const borrowerType = borrowerTypeSelect.value;
            const loanType = loanTypeSelect.value;

            if (borrowerType === 'individual') {
                indContainer.classList.remove('d-none');
                indSelect.disabled = false;

                grpContainer.classList.add('d-none');
                groupSelect.disabled = true;
                grpMemberContainer.classList.add('d-none');
                toggleInputsInside(grpMemberContainer, true);
            } else {
                indContainer.classList.add('d-none');
                indSelect.disabled = true;

                grpContainer.classList.remove('d-none');
                groupSelect.disabled = false;
                grpMemberContainer.classList.remove('d-none');
                toggleInputsInside(grpMemberContainer, false);
                renderGroupMembers();
            }

            if (loanType === 'product') {
                productContainer.classList.remove('d-none');
                toggleInputsInside(productContainer, false);
                calculateProductSummary();
            } else {
                productContainer.classList.add('d-none');
                toggleInputsInside(productContainer, true);
                document.getElementById('productFinancialSummaryBox').classList.add('d-none');
            }
        }

        // Setup Category -> Brand -> Search Product Selector on a given row
        function setupProductRow(row) {
            const catSelect = row.querySelector('.category-select');
            const brandSelect = row.querySelector('.brand-select');
            const prodIdInput = row.querySelector('.product-id-input');
            const prodPriceInput = row.querySelector('.product-unit-price-input');
            const searchInput = row.querySelector('.product-search-input');
            const clearBtn = row.querySelector('.btn-clear-product');
            const resultsContainer = row.querySelector('.product-search-results');
            const removeBtn = row.querySelector('.btn-remove-product');
            const qtyInput = row.querySelector('.product-qty');

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
                calculateProductSummary();
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
                                        calculateProductSummary();
                                    });
                                    resultsContainer.appendChild(item);
                                });
                            }
                            resultsContainer.classList.add('show');
                        })
                        .catch(err => {
                            console.error('Search error:', err);
                            resultsContainer.innerHTML = '<div class="p-3 text-danger small text-center">Error fetching products</div>';
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

            if (qtyInput) {
                qtyInput.addEventListener('input', calculateProductSummary);
            }

            if (removeBtn) {
                removeBtn.addEventListener('click', function () {
                    const allRows = productRowsContainer.querySelectorAll('.product-item-row');
                    if (allRows.length > 1) {
                        row.remove();
                        updateRemoveButtons();
                        calculateProductSummary();
                    }
                });
            }
        }

        function updateRemoveButtons() {
            const allRows = productRowsContainer.querySelectorAll('.product-item-row');
            allRows.forEach((r, idx) => {
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

        // Initialize existing product rows
        const initialRows = productRowsContainer.querySelectorAll('.product-item-row');
        initialRows.forEach(row => setupProductRow(row));

        // Add new product item row
        btnAddProductRow.addEventListener('click', function () {
            productRowIndex++;
            const firstRow = productRowsContainer.querySelector('.product-item-row');
            const newRow = firstRow.cloneNode(true);

            // Reset inputs in new row
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
            calculateProductSummary();
        });

        function calculateProductSummary() {
            if (loanTypeSelect.value !== 'product') {
                document.getElementById('productFinancialSummaryBox').classList.add('d-none');
                return;
            }

            document.getElementById('productFinancialSummaryBox').classList.remove('d-none');

            let grossPrice = 0.00;
            const rows = productRowsContainer.querySelectorAll('.product-item-row');
            rows.forEach(row => {
                const prodIdInput = row.querySelector('.product-id-input');
                const priceInput = row.querySelector('.product-unit-price-input');
                const qtyInput = row.querySelector('.product-qty');
                if (prodIdInput && prodIdInput.value) {
                    const price = parseFloat(priceInput ? priceInput.value || 0 : 0);
                    const qty = parseFloat(qtyInput ? qtyInput.value || 0 : 0);
                    grossPrice += (price * qty);
                }
            });

            let financed = parseFloat(totalRequestedAmount ? totalRequestedAmount.value || 0 : 0);
            if (financed <= 0 || financed > grossPrice) {
                financed = grossPrice;
                if (totalRequestedAmount && grossPrice > 0) {
                    totalRequestedAmount.value = grossPrice.toFixed(2);
                }
            }
            const downPayment = Math.max(0, grossPrice - financed);

            document.getElementById('summaryGrossPrice').textContent = '₹' + grossPrice.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            document.getElementById('summaryFinancedAmount').textContent = '₹' + financed.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            document.getElementById('summaryDownPayment').textContent = '₹' + downPayment.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        }

        function toggleInputsInside(container, disable) {
            const inputs = container.querySelectorAll('input, select, textarea, button');
            inputs.forEach(el => {
                if (el.id !== 'btnAddProductRow') {
                    el.disabled = disable;
                }
            });
        }

        function renderGroupMembers() {
            const selectedOpt = groupSelect.options[groupSelect.selectedIndex];
            groupMemberRows.innerHTML = '';
            if (!selectedOpt || !selectedOpt.dataset.members) return;

            const members = JSON.parse(selectedOpt.dataset.members);
            members.forEach((m, idx) => {
                const customer = m.customer || {};
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>
                        <input type="hidden" name="members[${idx}][customer_id]" value="${customer.id}">
                        <strong>${customer.first_name || ''} ${customer.last_name || ''}</strong> (${customer.customer_code || ''})
                    </td>
                    <td>
                        <input type="number" step="0.01" name="members[${idx}][requested_amount]" class="form-control form-control-sm member-amount-input" placeholder="Amount" required>
                    </td>
                    <td>
                        <input type="text" name="members[${idx}][remarks]" class="form-control form-control-sm" placeholder="Member purpose">
                    </td>
                `;
                groupMemberRows.appendChild(tr);
            });
        }

        borrowerTypeSelect.addEventListener('change', toggleVisibility);
        loanTypeSelect.addEventListener('change', toggleVisibility);
        groupSelect.addEventListener('change', renderGroupMembers);
        totalRequestedAmount.addEventListener('input', calculateProductSummary);

        toggleVisibility();
    });
</script>
@endpush
@endsection
