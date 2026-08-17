@extends('layouts.admin')

@section('title', 'Create Journal Voucher - Grihalaxmi Finance ERP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">
            <i class="bi bi-pencil-square text-primary me-2"></i>Create Double-Entry Financial Voucher
        </h4>
        <p class="text-muted small mb-0">Record manual journal, payment, receipt, or contra entries into the General Ledger.</p>
    </div>
    <a href="{{ route('admin.accounting.vouchers.index') }}" class="btn btn-outline-secondary rounded-pill px-3">
        <i class="bi bi-arrow-left me-1"></i> Back to Vouchers
    </a>
</div>

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
        <div class="fw-bold mb-1"><i class="bi bi-exclamation-octagon-fill me-2"></i>Please resolve the following validation errors:</div>
        <ul class="mb-0 small ps-3">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<form action="{{ route('admin.accounting.vouchers.store') }}" method="POST" id="voucherForm">
    @csrf

    <!-- Voucher Header Card -->
    <x-ui.card class="shadow-sm border-0 p-4 mb-4">
        <div class="row g-3">
            @if(auth()->user()->hasRole('Super Admin'))
                <div class="col-md-3">
                    <label class="form-label fw-bold small">Company <span class="text-danger">*</span></label>
                    <select name="company_id" class="form-select @error('company_id') is-invalid @enderror" required>
                        @foreach($companies as $company)
                            <option value="{{ $company->id }}" {{ old('company_id', $companyId) == $company->id ? 'selected' : '' }}>{{ $company->name }}</option>
                        @endforeach
                    </select>
                </div>
            @else
                <input type="hidden" name="company_id" value="{{ auth()->user()->company_id }}">
            @endif

            <div class="col-md-3">
                <label class="form-label fw-bold small">Voucher Type <span class="text-danger">*</span></label>
                <select name="voucher_type" id="voucherTypeSelect" class="form-select @error('voucher_type') is-invalid @enderror" required>
                    <option value="journal" {{ old('voucher_type', 'journal') === 'journal' ? 'selected' : '' }}>Journal Voucher (JV)</option>
                    <option value="receipt" {{ old('voucher_type') === 'receipt' ? 'selected' : '' }}>Receipt Voucher (RV)</option>
                    <option value="payment" {{ old('voucher_type') === 'payment' ? 'selected' : '' }}>Payment Voucher (PV)</option>
                    <option value="contra" {{ old('voucher_type') === 'contra' ? 'selected' : '' }}>Contra Voucher (CV)</option>
                </select>
                @error('voucher_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-3">
                <label class="form-label fw-bold small">Operating Branch <span class="text-danger">*</span></label>
                <select name="branch_id" class="form-select @error('branch_id') is-invalid @enderror" required>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" {{ old('branch_id', auth()->user()->branch_id) == $branch->id ? 'selected' : '' }}>
                            {{ $branch->name }} ({{ $branch->code }})
                        </option>
                    @endforeach
                </select>
                @error('branch_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-3">
                <label class="form-label fw-bold small">Posting Date <span class="text-danger">*</span></label>
                <input type="date" name="voucher_date" class="form-control @error('voucher_date') is-invalid @enderror" value="{{ old('voucher_date', now()->toDateString()) }}" required>
                @error('voucher_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-12">
                <label class="form-label fw-bold small">General Narration / Memo</label>
                <textarea name="narration" class="form-control @error('narration') is-invalid @enderror" rows="2" placeholder="State the business rationale or reference for this voucher...">{{ old('narration') }}</textarea>
                @error('narration') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>
    </x-ui.card>

    <!-- Double-Entry Table Card -->
    <x-ui.card class="shadow-sm border-0 p-0 mb-4">
        <div class="p-3 border-bottom d-flex justify-content-between align-items-center bg-light">
            <h6 class="fw-bold text-dark mb-0"><i class="bi bi-list-columns text-primary me-2"></i>Ledger Entry Lines (Debit & Credit)</h6>
            <button type="button" class="btn btn-sm btn-primary rounded-pill px-3 fw-bold" id="addRowBtn">
                <i class="bi bi-plus-lg me-1"></i> Add Entry Line
            </button>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered align-middle mb-0" id="entriesTable">
                <thead class="table-light small text-uppercase">
                    <tr>
                        <th style="width: 40%;">Ledger Account <span class="text-danger">*</span></th>
                        <th style="width: 25%;">Line Description</th>
                        <th style="width: 15%;" class="text-end">Debit (₹)</th>
                        <th style="width: 15%;" class="text-end">Credit (₹)</th>
                        <th style="width: 5%;" class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody id="entriesBody">
                    <!-- Initial Line 1 (Debit) -->
                    <tr class="entry-row">
                        <td>
                            <select name="entries[0][account_id]" class="form-select account-select" required>
                                <option value="">-- Select Ledger Account --</option>
                                @foreach($accounts as $acc)
                                    <option value="{{ $acc->id }}">
                                        {{ $acc->account_code }} — {{ $acc->account_name }} ({{ ucfirst($acc->account_type) }})
                                    </option>
                                @endforeach
                            </select>
                        </td>
                        <td>
                            <input type="text" name="entries[0][description]" class="form-control form-control-sm" placeholder="Line note...">
                        </td>
                        <td>
                            <input type="number" step="0.01" min="0" name="entries[0][debit]" class="form-control form-control-sm text-end debit-input font-monospace" placeholder="0.00" value="0.00">
                        </td>
                        <td>
                            <input type="number" step="0.01" min="0" name="entries[0][credit]" class="form-control form-control-sm text-end credit-input font-monospace" placeholder="0.00" value="0.00">
                        </td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-outline-danger remove-row-btn" disabled><i class="bi bi-trash"></i></button>
                        </td>
                    </tr>

                    <!-- Initial Line 2 (Credit) -->
                    <tr class="entry-row">
                        <td>
                            <select name="entries[1][account_id]" class="form-select account-select" required>
                                <option value="">-- Select Ledger Account --</option>
                                @foreach($accounts as $acc)
                                    <option value="{{ $acc->id }}">
                                        {{ $acc->account_code }} — {{ $acc->account_name }} ({{ ucfirst($acc->account_type) }})
                                    </option>
                                @endforeach
                            </select>
                        </td>
                        <td>
                            <input type="text" name="entries[1][description]" class="form-control form-control-sm" placeholder="Line note...">
                        </td>
                        <td>
                            <input type="number" step="0.01" min="0" name="entries[1][debit]" class="form-control form-control-sm text-end debit-input font-monospace" placeholder="0.00" value="0.00">
                        </td>
                        <td>
                            <input type="number" step="0.01" min="0" name="entries[1][credit]" class="form-control form-control-sm text-end credit-input font-monospace" placeholder="0.00" value="0.00">
                        </td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-outline-danger remove-row-btn" disabled><i class="bi bi-trash"></i></button>
                        </td>
                    </tr>
                </tbody>
                <tfoot class="table-light fw-bold font-monospace">
                    <tr>
                        <td colspan="2" class="text-end text-dark uppercase fs-6">Totals:</td>
                        <td class="text-end text-primary fs-6" id="totalDebitDisplay">₹0.00</td>
                        <td class="text-end text-primary fs-6" id="totalCreditDisplay">₹0.00</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Balance Status Bar -->
        <div class="p-3 border-top d-flex justify-content-between align-items-center" id="balanceStatusBar">
            <div>
                <span class="badge bg-secondary-subtle text-secondary border px-3 py-2 fs-6" id="balanceBadge">
                    <i class="bi bi-arrow-left-right me-1"></i> Balance Difference: ₹0.00
                </span>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('admin.accounting.vouchers.index') }}" class="btn btn-light border text-secondary fw-bold px-4">Cancel</a>
                <button type="submit" class="btn btn-primary text-white fw-bold px-4" id="submitVoucherBtn" disabled>
                    <i class="bi bi-check2-circle me-1"></i> Post Double-Entry Voucher
                </button>
            </div>
        </div>
    </x-ui.card>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let rowIndex = 2;
    const entriesBody = document.getElementById('entriesBody');
    const addRowBtn = document.getElementById('addRowBtn');
    const totalDebitDisplay = document.getElementById('totalDebitDisplay');
    const totalCreditDisplay = document.getElementById('totalCreditDisplay');
    const balanceBadge = document.getElementById('balanceBadge');
    const submitBtn = document.getElementById('submitVoucherBtn');

    // Build Account Options template
    const firstSelect = document.querySelector('.account-select');
    const accountOptionsHtml = firstSelect ? firstSelect.innerHTML : '';

    function updateRowButtons() {
        const rows = document.querySelectorAll('.entry-row');
        rows.forEach(r => {
            const btn = r.querySelector('.remove-row-btn');
            if (btn) {
                btn.disabled = (rows.length <= 2);
            }
        });
    }

    function calculateTotals() {
        let totalDebit = 0;
        let totalCredit = 0;

        document.querySelectorAll('.debit-input').forEach(input => {
            const val = parseFloat(input.value) || 0;
            totalDebit += val;
        });

        document.querySelectorAll('.credit-input').forEach(input => {
            const val = parseFloat(input.value) || 0;
            totalCredit += val;
        });

        totalDebit = Math.round(totalDebit * 100) / 100;
        totalCredit = Math.round(totalCredit * 100) / 100;

        totalDebitDisplay.innerText = '₹' + totalDebit.toFixed(2);
        totalCreditDisplay.innerText = '₹' + totalCredit.toFixed(2);

        const diff = Math.abs(totalDebit - totalCredit);

        if (totalDebit > 0 && diff === 0) {
            balanceBadge.className = 'badge bg-success-subtle text-success border border-success-subtle px-3 py-2 fs-6';
            balanceBadge.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> Balanced Double-Entry (₹' + totalDebit.toFixed(2) + ')';
            submitBtn.disabled = false;
        } else if (totalDebit === 0 && totalCredit === 0) {
            balanceBadge.className = 'badge bg-secondary-subtle text-secondary border px-3 py-2 fs-6';
            balanceBadge.innerHTML = '<i class="bi bi-dash-circle me-1"></i> Enter Line Amounts';
            submitBtn.disabled = true;
        } else {
            balanceBadge.className = 'badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2 fs-6';
            balanceBadge.innerHTML = '<i class="bi bi-exclamation-triangle-fill me-1"></i> Out of Balance! Diff: ₹' + diff.toFixed(2);
            submitBtn.disabled = true;
        }
    }

    // Prevent both Debit and Credit on same line
    entriesBody.addEventListener('input', function(e) {
        if (e.target.classList.contains('debit-input')) {
            const row = e.target.closest('tr');
            const creditInput = row.querySelector('.credit-input');
            if (parseFloat(e.target.value) > 0) {
                creditInput.value = '0.00';
            }
            calculateTotals();
        } else if (e.target.classList.contains('credit-input')) {
            const row = e.target.closest('tr');
            const debitInput = row.querySelector('.debit-input');
            if (parseFloat(e.target.value) > 0) {
                debitInput.value = '0.00';
            }
            calculateTotals();
        }
    });

    // Add Line
    addRowBtn.addEventListener('click', function() {
        const newTr = document.createElement('tr');
        newTr.className = 'entry-row';
        newTr.innerHTML = `
            <td>
                <select name="entries[${rowIndex}][account_id]" class="form-select account-select" required>
                    ${accountOptionsHtml}
                </select>
            </td>
            <td>
                <input type="text" name="entries[${rowIndex}][description]" class="form-control form-control-sm" placeholder="Line note...">
            </td>
            <td>
                <input type="number" step="0.01" min="0" name="entries[${rowIndex}][debit]" class="form-control form-control-sm text-end debit-input font-monospace" placeholder="0.00" value="0.00">
            </td>
            <td>
                <input type="number" step="0.01" min="0" name="entries[${rowIndex}][credit]" class="form-control form-control-sm text-end credit-input font-monospace" placeholder="0.00" value="0.00">
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger remove-row-btn"><i class="bi bi-trash"></i></button>
            </td>
        `;
        entriesBody.appendChild(newTr);
        rowIndex++;
        updateRowButtons();
        calculateTotals();
    });

    // Remove Line
    entriesBody.addEventListener('click', function(e) {
        if (e.target.closest('.remove-row-btn')) {
            const rows = document.querySelectorAll('.entry-row');
            if (rows.length > 2) {
                e.target.closest('tr').remove();
                updateRowButtons();
                calculateTotals();
            }
        }
    });

    updateRowButtons();
    calculateTotals();
});
</script>
@endsection
