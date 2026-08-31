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

            <h5 class="fw-bold text-dark border-bottom pb-2 mt-4 mb-3"><i class="bi bi-box-seam text-warning me-2"></i>2. Product Line Items</h5>

            <div class="col-12" id="transferItemsContainer">
                <div class="row g-2 mb-3 transfer-item-row border rounded p-3 bg-light-subtle align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-muted mb-1">Product Category <span class="text-danger">*</span></label>
                        <select name="items[0][category_id]" class="form-select form-select-sm category-select" required>
                            <option value="">Select Category</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-muted mb-1">Product Brand <span class="text-danger">*</span></label>
                        <select name="items[0][brand_id]" class="form-select form-select-sm brand-select" disabled required>
                            <option value="">Select category first</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-muted mb-1">Product <span class="text-danger">*</span></label>
                        <select name="items[0][product_id]" class="form-select form-select-sm product-select" disabled required>
                            <option value="">Select brand first</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold text-muted mb-1">Quantity <span class="text-danger">*</span></label>
                        <div class="input-group input-group-sm">
                            <input type="number" name="items[0][quantity]" class="form-control form-control-sm qty-input" placeholder="Qty" min="1" value="1" required>
                            <button type="button" class="btn btn-outline-danger btn-remove-row disabled" title="Remove row">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 mt-2">
                <button type="button" class="btn btn-sm btn-outline-primary fw-bold rounded-pill px-3" id="btnAddRow">
                    <i class="bi bi-plus-circle me-1"></i> Add Another Product Line Item
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
        let rowIdx = 0;
        const container = document.getElementById('transferItemsContainer');
        const btnAdd = document.getElementById('btnAddRow');

        function setupTransferRow(row) {
            const catSelect = row.querySelector('.category-select');
            const brandSelect = row.querySelector('.brand-select');
            const productSelect = row.querySelector('.product-select');
            const removeBtn = row.querySelector('.btn-remove-row');

            if (catSelect) {
                catSelect.addEventListener('change', function () {
                    const catId = this.value;

                    brandSelect.innerHTML = '<option value="">Select Brand</option>';
                    brandSelect.disabled = true;

                    productSelect.innerHTML = '<option value="">Select brand first</option>';
                    productSelect.disabled = true;

                    if (!catId) return;

                    brandSelect.innerHTML = '<option value="">Loading brands...</option>';

                    fetch(`{{ route('admin.inventory.ajax.brands-by-category') }}?category_id=${catId}`)
                        .then(res => res.json())
                        .then(brands => {
                            brandSelect.innerHTML = '<option value="">Select Brand</option>';
                            if (brands.length === 0) {
                                brandSelect.innerHTML = '<option value="">No brands available</option>';
                            } else {
                                brands.forEach(b => {
                                    const opt = document.createElement('option');
                                    opt.value = b.id;
                                    opt.textContent = b.name;
                                    brandSelect.appendChild(opt);
                                });
                                brandSelect.disabled = false;
                            }
                        })
                        .catch(err => {
                            console.error('Error fetching brands:', err);
                            brandSelect.innerHTML = '<option value="">Error loading brands</option>';
                        });
                });
            }

            if (brandSelect) {
                brandSelect.addEventListener('change', function () {
                    const catId = catSelect ? catSelect.value : '';
                    const brandId = this.value;

                    productSelect.innerHTML = '<option value="">Select Product</option>';
                    productSelect.disabled = true;

                    if (!catId || !brandId) return;

                    productSelect.innerHTML = '<option value="">Loading products...</option>';

                    fetch(`{{ route('admin.inventory.ajax.products-by-brand') }}?category_id=${catId}&brand_id=${brandId}`)
                        .then(res => res.json())
                        .then(products => {
                            productSelect.innerHTML = '<option value="">Select Product</option>';
                            if (products.length === 0) {
                                productSelect.innerHTML = '<option value="">No products available</option>';
                            } else {
                                products.forEach(p => {
                                    const opt = document.createElement('option');
                                    opt.value = p.id;
                                    opt.textContent = `${p.name} (SKU: ${p.sku}) - ₹${parseFloat(p.unit_price).toFixed(2)}`;
                                    productSelect.appendChild(opt);
                                });
                                productSelect.disabled = false;
                            }
                        })
                        .catch(err => {
                            console.error('Error fetching products:', err);
                            productSelect.innerHTML = '<option value="">Error loading products</option>';
                        });
                });
            }

            if (removeBtn) {
                removeBtn.addEventListener('click', function () {
                    const allRows = container.querySelectorAll('.transfer-item-row');
                    if (allRows.length > 1) {
                        row.remove();
                        updateRemoveButtons();
                    }
                });
            }
        }

        function updateRemoveButtons() {
            const allRows = container.querySelectorAll('.transfer-item-row');
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

        const initialRows = container.querySelectorAll('.transfer-item-row');
        initialRows.forEach(row => setupTransferRow(row));

        if (btnAdd) {
            btnAdd.addEventListener('click', function () {
                rowIdx++;
                const firstRow = container.querySelector('.transfer-item-row');
                const newRow = firstRow.cloneNode(true);

                const catSelect = newRow.querySelector('.category-select');
                catSelect.name = `items[${rowIdx}][category_id]`;
                catSelect.selectedIndex = 0;

                const brandSelect = newRow.querySelector('.brand-select');
                brandSelect.name = `items[${rowIdx}][brand_id]`;
                brandSelect.innerHTML = '<option value="">Select category first</option>';
                brandSelect.disabled = true;

                const productSelect = newRow.querySelector('.product-select');
                productSelect.name = `items[${rowIdx}][product_id]`;
                productSelect.innerHTML = '<option value="">Select brand first</option>';
                productSelect.disabled = true;

                const qtyInput = newRow.querySelector('.qty-input');
                qtyInput.name = `items[${rowIdx}][quantity]`;
                qtyInput.value = 1;

                container.appendChild(newRow);
                setupTransferRow(newRow);
                updateRemoveButtons();
            });
        }
    });
</script>
@endpush
@endsection
