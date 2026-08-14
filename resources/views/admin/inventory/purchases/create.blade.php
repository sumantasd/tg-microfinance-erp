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
                <label class="form-label fw-bold small">Supplier / Vendor Name <span class="text-danger">*</span></label>
                <input type="text" name="supplier_name" class="form-control @error('supplier_name') is-invalid @enderror" value="{{ old('supplier_name') }}" placeholder="e.g. Tata Solar Energy Pvt Ltd" required>
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

            <h5 class="fw-bold text-dark border-bottom pb-2 mt-4 mb-3">2. Purchase Line Items</h5>

            <div class="col-12" id="purchaseItemsContainer">
                <div class="row g-2 mb-2 purchase-item-row border-bottom pb-2">
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Select Product <span class="text-danger">*</span></label>
                        <select name="items[0][product_id]" class="form-select" required>
                            <option value="">Choose Product Catalog Item</option>
                            @foreach($products as $p)
                                <option value="{{ $p->id }}">{{ $p->name }} (SKU: {{ $p->sku }}) - Standard Cost: ₹{{ number_format($p->cost_price ?? 0, 2) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold">Quantity <span class="text-danger">*</span></label>
                        <input type="number" name="items[0][quantity]" class="form-control" placeholder="Qty" min="1" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Unit Cost Price (₹)</label>
                        <input type="number" step="0.01" name="items[0][unit_purchase_cost]" class="form-control" placeholder="Cost per unit">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold">GST Tax %</label>
                        <input type="number" step="0.01" name="items[0][tax_rate]" class="form-control" placeholder="18.00" value="18.00">
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="button" class="btn btn-outline-danger w-100 disabled"><i class="bi bi-trash"></i></button>
                    </div>
                </div>
            </div>

            <div class="col-12 mt-2">
                <button type="button" class="btn btn-sm btn-outline-primary fw-bold" id="btnAddRow">
                    <i class="bi bi-plus-circle me-1"></i> Add Another Product Line Item
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
        let rowIdx = 1;
        const container = document.getElementById('purchaseItemsContainer');
        const btnAdd = document.getElementById('btnAddRow');

        const productOptions = `@foreach($products as $p)<option value="{{ $p->id }}">{{ addslashes($p->name) }} (SKU: {{ $p->sku }}) - Standard Cost: ₹{{ number_format($p->cost_price ?? 0, 2) }}</option>@endforeach`;

        btnAdd.addEventListener('click', function () {
            const rowDiv = document.createElement('div');
            rowDiv.className = 'row g-2 mb-2 purchase-item-row border-bottom pb-2';
            rowDiv.innerHTML = `
                <div class="col-md-4">
                    <select name="items[\${rowIdx}][product_id]" class="form-select" required>
                        <option value="">Choose Product Catalog Item</option>
                        \${productOptions}
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="number" name="items[\${rowIdx}][quantity]" class="form-control" placeholder="Qty" min="1" required>
                </div>
                <div class="col-md-3">
                    <input type="number" step="0.01" name="items[\${rowIdx}][unit_purchase_cost]" class="form-control" placeholder="Cost per unit">
                </div>
                <div class="col-md-2">
                    <input type="number" step="0.01" name="items[\${rowIdx}][tax_rate]" class="form-control" placeholder="18.00" value="18.00">
                </div>
                <div class="col-md-1">
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
