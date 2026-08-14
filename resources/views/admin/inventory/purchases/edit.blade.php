@extends('layouts.admin')

@section('title', 'Edit Purchase - Grihalaxmi Finance ERP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">
            <i class="bi bi-pencil-square text-primary me-2"></i>Edit Purchase - {{ $productPurchase->purchase_number }}
        </h4>
        <p class="text-muted small mb-0">Supplier: {{ $productPurchase->supplier_name }} | Status: <span class="badge bg-secondary text-white">{{ $productPurchase->purchase_status }}</span></p>
    </div>
    <a href="{{ route('admin.product-purchase.show', $productPurchase->id) }}" class="btn btn-outline-secondary rounded-pill px-3">
        <i class="bi bi-arrow-left me-1"></i> Back to Details
    </a>
</div>

<x-ui.card class="shadow-sm border-0 p-4">
    <form action="{{ route('admin.product-purchase.update', $productPurchase->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="row g-3">
            <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">1. Supplier & Purchase Header</h5>

            <div class="col-md-4">
                <label class="form-label fw-bold small">Branch</label>
                <input type="text" class="form-control" value="{{ $productPurchase->branch->name }} ({{ $productPurchase->branch->code }})" disabled>
            </div>

            <div class="col-md-4">
                <label class="form-label fw-bold small">Supplier / Vendor Name <span class="text-danger">*</span></label>
                <input type="text" name="supplier_name" class="form-control @error('supplier_name') is-invalid @enderror" value="{{ old('supplier_name', $productPurchase->supplier_name) }}" required>
                @error('supplier_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-4">
                <label class="form-label fw-bold small">Supplier Invoice Number</label>
                <input type="text" name="supplier_invoice_number" class="form-control @error('supplier_invoice_number') is-invalid @enderror" value="{{ old('supplier_invoice_number', $productPurchase->supplier_invoice_number) }}">
                @error('supplier_invoice_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-4">
                <label class="form-label fw-bold small">Purchase Date <span class="text-danger">*</span></label>
                <input type="date" name="purchase_date" class="form-control @error('purchase_date') is-invalid @enderror" value="{{ old('purchase_date', $productPurchase->purchase_date ? $productPurchase->purchase_date->format('Y-m-d') : date('Y-m-d')) }}" required>
                @error('purchase_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-4">
                <label class="form-label fw-bold small">Supplier Reference / PO #</label>
                <input type="text" name="supplier_reference" class="form-control @error('supplier_reference') is-invalid @enderror" value="{{ old('supplier_reference', $productPurchase->supplier_reference) }}">
                @error('supplier_reference') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-4">
                <label class="form-label fw-bold small">Payment Method</label>
                <select name="payment_method" class="form-select @error('payment_method') is-invalid @enderror">
                    <option value="bank_transfer" {{ old('payment_method', $productPurchase->payment_method) === 'bank_transfer' ? 'selected' : '' }}>Bank Transfer / NEFT</option>
                    <option value="cheque" {{ old('payment_method', $productPurchase->payment_method) === 'cheque' ? 'selected' : '' }}>Cheque</option>
                    <option value="cash" {{ old('payment_method', $productPurchase->payment_method) === 'cash' ? 'selected' : '' }}>Cash</option>
                    <option value="upi" {{ old('payment_method', $productPurchase->payment_method) === 'upi' ? 'selected' : '' }}>UPI / Digital</option>
                </select>
                @error('payment_method') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <h5 class="fw-bold text-dark border-bottom pb-2 mt-4 mb-3">2. Purchase Line Items</h5>

            <div class="col-12" id="purchaseItemsContainer">
                @foreach($productPurchase->items as $idx => $item)
                    <div class="row g-2 mb-2 purchase-item-row border-bottom pb-2">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Select Product <span class="text-danger">*</span></label>
                            <select name="items[{{ $idx }}][product_id]" class="form-select" required>
                                @foreach($products as $p)
                                    <option value="{{ $p->id }}" {{ $item->product_id == $p->id ? 'selected' : '' }}>{{ $p->name }} (SKU: {{ $p->sku }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-bold">Quantity <span class="text-danger">*</span></label>
                            <input type="number" name="items[{{ $idx }}][quantity]" class="form-control" value="{{ $item->quantity }}" min="1" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Unit Cost Price (₹)</label>
                            <input type="number" step="0.01" name="items[{{ $idx }}][unit_purchase_cost]" class="form-control" value="{{ $item->unit_purchase_cost }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-bold">GST Tax %</label>
                            <input type="number" step="0.01" name="items[{{ $idx }}][tax_rate]" class="form-control" value="{{ $item->tax_rate }}">
                        </div>
                        <div class="col-md-1 d-flex align-items-end">
                            <button type="button" class="btn btn-outline-danger w-100 btn-remove-row"><i class="bi bi-trash"></i></button>
                        </div>
                    </div>
                @endforeach
            </div>

            <h5 class="fw-bold text-dark border-bottom pb-2 mt-4 mb-3">3. Payments & Financial Adjustment</h5>

            <div class="col-md-4">
                <label class="form-label fw-bold small">Discount Amount (₹)</label>
                <input type="number" step="0.01" name="discount_amount" class="form-control @error('discount_amount') is-invalid @enderror" value="{{ old('discount_amount', $productPurchase->discount_amount) }}">
                @error('discount_amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-4">
                <label class="form-label fw-bold small">Other Charges / Freight (₹)</label>
                <input type="number" step="0.01" name="other_charges" class="form-control @error('other_charges') is-invalid @enderror" value="{{ old('other_charges', $productPurchase->other_charges) }}">
                @error('other_charges') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-4">
                <label class="form-label fw-bold small">Paid Amount (₹)</label>
                <input type="number" step="0.01" name="paid_amount" class="form-control @error('paid_amount') is-invalid @enderror" value="{{ old('paid_amount', $productPurchase->paid_amount) }}">
                @error('paid_amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-12 mt-3">
                <label class="form-label fw-bold small">Remarks</label>
                <textarea name="remarks" class="form-control @error('remarks') is-invalid @enderror" rows="2">{{ old('remarks', $productPurchase->remarks) }}</textarea>
                @error('remarks') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-12 d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                <a href="{{ route('admin.product-purchase.show', $productPurchase->id) }}" class="btn btn-light border text-secondary fw-bold px-4">Cancel</a>
                <button type="submit" class="btn btn-primary fw-bold px-4"><i class="bi bi-save me-1"></i> Update Purchase</button>
            </div>
        </div>
    </form>
</x-ui.card>
@endsection
