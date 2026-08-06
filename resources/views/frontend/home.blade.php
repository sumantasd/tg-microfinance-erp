@extends('layouts.public')

@section('title', 'TG Microfinance ERP - Empowering Financial Independence')
@section('meta_description', 'Empowering small businesses, micro-entrepreneurs, and individuals with fast loans, high-yield savings, and enterprise financial services.')

@section('content')
<!-- Hero Section -->
<section class="hero-section">
    <div class="container-xl">
        <div class="row align-items-center g-4 g-lg-5 py-3 py-md-4">
            <div class="col-lg-7">
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle fs-6 px-3 py-1.5 rounded-pill fw-semibold mb-3 shadow-sm d-inline-flex align-items-center gap-1 text-wrap">
                    <i class="bi bi-shield-check me-1"></i> Certified Enterprise Microfinance Portal
                </span>
                <h1 class="hero-title display-4 mb-3 text-white">Empowering Small Businesses & Micro-Entrepreneurs</h1>
                <p class="lead mb-4 text-light opacity-90">
                    Fast, accessible credit solutions, flexible savings schemes, and digital branch operations designed for community growth and financial independence.
                </p>
                <div class="d-flex flex-wrap gap-3">
                    <a href="{{ url('/apply-loan') }}" class="btn btn-primary btn-lg rounded-pill px-4 py-3 fw-bold shadow-lg d-flex align-items-center gap-2">
                        <i class="bi bi-file-earmark-text fs-5"></i> Apply for Loan
                    </a>
                    <a href="{{ url('/loan-products') }}" class="btn btn-outline-light btn-lg rounded-pill px-4 py-3 fw-semibold d-flex align-items-center gap-2">
                        <span>Explore Loan Schemes</span>
                        <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>

            <!-- Rate Calculator Widget Card -->
            <div class="col-lg-5">
                <div class="card border-0 shadow-lg rounded-4 p-4 text-dark bg-white tg-hover-lift">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="bg-primary-subtle text-primary rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
                            <i class="bi bi-calculator fs-3"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-0">Loan Rate Estimator</h5>
                            <small class="text-muted">Instant repayment calculation</small>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Estimated Principal Amount</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light fw-bold">$</span>
                            <input type="text" class="form-control bg-light fw-bold" value="5,000" readonly>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Tenure Period</label>
                        <select class="form-select bg-light fw-semibold">
                            <option>12 Months (Weekly/Monthly Repayment)</option>
                            <option>24 Months</option>
                            <option>36 Months</option>
                        </select>
                    </div>

                    <div class="p-3 bg-light rounded-3 mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="small text-muted">Estimated Interest Rate:</span>
                            <strong class="small text-primary">12.5% P.A.</strong>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-bold small text-dark">Estimated Monthly Installment:</span>
                            <h4 class="fw-bold text-success mb-0">$445.60</h4>
                        </div>
                    </div>

                    <a href="{{ url('/apply-loan') }}" class="btn btn-primary w-100 py-2.5 rounded-pill fw-bold shadow-sm">
                        Proceed with Application <i class="bi bi-chevron-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Core Features Overview -->
<section class="container-xl py-5">
    <div class="text-center mx-auto mb-5" style="max-width: 700px;">
        <span class="text-uppercase small fw-bold text-primary tracking-wider mb-2 d-block">Core Value Offerings</span>
        <h2 class="fw-bold text-dark">Why Micro-Borrowers Choose TG Microfinance</h2>
        <p class="text-muted">Transparent terms, fast counter disbursements, and nationwide branch accessibility.</p>
    </div>

    <div class="row g-4">
        <div class="col-md-4">
            <x-ui.card class="h-100 tg-hover-lift text-center p-4">
                <div class="bg-primary-subtle text-primary rounded-circle p-3 mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 64px; height: 64px;">
                    <i class="bi bi-lightning-charge fs-2"></i>
                </div>
                <h5 class="fw-bold">Fast Approval & Disbursement</h5>
                <p class="text-muted small mb-0">Streamlined digital KYC verification enabling loan approvals within 24–48 hours.</p>
            </x-ui.card>
        </div>
        <div class="col-md-4">
            <x-ui.card class="h-100 tg-hover-lift text-center p-4">
                <div class="bg-success-subtle text-success rounded-circle p-3 mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 64px; height: 64px;">
                    <i class="bi bi-piggy-bank fs-2"></i>
                </div>
                <h5 class="fw-bold">High-Yield Savings Schemes</h5>
                <p class="text-muted small mb-0">Competitive compound interest accounts tailored for micro-savers and self-help groups.</p>
            </x-ui.card>
        </div>
        <div class="col-md-4">
            <x-ui.card class="h-100 tg-hover-lift text-center p-4">
                <div class="bg-info-subtle text-info rounded-circle p-3 mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 64px; height: 64px;">
                    <i class="bi bi-diagram-3 fs-2"></i>
                </div>
                <h5 class="fw-bold">Nationwide Branch Network</h5>
                <p class="text-muted small mb-0">Access cash counters, account officers, and field collection services across our branch footprint.</p>
            </x-ui.card>
        </div>
    </div>
</section>

<!-- Impact Metrics Strip -->
<section class="bg-white border-top border-bottom py-5 overflow-hidden">
    <div class="container-xl">
        <div class="row g-4 text-center">
            <div class="col-6 col-md-3">
                <h2 class="display-6 fw-bold text-primary mb-1">50,000+</h2>
                <span class="text-muted small fw-semibold text-uppercase">Active Borrowers</span>
            </div>
            <div class="col-6 col-md-3">
                <h2 class="display-6 fw-bold text-success mb-1">$120M+</h2>
                <span class="text-muted small fw-semibold text-uppercase">Loans Disbursed</span>
            </div>
            <div class="col-6 col-md-3">
                <h2 class="display-6 fw-bold text-info mb-1">85+</h2>
                <span class="text-muted small fw-semibold text-uppercase">Branch Offices</span>
            </div>
            <div class="col-6 col-md-3">
                <h2 class="display-6 fw-bold text-warning mb-1">99.2%</h2>
                <span class="text-muted small fw-semibold text-uppercase">Repayment Efficiency</span>
            </div>
        </div>
    </div>
</section>

<!-- Call to Action Section -->
<section class="container-xl py-5">
    <div class="bg-dark text-white rounded-4 p-4 p-md-5 text-center position-relative overflow-hidden shadow-lg">
        <div class="position-relative z-1 py-3">
            <h2 class="fw-bold display-6 mb-3">Ready to Expand Your Enterprise?</h2>
            <p class="lead opacity-80 mx-auto mb-4" style="max-width: 600px;">Apply for a micro-loan online or visit any of our branch counters today.</p>
            <div class="d-flex justify-content-center gap-3 flex-wrap">
                <a href="{{ url('/apply-loan') }}" class="btn btn-primary btn-lg rounded-pill px-4 py-2.5 fw-bold shadow">
                    Apply Online Now
                </a>
                <a href="{{ url('/contact') }}" class="btn btn-outline-light btn-lg rounded-pill px-4 py-2.5 fw-semibold">
                    Contact Head Office
                </a>
            </div>
        </div>
    </div>
</section>
@endsection
