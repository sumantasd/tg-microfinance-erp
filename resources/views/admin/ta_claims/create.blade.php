@extends('layouts.admin')

@section('title', 'Submit Travel Allowance Claim - Grihalaxmi Finance')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h4 class="fw-bold text-dark font-heading mb-1"><i class="bi bi-file-earmark-plus me-2 text-primary"></i>Submit Travel Allowance Claim</h4>
        <p class="text-muted small mb-0">Record travel details and submit claim for manager approval and Cash Book reimbursement.</p>
    </div>
    <div>
        <a href="{{ route('admin.ta-claims.index') }}" class="btn btn-outline-secondary btn-sm rounded-pill">
            <i class="bi bi-arrow-left me-1"></i> Back to Claims List
        </a>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <!-- Verified GPS Evidence Strip -->
        <div id="verifiedGpsBanner" class="alert alert-info border border-info-subtle rounded-3 shadow-sm mb-4" style="display: none;">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <i class="bi bi-geo-alt-fill text-info me-2"></i>
                    <strong class="text-dark">GPS Tracked Distance on Selected Date:</strong>
                    <span id="verifiedGpsKmText" class="font-monospace fw-bold text-primary ms-1">0.00 km</span>
                </div>
                <button type="button" class="btn btn-sm btn-info text-white rounded-pill px-3 py-1 fw-bold" onclick="useGpsDistance()">
                    <i class="bi bi-magic me-1"></i> Use Verified GPS Distance
                </button>
            </div>
        </div>

        <x-ui.card class="border-0 shadow-sm p-4 bg-white">
            @if(session('error'))
                <div class="alert alert-danger rounded-3 mb-3 small">
                    <i class="bi bi-exclamation-triangle me-1"></i> {{ session('error') }}
                </div>
            @endif

            <form method="POST" action="{{ route('admin.ta-claims.store') }}">
                @csrf
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-secondary">Travel Date <span class="text-danger">*</span></label>
                        <input type="date" name="travel_date" id="travel_date" value="{{ old('travel_date', date('Y-m-d')) }}" max="{{ date('Y-m-d') }}" class="form-control form-control-sm" required onchange="fetchGpsDistance()">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-secondary">Transport Mode <span class="text-danger">*</span></label>
                        <select name="transport_mode" id="transport_mode" class="form-select form-select-sm" required onchange="updateRateAndCalculate()">
                            <option value="bike" selected>Two Wheeler / Bike (₹4.00/km)</option>
                            <option value="car">Four Wheeler / Car (₹8.00/km)</option>
                            <option value="bus">Public Bus (Standard)</option>
                            <option value="train">Train</option>
                            <option value="auto">Auto / Rickshaw</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-secondary">From Location <span class="text-danger">*</span></label>
                        <input type="text" name="from_location" value="{{ old('from_location', 'Branch Office') }}" class="form-control form-control-sm" placeholder="Starting point / Branch" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-secondary">To Location <span class="text-danger">*</span></label>
                        <input type="text" name="to_location" value="{{ old('to_location') }}" class="form-control form-control-sm" placeholder="Destination / Village / Customer site" required>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-secondary">Distance (km) <span class="text-danger">*</span></label>
                        <input type="number" step="0.1" name="distance_km" id="distance_km" value="{{ old('distance_km', 0) }}" class="form-control form-control-sm" required oninput="calculateTA()">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-secondary">Rate per km (₹) <span class="text-danger">*</span></label>
                        <input type="number" step="0.5" name="rate_per_km" id="rate_per_km" value="{{ old('rate_per_km', 4.00) }}" class="form-control form-control-sm" required oninput="calculateTA()">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-secondary">Total Amount (₹) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="amount" id="amount" value="{{ old('amount', 0) }}" class="form-control form-control-sm font-monospace fw-bold" required>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label small fw-semibold text-secondary">Travel Purpose / Remarks <span class="text-danger">*</span></label>
                    <textarea name="purpose" rows="3" class="form-control form-control-sm" required placeholder="e.g. Field EMI collection visit to Branch 1 villages">{{ old('purpose') }}</textarea>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('admin.ta-claims.index') }}" class="btn btn-sm btn-light border rounded-pill px-4">Cancel</a>
                    <button type="submit" class="btn btn-sm btn-primary rounded-pill px-4 fw-bold shadow-sm"><i class="bi bi-check-lg me-1"></i> Submit Claim</button>
                </div>
            </form>
        </x-ui.card>
    </div>
</div>

<script>
let gpsKm = 0;

function fetchGpsDistance() {
    const date = document.getElementById('travel_date').value;
    if (!date) return;

    fetch(`{{ url('/api/v1/ta-claims/eligible-distance') }}?date=${date}`, {
        headers: { 'Accept': 'application/json' }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success && data.data.verified_distance_km > 0) {
            gpsKm = data.data.verified_distance_km;
            document.getElementById('verifiedGpsKmText').textContent = `${gpsKm.toFixed(2)} km`;
            document.getElementById('verifiedGpsBanner').style.display = 'block';
        } else {
            document.getElementById('verifiedGpsBanner').style.display = 'none';
        }
    })
    .catch(err => {
        document.getElementById('verifiedGpsBanner').style.display = 'none';
    });
}

function useGpsDistance() {
    if (gpsKm > 0) {
        document.getElementById('distance_km').value = gpsKm.toFixed(2);
        updateRateAndCalculate();
    }
}

function updateRateAndCalculate() {
    const mode = document.getElementById('transport_mode').value;
    const rateInput = document.getElementById('rate_per_km');

    if (mode === 'car') {
        rateInput.value = 8.00;
    } else if (mode === 'bike') {
        rateInput.value = 4.00;
    }

    calculateTA();
}

function calculateTA() {
    const dist = parseFloat(document.getElementById('distance_km').value) || 0;
    const rate = parseFloat(document.getElementById('rate_per_km').value) || 0;
    if (dist > 0 && rate > 0) {
        document.getElementById('amount').value = (dist * rate).toFixed(2);
    }
}

document.addEventListener('DOMContentLoaded', fetchGpsDistance);
</script>
@endsection
