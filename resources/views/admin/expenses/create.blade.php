@extends('layouts.admin')

@section('title', 'Create Expense - Grihalaxmi Finance ERP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">
            <i class="bi bi-plus-circle text-primary me-2"></i>Record New Expense Entry
        </h4>
        <p class="text-muted small mb-0">Fill in the operational expense details below.</p>
    </div>
    <a href="{{ route('admin.expenses.index') }}" class="btn btn-outline-secondary rounded-pill px-3">
        <i class="bi bi-arrow-left me-1"></i> Back to List
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

<form method="POST" action="{{ route('admin.expenses.store') }}" enctype="multipart/form-data">
    @csrf
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="fw-bold text-dark mb-0"><i class="bi bi-info-circle text-primary me-2"></i>Expense Information</h6>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-dark">Branch Allocation <span class="text-danger">*</span></label>
                            <select name="branch_id" class="form-select @error('branch_id') is-invalid @enderror" required>
                                <option value="">Select Permitted Branch</option>
                                @foreach($branches as $b)
                                    <option value="{{ $b->id }}" {{ (old('branch_id', auth()->user()->branch_id) == $b->id) ? 'selected' : '' }}>
                                        {{ $b->name }} ({{ $b->code }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-dark">Expense Date <span class="text-danger">*</span></label>
                            <input type="date" name="expense_date" class="form-control @error('expense_date') is-invalid @enderror" value="{{ old('expense_date', date('Y-m-d')) }}" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-dark">Expense Category <span class="text-danger">*</span></label>
                            <select name="expense_category_id" class="form-select @error('expense_category_id') is-invalid @enderror" required>
                                <option value="">Select Expense Category</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}" {{ old('expense_category_id') == $cat->id ? 'selected' : '' }}>
                                        {{ $cat->category_name }} {{ $cat->category_code ? "({$cat->category_code})" : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-dark">Requested By Staff</label>
                            <select name="requested_by" class="form-select">
                                <option value="">Default (Current Staff: {{ auth()->user()->name }})</option>
                                @foreach($staffUsers as $u)
                                    <option value="{{ $u->id }}" {{ old('requested_by', auth()->id()) == $u->id ? 'selected' : '' }}>
                                        {{ $u->name }} ({{ $u->email }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-semibold text-dark">Expense Description / Particulars <span class="text-danger">*</span></label>
                            <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="3" placeholder="Provide details regarding the operational expense incurred..." required>{{ old('description') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="fw-bold text-dark mb-0"><i class="bi bi-person-truck text-info me-2"></i>Payee / Vendor Details</h6>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-dark">Registered Vendor / Supplier (Optional)</label>
                            <select name="supplier_id" id="supplier_id" class="form-select">
                                <option value="">None / Manual Payee Entry</option>
                                @foreach($suppliers as $sup)
                                    <option value="{{ $sup->id }}" {{ old('supplier_id') == $sup->id ? 'selected' : '' }}>
                                        {{ $sup->supplier_name }} {{ $sup->company_name ? "({$sup->company_name})" : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-dark">Direct Payee Name (If not registered)</label>
                            <input type="text" name="payee_name" id="payee_name" class="form-control" placeholder="e.g. Electric Company, Landlord Name, Contractor" value="{{ old('payee_name') }}">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-dark">Bill / Invoice / Reference Number</label>
                            <input type="text" name="reference_number" class="form-control" placeholder="e.g. INV-9982, Bill #1029" value="{{ old('reference_number') }}">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="fw-bold text-dark mb-0"><i class="bi bi-cash-coin text-success me-2"></i>Amount & Tax</h6>
                </div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark">Base Amount (₹) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="amount" id="base_amount" class="form-control form-control-lg @error('amount') is-invalid @enderror" placeholder="0.00" value="{{ old('amount') }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark">GST / Tax Amount (₹)</label>
                        <input type="number" step="0.01" name="tax_amount" id="tax_amount" class="form-control" placeholder="0.00" value="{{ old('tax_amount', '0.00') }}">
                    </div>

                    <div class="p-3 bg-light rounded-3 mb-3 border">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-bold text-muted">Total Expense Amount:</span>
                            <span class="fs-4 fw-bold text-primary" id="total_display">₹0.00</span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark">Receipt / Bill Attachment</label>
                        <input type="file" name="attachment" class="form-control">
                        <small class="text-muted d-block mt-1">Allowed: PDF, JPG, PNG, DOC, XLS (Max 10MB)</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark">Internal Remarks / Notes</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Optional notes for manager review...">{{ old('notes') }}</textarea>
                    </div>

                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" name="auto_submit" value="1" id="auto_submit" checked>
                        <label class="form-check-label fw-semibold small" for="auto_submit">
                            Submit for approval immediately after saving
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold shadow-sm">
                        <i class="bi bi-check-circle me-1"></i> Save Expense Entry
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const baseInput = document.getElementById('base_amount');
    const taxInput = document.getElementById('tax_amount');
    const totalDisplay = document.getElementById('total_display');

    function updateTotal() {
        const base = parseFloat(baseInput.value) || 0;
        const tax = parseFloat(taxInput.value) || 0;
        const total = base + tax;
        totalDisplay.textContent = '₹' + total.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    baseInput.addEventListener('input', updateTotal);
    taxInput.addEventListener('input', updateTotal);
    updateTotal();
});
</script>
@endpush
