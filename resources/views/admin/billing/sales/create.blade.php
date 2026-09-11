@extends('layouts.admin')

@section('title', 'New Direct Product Sale - ' . config('app.name'))

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1"><i class="bi bi-cart-plus text-primary me-2"></i>New Direct Product Sale</h4>
        <p class="text-muted small mb-0">Record a direct inventory sale, update branch stock, and generate a customer invoice.</p>
    </div>
    <div>
        <a href="{{ route('admin.billing.invoices.index') }}" class="btn btn-outline-secondary rounded-pill px-4 fw-bold shadow-sm">
            <i class="bi bi-arrow-left me-1"></i> Back to Invoices
        </a>
    </div>
</div>

@if($errors->any())
    <div class="alert alert-danger rounded-3 shadow-sm mb-4">
        <h6 class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-1"></i> Form Validation Errors:</h6>
        <ul class="mb-0 small ps-3">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form action="{{ route('admin.billing.sales.store') }}" method="POST" id="saleForm">
    @csrf

    <div class="row g-4">
        <!-- Left Panel: Customer & Branch Details -->
        <div class="col-12 col-lg-4">
            <x-ui.card class="p-4 shadow-sm border-0 bg-white mb-4">
                <h6 class="fw-bold text-dark border-bottom pb-2 mb-3"><i class="bi bi-geo-alt-fill text-primary me-1"></i> Transaction Branch</h6>
                
                <div class="mb-3">
                    <label class="form-label fw-bold text-dark small">Branch <span class="text-danger">*</span></label>
                    <select name="branch_id" id="branchSelect" class="form-select bg-light fw-semibold" required {{ (!auth()->user()->isSuperAdmin() && !auth()->user()->isCompanyAdmin()) ? 'readonly' : '' }}>
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}" {{ old('branch_id', $selectedBranchId) == $b->id ? 'selected' : '' }}>{{ $b->name }} ({{ $b->code }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold text-dark small">Sale Date <span class="text-danger">*</span></label>
                    <input type="date" name="sale_date" value="{{ old('sale_date', now()->toDateString()) }}" class="form-control" required>
                </div>
            </x-ui.card>

            <x-ui.card class="p-4 shadow-sm border-0 bg-white mb-4">
                <h6 class="fw-bold text-dark border-bottom pb-2 mb-3"><i class="bi bi-person-bounding-box text-primary me-1"></i> Customer Information</h6>
                
                <div class="mb-3">
                    <label class="form-label fw-bold text-dark small">Registered Customer (Optional)</label>
                    <select name="customer_id" id="customerSelect" class="form-select">
                        <option value="">-- Walk-in / Unregistered Customer --</option>
                        @foreach($customers as $c)
                            <option value="{{ $c->id }}" data-name="{{ $c->full_name }}" data-phone="{{ $c->phone }}" data-address="{{ $c->address }}" {{ old('customer_id') == $c->id ? 'selected' : '' }}>
                                {{ $c->full_name }} ({{ $c->customer_code }}) - {{ $c->phone }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold text-dark small">Customer Name <span class="text-danger">*</span></label>
                    <input type="text" name="customer_name" id="customerName" value="{{ old('customer_name', 'Walk-in Customer') }}" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold text-dark small">Customer Phone</label>
                    <input type="text" name="customer_phone" id="customerPhone" value="{{ old('customer_phone') }}" class="form-control font-monospace" placeholder="9876543210">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold text-dark small">Customer Address</label>
                    <textarea name="customer_address" id="customerAddress" class="form-control" rows="2" placeholder="Customer billing address">{{ old('customer_address') }}</textarea>
                </div>
            </x-ui.card>

            <x-ui.card class="p-4 shadow-sm border-0 bg-white">
                <h6 class="fw-bold text-dark border-bottom pb-2 mb-3"><i class="bi bi-credit-card text-success me-1"></i> Payment & Settlement</h6>

                <div class="mb-3">
                    <label class="form-label fw-bold text-dark small">Payment Method <span class="text-danger">*</span></label>
                    <select name="payment_method" class="form-select bg-light fw-bold" required>
                        <option value="cash" {{ old('payment_method') === 'cash' ? 'selected' : '' }}>Cash</option>
                        <option value="bank_transfer" {{ old('payment_method') === 'bank_transfer' ? 'selected' : '' }}>Bank Transfer / NEFT</option>
                        <option value="upi" {{ old('payment_method') === 'upi' ? 'selected' : '' }}>UPI / QR Code</option>
                        <option value="cheque" {{ old('payment_method') === 'cheque' ? 'selected' : '' }}>Cheque</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold text-dark small">Paid Amount (₹) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" min="0" name="paid_amount" id="paidAmountInput" value="{{ old('paid_amount', '0.00') }}" class="form-control form-control-lg font-monospace fw-bold text-success" required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold text-dark small">Remarks / Notes</label>
                    <textarea name="remarks" class="form-control" rows="2" placeholder="Sale notes or reference details">{{ old('remarks') }}</textarea>
                </div>
            </x-ui.card>
        </div>

        <!-- Right Panel: Line Items & Totals -->
        <div class="col-12 col-lg-8">
            <x-ui.card class="p-4 shadow-sm border-0 bg-white mb-4">
                <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-3">
                    <h6 class="fw-bold text-dark mb-0"><i class="bi bi-box-seam text-primary me-1"></i> Product Line Items</h6>
                    <button type="button" class="btn btn-sm btn-outline-primary fw-bold rounded-pill" id="addRowBtn">
                        <i class="bi bi-plus-circle me-1"></i> Add Product Item
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table align-middle" id="itemsTable">
                        <thead class="table-light extra-small text-uppercase fw-bold text-muted">
                            <tr>
                                <th style="width: 40%;">Product Item</th>
                                <th style="width: 15%;">Unit Price (₹)</th>
                                <th style="width: 12%;">Qty</th>
                                <th style="width: 13%;">Discount (₹)</th>
                                <th style="width: 15%;" class="text-end">Total (₹)</th>
                                <th style="width: 5%;"></th>
                            </tr>
                        </thead>
                        <tbody id="itemsTbody">
                            <!-- Dynamic rows appended via Javascript -->
                        </tbody>
                    </table>
                </div>

                <!-- Financial Summary Box -->
                <div class="p-3 bg-light rounded-3 border mt-3">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <small class="text-muted d-block">Inventory Stock Note:</small>
                            <small class="text-secondary extra-small">Direct sale immediately decrements physical branch inventory stock.</small>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex justify-content-between small mb-1">
                                <span>Subtotal:</span>
                                <strong id="subtotalDisplay" class="font-monospace">₹0.00</strong>
                            </div>
                            <div class="d-flex justify-content-between small mb-1 text-danger">
                                <span>Total Discount:</span>
                                <strong id="discountDisplay" class="font-monospace">-₹0.00</strong>
                            </div>
                            <hr class="my-1">
                            <div class="d-flex justify-content-between fs-5 fw-bold text-dark">
                                <span>Grand Total:</span>
                                <strong id="grandTotalDisplay" class="font-monospace text-primary">₹0.00</strong>
                            </div>
                            <div class="d-flex justify-content-between small text-danger mt-1">
                                <span>Balance Due:</span>
                                <strong id="balanceDueDisplay" class="font-monospace">₹0.00</strong>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end mt-4">
                    <button type="submit" class="btn btn-primary rounded-pill px-5 py-2.5 fw-bold shadow">
                        <i class="bi bi-check-circle-fill me-1"></i> Complete Sale & Generate Invoice
                    </button>
                </div>
            </x-ui.card>
        </div>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const products = @json($products);
    const tbody = document.getElementById('itemsTbody');
    const addRowBtn = document.getElementById('addRowBtn');
    const customerSelect = document.getElementById('customerSelect');
    const customerName = document.getElementById('customerName');
    const customerPhone = document.getElementById('customerPhone');
    const customerAddress = document.getElementById('customerAddress');
    const paidAmountInput = document.getElementById('paidAmountInput');

    let rowIndex = 0;

    // Handle Registered Customer Selection Fill
    if (customerSelect) {
        customerSelect.addEventListener('change', function () {
            const selected = this.options[this.selectedIndex];
            if (this.value) {
                customerName.value = selected.getAttribute('data-name') || '';
                customerPhone.value = selected.getAttribute('data-phone') || '';
                customerAddress.value = selected.getAttribute('data-address') || '';
            } else {
                customerName.value = 'Walk-in Customer';
                customerPhone.value = '';
                customerAddress.value = '';
            }
        });
    }

    function addRow() {
        const tr = document.createElement('tr');
        tr.className = 'item-row';
        tr.innerHTML = `
            <td>
                <select name="items[${rowIndex}][product_id]" class="form-select product-select" required>
                    <option value="">-- Select Product --</option>
                    ${products.map(p => {
                        const stockObj = p.inventory_stocks && p.inventory_stocks.length > 0 ? p.inventory_stocks[0] : null;
                        const avail = stockObj ? stockObj.current_stock : 0;
                        return `<option value="${p.id}" data-price="${p.unit_price}" data-stock="${avail}">${p.name} (${p.sku}) - Stock: ${avail}</option>`;
                    }).join('')}
                </select>
                <small class="stock-warning text-danger extra-small d-none mt-1"></small>
            </td>
            <td>
                <input type="number" step="0.01" min="0" name="items[${rowIndex}][unit_price]" class="form-control font-monospace unit-price-input" value="0.00" required>
            </td>
            <td>
                <input type="number" min="1" name="items[${rowIndex}][quantity]" class="form-control font-monospace quantity-input" value="1" required>
            </td>
            <td>
                <input type="number" step="0.01" min="0" name="items[${rowIndex}][discount]" class="form-control font-monospace discount-input" value="0.00">
            </td>
            <td class="text-end font-monospace fw-bold">
                <span class="line-total-display">₹0.00</span>
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-link text-danger remove-row-btn p-0" title="Remove"><i class="bi bi-trash fs-6"></i></button>
            </td>
        `;

        tbody.appendChild(tr);

        const prodSelect = tr.querySelector('.product-select');
        const priceInput = tr.querySelector('.unit-price-input');
        const qtyInput = tr.querySelector('.quantity-input');
        const discInput = tr.querySelector('.discount-input');
        const warning = tr.querySelector('.stock-warning');
        const removeBtn = tr.querySelector('.remove-row-btn');

        prodSelect.addEventListener('change', function () {
            const opt = this.options[this.selectedIndex];
            if (this.value) {
                const price = parseFloat(opt.getAttribute('data-price')) || 0;
                const stock = parseInt(opt.getAttribute('data-stock')) || 0;
                priceInput.value = price.toFixed(2);

                if (stock <= 0) {
                    warning.textContent = 'Out of stock at this branch!';
                    warning.classList.remove('d-none');
                } else {
                    warning.classList.add('d-none');
                }
            }
            recalculateTotals();
        });

        priceInput.addEventListener('input', recalculateTotals);
        qtyInput.addEventListener('input', function () {
            const opt = prodSelect.options[prodSelect.selectedIndex];
            if (opt && opt.value) {
                const stock = parseInt(opt.getAttribute('data-stock')) || 0;
                const qty = parseInt(this.value) || 0;
                if (qty > stock) {
                    warning.textContent = `Only ${stock} available in stock!`;
                    warning.classList.remove('d-none');
                } else {
                    warning.classList.add('d-none');
                }
            }
            recalculateTotals();
        });
        discInput.addEventListener('input', recalculateTotals);

        removeBtn.addEventListener('click', function () {
            if (tbody.querySelectorAll('.item-row').length > 1) {
                tr.remove();
                recalculateTotals();
            }
        });

        rowIndex++;
        recalculateTotals();
    }

    function recalculateTotals() {
        let subtotal = 0;
        let totalDiscount = 0;

        document.querySelectorAll('.item-row').forEach(row => {
            const price = parseFloat(row.querySelector('.unit-price-input').value) || 0;
            const qty = parseInt(row.querySelector('.quantity-input').value) || 0;
            const disc = parseFloat(row.querySelector('.discount-input').value) || 0;

            const lineSub = price * qty;
            const lineTot = Math.max(0, lineSub - disc);

            row.querySelector('.line-total-display').textContent = '₹' + lineTot.toFixed(2);

            subtotal += lineSub;
            totalDiscount += disc;
        });

        const grandTotal = Math.max(0, subtotal - totalDiscount);
        
        document.getElementById('subtotalDisplay').textContent = '₹' + subtotal.toFixed(2);
        document.getElementById('discountDisplay').textContent = '-₹' + totalDiscount.toFixed(2);
        document.getElementById('grandTotalDisplay').textContent = '₹' + grandTotal.toFixed(2);

        // Auto fill paid amount if not touched or greater than grandTotal
        if (!paidAmountInput.dataset.userEdited) {
            paidAmountInput.value = grandTotal.toFixed(2);
        }

        const paid = parseFloat(paidAmountInput.value) || 0;
        const balance = Math.max(0, grandTotal - paid);
        document.getElementById('balanceDueDisplay').textContent = '₹' + balance.toFixed(2);
    }

    paidAmountInput.addEventListener('input', function () {
        this.dataset.userEdited = 'true';
        recalculateTotals();
    });

    addRowBtn.addEventListener('click', addRow);

    // Initial first row
    addRow();
});
</script>
@endsection
