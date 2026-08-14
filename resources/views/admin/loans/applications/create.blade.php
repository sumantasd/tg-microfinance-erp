@extends('layouts.admin')

@section('title', 'Apply for Loan - Grihalaxmi Finance ERP')

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
                <select name="loan_scheme_id" class="form-select @error('loan_scheme_id') is-invalid @enderror" required>
                    <option value="">Select Finance Loan Scheme</option>
                    @foreach($schemes as $s)
                        <option value="{{ $s->id }}" {{ old('loan_scheme_id') == $s->id ? 'selected' : '' }}>
                            {{ $s->name }} ({{ $s->code }}) - {{ $s->interest_rate_per_annum }}% p.a. | Min: ₹{{ number_format($s->min_amount) }} - Max: ₹{{ number_format($s->max_amount) }}
                        </option>
                    @endforeach
                </select>
                @error('loan_scheme_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <h5 class="fw-bold text-dark border-bottom pb-2 mt-4 mb-3">2. Requested Amount & Terms</h5>

            <div class="col-md-4">
                <label class="form-label fw-bold small">Application Date <span class="text-danger">*</span></label>
                <input type="date" name="application_date" class="form-control @error('application_date') is-invalid @enderror" value="{{ old('application_date', date('Y-m-d')) }}" required>
                @error('application_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-4">
                <label class="form-label fw-bold small">Total Requested Amount (₹) <span class="text-danger">*</span></label>
                <input type="number" step="0.01" name="requested_amount" id="totalRequestedAmount" class="form-control @error('requested_amount') is-invalid @enderror" value="{{ old('requested_amount') }}" placeholder="e.g. 50000.00" required>
                @error('requested_amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-4">
                <label class="form-label fw-bold small">Requested Tenure (Months) <span class="text-danger">*</span></label>
                <input type="number" name="tenure_months" class="form-control @error('tenure_months') is-invalid @enderror" value="{{ old('tenure_months', 12) }}" required>
                @error('tenure_months') <div class="invalid-feedback">{{ $message }}</div> @enderror
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

            <!-- Product Items Section (Conditional) -->
            <div class="col-12 mt-4 d-none" id="productItemsContainer">
                <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                    <h5 class="fw-bold text-dark mb-0">Product Loan Items</h5>
                    <button type="button" class="btn btn-sm btn-outline-primary fw-bold" id="btnAddProductRow"><i class="bi bi-plus-lg me-1"></i> Add Product Item</button>
                </div>
                <div id="productRowsContainer">
                    <div class="row g-2 mb-2 product-item-row">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Select Product Catalog Item <span class="text-danger">*</span></label>
                            <select name="products[0][product_id]" class="form-select product-select">
                                <option value="">Choose Product</option>
                                @foreach($products as $p)
                                    <option value="{{ $p->id }}" data-price="{{ $p->unit_price }}">{{ $p->name }} (SKU: {{ $p->sku }}) - ₹{{ number_format($p->unit_price, 2) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Quantity <span class="text-danger">*</span></label>
                            <input type="number" name="products[0][quantity]" class="form-control product-qty" placeholder="Qty" min="1" value="1">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="button" class="btn btn-outline-danger w-100 btn-remove-product disabled"><i class="bi bi-trash"></i></button>
                        </div>
                    </div>
                </div>

                <div class="alert alert-primary shadow-sm border mt-3 d-none" id="productFinancialSummaryBox">
                    <div class="row align-items-center text-center text-md-start">
                        <div class="col-md-4 mb-2 mb-md-0">
                            <span class="text-muted small d-block uppercase">Gross Product Price</span>
                            <strong class="fs-5 font-monospace text-dark" id="summaryGrossPrice">₹0.00</strong>
                        </div>
                        <div class="col-md-4 mb-2 mb-md-0">
                            <span class="text-muted small d-block uppercase">Requested Financed Amount</span>
                            <strong class="fs-5 font-monospace text-primary" id="summaryFinancedAmount">₹0.00</strong>
                        </div>
                        <div class="col-md-4">
                            <span class="text-muted small d-block uppercase">Estimated Down Payment</span>
                            <strong class="fs-5 font-monospace text-success" id="summaryDownPayment">₹0.00</strong>
                        </div>
                    </div>
                    <div class="small text-muted mt-2 border-top pt-2">
                        <i class="bi bi-info-circle me-1"></i> <strong>Financial Formula:</strong> Financed Principal = Product Price - Down Payment. EMI and interest are calculated <strong>ONLY</strong> on the Financed Principal!
                    </div>
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
        const indContainer = document.getElementById('individualBorrowerContainer');
        const grpContainer = document.getElementById('groupBorrowerContainer');
        const grpMemberContainer = document.getElementById('groupMemberAllocationContainer');
        const productContainer = document.getElementById('productItemsContainer');
        const groupSelect = document.getElementById('groupSelect');
        const groupMemberRows = document.getElementById('groupMemberRows');
        const indSelect = document.getElementById('individualCustomerSelect');
        const productRowsContainer = document.getElementById('productRowsContainer');
        const btnAddProductRow = document.getElementById('btnAddProductRow');

        let productRowCount = 1;

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

        function calculateProductSummary() {
            if (loanTypeSelect.value !== 'product') {
                document.getElementById('productFinancialSummaryBox').classList.add('d-none');
                return;
            }

            document.getElementById('productFinancialSummaryBox').classList.remove('d-none');

            let grossPrice = 0.00;
            const rows = productRowsContainer.querySelectorAll('.product-item-row');
            rows.forEach(row => {
                const sel = row.querySelector('.product-select');
                const qtyInput = row.querySelector('.product-qty');
                if (sel && sel.selectedIndex > 0) {
                    const price = parseFloat(sel.options[sel.selectedIndex].dataset.price || 0);
                    const qty = parseFloat(qtyInput ? qtyInput.value || 0 : 0);
                    grossPrice += (price * qty);
                }
            });

            const reqInput = document.getElementById('totalRequestedAmount');
            let financed = parseFloat(reqInput ? reqInput.value || 0 : 0);
            if (financed <= 0) {
                financed = grossPrice;
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
        document.getElementById('totalRequestedAmount').addEventListener('input', calculateProductSummary);

        productRowsContainer.addEventListener('change', calculateProductSummary);
        productRowsContainer.addEventListener('input', calculateProductSummary);

        btnAddProductRow.addEventListener('click', function () {
            productRowIndex++;
            const firstRow = productRowsContainer.querySelector('.product-item-row');
            const newRow = firstRow.cloneNode(true);

            const sel = newRow.querySelector('.product-select');
            sel.name = `products[${productRowIndex}][product_id]`;
            sel.selectedIndex = 0;

            const qty = newRow.querySelector('.product-qty');
            qty.name = `products[${productRowIndex}][quantity]`;
            qty.value = 1;

            const removeBtn = newRow.querySelector('.btn-remove-product');
            removeBtn.classList.remove('disabled');
            removeBtn.addEventListener('click', function () {
                newRow.remove();
                calculateProductSummary();
            });

            productRowsContainer.appendChild(newRow);
            calculateProductSummary();
        });

        toggleVisibility();
    });
</script>
@endpush
@endsection
