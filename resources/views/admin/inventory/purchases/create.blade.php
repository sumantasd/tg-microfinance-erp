@extends('layouts.admin')

@section('title', 'Create Product Purchase - Grihalaxmi Finance ERP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">
            <i class="bi bi-cart-plus text-primary me-2"></i>Create Product Procurement / Purchase
        </h4>
        <p class="text-muted small mb-0">Record supplier invoice details, line item costs, taxes, and payment amounts for branch inventory.</p>
    </div>
    <a href="{{ route('admin.product-purchase.index') }}" class="btn btn-outline-secondary rounded-pill px-3">
        <i class="bi bi-arrow-left me-1"></i> Back to Purchase History
    </a>
</div>

<x-ui.card class="shadow-sm border-0 p-4">
    <form action="{{ route('admin.product-purchase.store') }}" method="POST">
        @csrf
        <div class="row g-3">
            <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">1. Supplier & Purchase Header</h5>

            <div class="col-md-4">
                <label class="form-label fw-bold small">Receiving Branch <span class="text-danger">*</span></label>
                <select name="branch_id" class="form-select @error('branch_id') is-invalid @enderror" required>
                    <option value="">Select Branch</option>
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}" {{ old('branch_id', auth()->user()->branch_id) == $b->id ? 'selected' : '' }}>{{ $b->name }} ({{ $b->code }})</option>
                    @endforeach
                </select>
                @error('branch_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-4">
                <label class="form-label fw-bold small">Select Supplier / Vendor</label>
                <select name="supplier_id" id="supplier_select" class="form-select @error('supplier_id') is-invalid @enderror" onchange="if(this.value){ document.getElementById('supplier_name_input').value = this.options[this.selectedIndex].getAttribute('data-name'); }">
                    <option value="">-- Choose Registered Supplier --</option>
                    @foreach($suppliers as $sup)
                        <option value="{{ $sup->id }}" data-name="{{ $sup->supplier_name }}" {{ (old('supplier_id', request('supplier_id')) == $sup->id) ? 'selected' : '' }}>
                            {{ $sup->supplier_name }} ({{ $sup->supplier_code }})
                        </option>
                    @endforeach
                </select>
                @error('supplier_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-4">
                <label class="form-label fw-bold small">Supplier Name (Invoice/Display) <span class="text-danger">*</span></label>
                <input type="text" name="supplier_name" id="supplier_name_input" class="form-control @error('supplier_name') is-invalid @enderror" value="{{ old('supplier_name') }}" placeholder="e.g. Tata Solar Energy Pvt Ltd" required>
                @error('supplier_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-4">
                <label class="form-label fw-bold small">Supplier Invoice Number</label>
                <input type="text" name="supplier_invoice_number" class="form-control @error('supplier_invoice_number') is-invalid @enderror" value="{{ old('supplier_invoice_number') }}" placeholder="e.g. INV-2026-9812">
                @error('supplier_invoice_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-4">
                <label class="form-label fw-bold small">Purchase Date <span class="text-danger">*</span></label>
                <input type="date" name="purchase_date" class="form-control @error('purchase_date') is-invalid @enderror" value="{{ old('purchase_date', date('Y-m-d')) }}" required>
                @error('purchase_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-4">
                <label class="form-label fw-bold small">Supplier Reference / PO #</label>
                <input type="text" name="supplier_reference" class="form-control @error('supplier_reference') is-invalid @enderror" value="{{ old('supplier_reference') }}" placeholder="e.g. PO-PATNA-004">
                @error('supplier_reference') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-4">
                <label class="form-label fw-bold small">Payment Method</label>
                <select name="payment_method" class="form-select @error('payment_method') is-invalid @enderror">
                    <option value="bank_transfer" {{ old('payment_method') === 'bank_transfer' ? 'selected' : '' }}>Bank Transfer / NEFT</option>
                    <option value="cheque" {{ old('payment_method') === 'cheque' ? 'selected' : '' }}>Cheque</option>
                    <option value="cash" {{ old('payment_method') === 'cash' ? 'selected' : '' }}>Cash</option>
                    <option value="upi" {{ old('payment_method') === 'upi' ? 'selected' : '' }}>UPI / Digital</option>
                </select>
                @error('payment_method') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <h5 class="fw-bold text-dark border-bottom pb-2 mt-4 mb-3"><i class="bi bi-cart-plus text-primary me-2"></i>2. Purchase Line Items</h5>

            <div class="col-12" id="purchaseItemsContainer">
                <div class="row g-2 mb-3 purchase-item-row mobile-line-item-card border rounded p-2.5 bg-light-subtle align-items-center">
                    <div class="card-item-header d-md-none">
                        <span class="fw-bold text-primary font-monospace small"><i class="bi bi-box-seam me-1"></i>Purchase Item Line</span>
                    </div>
                    <div class="col-12 col-md-2 mb-2 mb-md-0">
                        <label class="form-label small fw-bold text-muted mb-1">Product Category <span class="text-danger">*</span></label>
                        <select name="items[0][category_id]" class="form-select form-select-sm category-select" required>
                            <option value="">Select Category</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-2 mb-2 mb-md-0">
                        <label class="form-label small fw-bold text-muted mb-1">Product Brand <span class="text-danger">*</span></label>
                        <select name="items[0][brand_id]" class="form-select form-select-sm brand-select" disabled required>
                            <option value="">Select category first</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-3 position-relative mb-2 mb-md-0">
                        <label class="form-label small fw-bold text-muted mb-1">Select Product <span class="text-danger">*</span></label>
                        <input type="hidden" name="items[0][product_id]" class="product-id-input" value="" required>
                        <div class="input-group input-group-sm">
                            <input type="text" class="form-control form-control-sm product-search-input" placeholder="Select category & brand first" disabled autocomplete="off">
                            <button type="button" class="btn btn-outline-secondary btn-clear-product d-none" title="Clear selection">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>
                        <div class="dropdown-menu w-100 shadow-lg p-0 product-search-results border mt-1" style="max-height: 250px; overflow-y: auto; z-index: 1050;">
                        </div>
                    </div>
                    <div class="col-6 col-md-1 mb-2 mb-md-0">
                        <label class="form-label small fw-bold text-muted mb-1">Quantity <span class="text-danger">*</span></label>
                        <input type="number" name="items[0][quantity]" class="form-control form-control-sm item-qty" placeholder="Qty" min="1" value="1" required>
                    </div>
                    <div class="col-6 col-md-2 mb-2 mb-md-0">
                        <label class="form-label small fw-bold text-muted mb-1">Unit Cost (₹)</label>
                        <input type="number" step="0.01" name="items[0][unit_purchase_cost]" class="form-control form-control-sm unit-cost-input" placeholder="Cost per unit">
                    </div>
                    <div class="col-6 col-md-1 mb-2 mb-md-0">
                        <label class="form-label small fw-bold text-muted mb-1">GST Tax %</label>
                        <input type="number" step="0.01" name="items[0][tax_rate]" class="form-control form-control-sm tax-rate-input" placeholder="18.00" value="18.00">
                    </div>
                    <div class="col-6 col-md-1 d-flex align-items-end justify-content-center">
                        <button type="button" class="btn btn-sm btn-outline-danger w-100 btn-remove-row remove-item-btn disabled" title="Remove line item">
                            <i class="bi bi-trash me-1"></i> Remove Line
                        </button>
                    </div>
                </div>
            </div>

            <div class="col-12 mt-2">
                <button type="button" class="btn btn-sm btn-outline-primary fw-bold rounded-pill px-3" id="btnAddRow">
                    <i class="bi bi-plus-lg me-1"></i> Add Another Product Line Item
                </button>
            </div>

            <h5 class="fw-bold text-dark border-bottom pb-2 mt-4 mb-3">3. Payments & Financial Adjustment</h5>

            <div class="col-md-4">
                <label class="form-label fw-bold small">Discount Amount (₹)</label>
                <input type="number" step="0.01" name="discount_amount" class="form-control @error('discount_amount') is-invalid @enderror" value="{{ old('discount_amount', '0.00') }}">
                @error('discount_amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-4">
                <label class="form-label fw-bold small">Other Charges / Freight (₹)</label>
                <input type="number" step="0.01" name="other_charges" class="form-control @error('other_charges') is-invalid @enderror" value="{{ old('other_charges', '0.00') }}">
                @error('other_charges') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-4">
                <label class="form-label fw-bold small">Initial Paid Amount (₹)</label>
                <input type="number" step="0.01" name="paid_amount" class="form-control @error('paid_amount') is-invalid @enderror" value="{{ old('paid_amount', '0.00') }}">
                @error('paid_amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-12 mt-3">
                <label class="form-label fw-bold small">Remarks & Purchase Notes</label>
                <textarea name="remarks" class="form-control @error('remarks') is-invalid @enderror" rows="2" placeholder="Internal notes or payment terms...">{{ old('remarks') }}</textarea>
                @error('remarks') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-12 d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                <a href="{{ route('admin.product-purchase.index') }}" class="btn btn-light border text-secondary fw-bold px-4">Cancel</a>
                <button type="submit" class="btn btn-primary fw-bold px-4"><i class="bi bi-check-circle me-1"></i> Save Draft Purchase</button>
            </div>
        </div>
    </form>
</x-ui.card>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const supSelect = document.getElementById('supplier_select');
        const supNameInput = document.getElementById('supplier_name_input');
        if (supSelect && supSelect.value && !supNameInput.value) {
            supNameInput.value = supSelect.options[supSelect.selectedIndex].getAttribute('data-name');
        }

        let rowIdx = 0;
        const container = document.getElementById('purchaseItemsContainer');
        const btnAdd = document.getElementById('btnAddRow');

        function setupPurchaseRow(row) {
            const catSelect = row.querySelector('.category-select');
            const brandSelect = row.querySelector('.brand-select');
            const prodIdInput = row.querySelector('.product-id-input');
            const searchInput = row.querySelector('.product-search-input');
            const clearBtn = row.querySelector('.btn-clear-product');
            const resultsContainer = row.querySelector('.product-search-results');
            const costInput = row.querySelector('.unit-cost-input');
            const taxInput = row.querySelector('.tax-rate-input');
            const removeBtn = row.querySelector('.btn-remove-row');

            let searchDebounce = null;

            function clearProductSelection() {
                if (prodIdInput) prodIdInput.value = '';
                if (searchInput) searchInput.value = '';
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
                                        <div class="small text-muted">Model: ${p.model_number} | Cost: ₹${parseFloat(p.cost_price).toFixed(2)} | GST: ${p.tax_percentage}%</div>
                                    `;
                                    item.addEventListener('click', function (e) {
                                        e.preventDefault();
                                        if (prodIdInput) prodIdInput.value = p.id;
                                        searchInput.value = `${p.name} (SKU: ${p.sku})`;
                                        if (clearBtn) clearBtn.classList.remove('d-none');
                                        resultsContainer.classList.remove('show');

                                        if (costInput) costInput.value = parseFloat(p.cost_price).toFixed(2);
                                        if (taxInput) taxInput.value = parseFloat(p.tax_percentage || 18).toFixed(2);
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

            if (removeBtn) {
                removeBtn.addEventListener('click', function () {
                    const allRows = container.querySelectorAll('.purchase-item-row');
                    if (allRows.length > 1) {
                        row.remove();
                        updateRemoveButtons();
                    }
                });
            }
        }

        function updateRemoveButtons() {
            const allRows = container.querySelectorAll('.purchase-item-row');
            allRows.forEach(r => {
                const btn = r.querySelector('.btn-remove-row');
                if (btn) {
                    if (allRows.length === 1) {
                        btn.classList.add('disabled');
                    } else {
                        btn.classList.remove('disabled');
                    }
                }
            });
        }

        const initialRows = container.querySelectorAll('.purchase-item-row');
        initialRows.forEach(row => setupPurchaseRow(row));

        if (btnAdd) {
            btnAdd.addEventListener('click', function () {
                rowIdx++;
                const firstRow = container.querySelector('.purchase-item-row');
                const newRow = firstRow.cloneNode(true);

                const catSelect = newRow.querySelector('.category-select');
                catSelect.name = `items[${rowIdx}][category_id]`;
                catSelect.selectedIndex = 0;

                const brandSelect = newRow.querySelector('.brand-select');
                brandSelect.name = `items[${rowIdx}][brand_id]`;
                brandSelect.innerHTML = '<option value="">Select category first</option>';
                brandSelect.disabled = true;

                const prodIdInput = newRow.querySelector('.product-id-input');
                prodIdInput.name = `items[${rowIdx}][product_id]`;
                prodIdInput.value = '';

                const searchInput = newRow.querySelector('.product-search-input');
                searchInput.value = '';
                searchInput.disabled = true;
                searchInput.placeholder = 'Select category and brand first';

                const clearBtn = newRow.querySelector('.btn-clear-product');
                clearBtn.classList.add('d-none');

                const resultsContainer = newRow.querySelector('.product-search-results');
                resultsContainer.innerHTML = '';
                resultsContainer.classList.remove('show');

                const qtyInput = newRow.querySelector('.item-qty');
                qtyInput.name = `items[${rowIdx}][quantity]`;
                qtyInput.value = 1;

                const costInput = newRow.querySelector('.unit-cost-input');
                costInput.name = `items[${rowIdx}][unit_purchase_cost]`;
                costInput.value = '';

                const taxInput = newRow.querySelector('.tax-rate-input');
                taxInput.name = `items[${rowIdx}][tax_rate]`;
                taxInput.value = '18.00';

                container.appendChild(newRow);
                setupPurchaseRow(newRow);
                updateRemoveButtons();
            });
        }
    });
</script>
@endpush
@endsection
