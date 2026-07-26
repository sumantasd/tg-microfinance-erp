@extends('layouts.public')

@section('title', 'TG Microfinance ERP - Empowering Financial Independence')
@section('meta_description', 'Empowering small businesses, micro-entrepreneurs, and individuals with fast loans, high-yield savings, and enterprise financial services.')

@section('content')

<!-- 1. HERO BANNER -->
<section class="hero-section">
    <div class="container-xl">
        <div class="row align-items-center g-4 g-lg-5 py-3 py-md-4">
            <div class="col-lg-7">
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle fs-6 px-3 py-1.5 rounded-pill fw-semibold mb-3 shadow-sm d-inline-flex align-items-center gap-1 text-wrap">
                    <i class="bi bi-shield-check me-1"></i> Certified Enterprise Microfinance Institution
                </span>
                <h1 class="hero-title display-4 mb-3">Empowering Small Businesses & Micro-Entrepreneurs</h1>
                <p class="lead mb-4 text-light opacity-90">
                    Fast, accessible credit solutions, flexible savings schemes, and digital branch operations designed for community growth and financial independence.
                </p>
                <div class="d-flex flex-wrap gap-3">
                    <a href="{{ url('/apply-loan') }}" class="btn btn-primary btn-lg rounded-pill px-4 py-3 fw-bold shadow-lg d-flex align-items-center gap-2">
                        <i class="bi bi-file-earmark-text fs-5"></i> Apply for Loan
                    </a>
                    <a href="{{ url('/products/loan') }}" class="btn btn-outline-light btn-lg rounded-pill px-4 py-3 fw-semibold d-flex align-items-center gap-2">
                        <span>Explore Loan Products</span>
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
                            <span class="fw-bold small text-dark">Estimated Monthly Repayment:</span>
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

<!-- 2. ABOUT COMPANY -->
<section class="container-xl py-5">
    <div class="row align-items-center g-4 g-lg-5">
        <div class="col-lg-6">
            <span class="text-uppercase small fw-bold text-primary tracking-wider mb-2 d-block">About TG Microfinance</span>
            <h2 class="display-6 fw-bold text-dark mb-3">Pioneering Financial Inclusion for Over 15 Years</h2>
            <p class="text-muted lead mb-4">
                TG Microfinance is a regulated microfinance institution providing tailored financial capital, group savings schemes, and doorstep field banking to micro-borrowers and underserved business communities.
            </p>

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <x-ui.card class="p-3 bg-light border-0 h-100">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi bi-bullseye text-primary fs-4"></i>
                            <h6 class="fw-bold mb-0">Our Mission</h6>
                        </div>
                        <p class="text-muted small mb-0">To deliver transparent, accessible credit that fosters self-reliance and community wealth creation.</p>
                    </x-ui.card>
                </div>
                <div class="col-md-6">
                    <x-ui.card class="p-3 bg-light border-0 h-100">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi bi-eye text-success fs-4"></i>
                            <h6 class="fw-bold mb-0">Our Vision</h6>
                        </div>
                        <p class="text-muted small mb-0">To be the most trusted digital microfinance institution recognized for client protection and impact.</p>
                    </x-ui.card>
                </div>
            </div>

            <a href="{{ url('/about') }}" class="btn btn-outline-primary rounded-pill px-4 py-2 fw-bold d-inline-flex align-items-center gap-2">
                <span>Read Full Corporate Profile</span>
                <i class="bi bi-arrow-right"></i>
            </a>
        </div>

        <div class="col-lg-6">
            <div class="position-relative">
                <x-ui.card class="p-4 p-md-5 border-0 shadow-lg rounded-4 bg-dark text-white">
                    <div class="d-flex align-items-center gap-3 mb-4">
                        <div class="bg-primary text-white rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 56px; height: 56px;">
                            <i class="bi bi-bank2 fs-3"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-0 text-white">Institutional Governance</h5>
                            <small class="text-light opacity-75">Regulated Micro-Finance ERP</small>
                        </div>
                    </div>
                    <ul class="list-unstyled mb-0 d-flex flex-column gap-3">
                        <li class="d-flex align-items-center gap-3">
                            <i class="bi bi-check-circle-fill text-primary fs-5"></i>
                            <span>Double-entry general ledger audited financial accounting</span>
                        </li>
                        <li class="d-flex align-items-center gap-3">
                            <i class="bi bi-check-circle-fill text-primary fs-5"></i>
                            <span>Field officer GPS biometric KYC identification</span>
                        </li>
                        <li class="d-flex align-items-center gap-3">
                            <i class="bi bi-check-circle-fill text-primary fs-5"></i>
                            <span>Central vault limit controls and instant digital receipts</span>
                        </li>
                    </ul>
                </x-ui.card>
            </div>
        </div>
    </div>
</section>

<!-- 3. WHY CHOOSE TG MICROFINANCE (6 FEATURE CARDS) -->
<section class="bg-white py-5 border-top border-bottom">
    <div class="container-xl">
        <div class="text-center mx-auto mb-5" style="max-width: 700px;">
            <span class="text-uppercase small fw-bold text-primary tracking-wider mb-2 d-block">Institutional Strengths</span>
            <h2 class="fw-bold text-dark">Why Micro-Borrowers Choose Us</h2>
            <p class="text-muted">Enterprise-grade security, rapid processing turnarounds, and client protection standards.</p>
        </div>

        <div class="row g-4">
            <div class="col-md-4">
                <x-ui.card class="h-100 tg-hover-lift text-center p-4">
                    <div class="bg-primary-subtle text-primary rounded-circle p-3 mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                        <i class="bi bi-shield-lock fs-3"></i>
                    </div>
                    <h5 class="fw-bold">Bank-Grade Security</h5>
                    <p class="text-muted small mb-0">Encrypted user sessions, role-based access control, and complete audit trail logging.</p>
                </x-ui.card>
            </div>

            <div class="col-md-4">
                <x-ui.card class="h-100 tg-hover-lift text-center p-4">
                    <div class="bg-success-subtle text-success rounded-circle p-3 mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                        <i class="bi bi-lightning-charge fs-3"></i>
                    </div>
                    <h5 class="fw-bold">Fast Loan Approval</h5>
                    <p class="text-muted small mb-0">Streamlined KYC verification allowing rapid decision turnarounds within 24 to 48 hours.</p>
                </x-ui.card>
            </div>

            <div class="col-md-4">
                <x-ui.card class="h-100 tg-hover-lift text-center p-4">
                    <div class="bg-info-subtle text-info rounded-circle p-3 mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                        <i class="bi bi-eye fs-3"></i>
                    </div>
                    <h5 class="fw-bold">100% Transparent Terms</h5>
                    <p class="text-muted small mb-0">Zero hidden fees, transparent interest rate calculations, and clear repayment schedules.</p>
                </x-ui.card>
            </div>

            <div class="col-md-4">
                <x-ui.card class="h-100 tg-hover-lift text-center p-4">
                    <div class="bg-warning-subtle text-warning rounded-circle p-3 mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                        <i class="bi bi-phone fs-3"></i>
                    </div>
                    <h5 class="fw-bold">Digital Services</h5>
                    <p class="text-muted small mb-0">Instant SMS transaction receipts, mobile collection logging, and online application tracking.</p>
                </x-ui.card>
            </div>

            <div class="col-md-4">
                <x-ui.card class="h-100 tg-hover-lift text-center p-4">
                    <div class="bg-danger-subtle text-danger rounded-circle p-3 mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                        <i class="bi bi-heart fs-3"></i>
                    </div>
                    <h5 class="fw-bold">Trusted Community Partner</h5>
                    <p class="text-muted small mb-0">Serving over 50,000 active micro-borrowers and self-help group members with high satisfaction.</p>
                </x-ui.card>
            </div>

            <div class="col-md-4">
                <x-ui.card class="h-100 tg-hover-lift text-center p-4">
                    <div class="bg-primary-subtle text-primary rounded-circle p-3 mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                        <i class="bi bi-diagram-3 fs-3"></i>
                    </div>
                    <h5 class="fw-bold">Extensive Branch Network</h5>
                    <p class="text-muted small mb-0">85+ nationwide branch offices providing physical cash counters and loan officer support.</p>
                </x-ui.card>
            </div>
        </div>
    </div>
</section>

<!-- 4. LOAN PRODUCTS -->
<section class="container-xl py-5">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end mb-5">
        <div>
            <span class="text-uppercase small fw-bold text-primary tracking-wider mb-2 d-block">Credit Facilities</span>
            <h2 class="fw-bold text-dark mb-0">Tailored Micro-Loan Products</h2>
        </div>
        <a href="{{ url('/products/loan') }}" class="btn btn-outline-primary rounded-pill px-3 py-1.5 small fw-bold mt-3 mt-md-0">
            View All Loan Schemes <i class="bi bi-arrow-right ms-1"></i>
        </a>
    </div>

    <div class="row g-4">
        <div class="col-md-4">
            <x-ui.card class="h-100 p-4 border-top border-4 border-primary tg-hover-lift">
                <div class="bg-primary-subtle text-primary rounded-circle p-3 mb-3 d-inline-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
                    <i class="bi bi-briefcase fs-3"></i>
                </div>
                <h5 class="fw-bold">Micro-Enterprise Loan</h5>
                <p class="text-muted small">Fast working capital for small shop owners and trade vendors needing inventory funds.</p>
                <ul class="list-unstyled small text-muted mb-4">
                    <li class="mb-1"><i class="bi bi-check text-success me-1"></i> Amount: $500 – $5,000</li>
                    <li class="mb-1"><i class="bi bi-check text-success me-1"></i> Rate: 12.5% P.A.</li>
                    <li class="mb-1"><i class="bi bi-check text-success me-1"></i> Weekly/Monthly Repayment</li>
                </ul>
                <a href="{{ url('/apply-loan') }}" class="btn btn-primary w-100 rounded-pill btn-sm fw-bold">Apply Now</a>
            </x-ui.card>
        </div>

        <div class="col-md-4">
            <x-ui.card class="h-100 p-4 border-top border-4 border-success tg-hover-lift">
                <div class="bg-success-subtle text-success rounded-circle p-3 mb-3 d-inline-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
                    <i class="bi bi-people fs-3"></i>
                </div>
                <h5 class="fw-bold">Group Solidarity Loan</h5>
                <p class="text-muted small">Community group lending model providing cross-guaranteed micro-loans for self-help groups.</p>
                <ul class="list-unstyled small text-muted mb-4">
                    <li class="mb-1"><i class="bi bi-check text-success me-1"></i> Amount: $200 – $2,000 / member</li>
                    <li class="mb-1"><i class="bi bi-check text-success me-1"></i> Rate: 11.0% P.A.</li>
                    <li class="mb-1"><i class="bi bi-check text-success me-1"></i> No Individual Collateral</li>
                </ul>
                <a href="{{ url('/apply-loan') }}" class="btn btn-success text-white w-100 rounded-pill btn-sm fw-bold">Apply Now</a>
            </x-ui.card>
        </div>

        <div class="col-md-4">
            <x-ui.card class="h-100 p-4 border-top border-4 border-info tg-hover-lift">
                <div class="bg-info-subtle text-info rounded-circle p-3 mb-3 d-inline-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
                    <i class="bi bi-building fs-3"></i>
                </div>
                <h5 class="fw-bold">SME Expansion Loan</h5>
                <p class="text-muted small">Substantial credit line for established businesses investing in machinery and facility upgrades.</p>
                <ul class="list-unstyled small text-muted mb-4">
                    <li class="mb-1"><i class="bi bi-check text-success me-1"></i> Amount: $5,000 – $25,000</li>
                    <li class="mb-1"><i class="bi bi-check text-success me-1"></i> Rate: 14.0% P.A.</li>
                    <li class="mb-1"><i class="bi bi-check text-success me-1"></i> Flexible Repayment Terms</li>
                </ul>
                <a href="{{ url('/apply-loan') }}" class="btn btn-info text-white w-100 rounded-pill btn-sm fw-bold">Apply Now</a>
            </x-ui.card>
        </div>
    </div>
</section>

<!-- 5. SAVINGS PRODUCTS -->
<section class="bg-light py-5 border-top border-bottom">
    <div class="container-xl">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end mb-5">
            <div>
                <span class="text-uppercase small fw-bold text-primary tracking-wider mb-2 d-block">Deposit Accounts</span>
                <h2 class="fw-bold text-dark mb-0">High-Yield Savings Schemes</h2>
            </div>
            <a href="{{ url('/products/savings') }}" class="btn btn-outline-primary rounded-pill px-3 py-1.5 small fw-bold mt-3 mt-md-0">
                View All Savings Schemes <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>

        <div class="row g-4">
            <div class="col-md-4">
                <x-ui.card class="h-100 p-4 tg-hover-lift">
                    <div class="bg-success-subtle text-success rounded-circle p-3 mb-3 d-inline-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
                        <i class="bi bi-wallet2 fs-3"></i>
                    </div>
                    <h5 class="fw-bold">Regular Savings Account</h5>
                    <p class="text-muted small">Everyday savings with monthly compound interest credits and zero account maintenance fees.</p>
                    <ul class="list-unstyled small text-muted mb-4">
                        <li class="mb-1"><i class="bi bi-check text-success me-1"></i> Interest: 4.5% P.A.</li>
                        <li class="mb-1"><i class="bi bi-check text-success me-1"></i> Opening Balance: $10</li>
                    </ul>
                    <a href="{{ url('/contact') }}" class="btn btn-outline-success w-100 rounded-pill btn-sm fw-bold">Open Account</a>
                </x-ui.card>
            </div>

            <div class="col-md-4">
                <x-ui.card class="h-100 p-4 tg-hover-lift">
                    <div class="bg-primary-subtle text-primary rounded-circle p-3 mb-3 d-inline-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
                        <i class="bi bi-bank fs-3"></i>
                    </div>
                    <h5 class="fw-bold">Fixed Term Deposit</h5>
                    <p class="text-muted small">Guaranteed high returns when locking funds for 3, 6, 12, or 24 month tenure terms.</p>
                    <ul class="list-unstyled small text-muted mb-4">
                        <li class="mb-1"><i class="bi bi-check text-success me-1"></i> Interest: Up to 8.5% P.A.</li>
                        <li class="mb-1"><i class="bi bi-check text-success me-1"></i> Guaranteed Maturity Payout</li>
                    </ul>
                    <a href="{{ url('/contact') }}" class="btn btn-outline-primary w-100 rounded-pill btn-sm fw-bold">Open Account</a>
                </x-ui.card>
            </div>

            <div class="col-md-4">
                <x-ui.card class="h-100 p-4 tg-hover-lift">
                    <div class="bg-info-subtle text-info rounded-circle p-3 mb-3 d-inline-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
                        <i class="bi bi-people fs-3"></i>
                    </div>
                    <h5 class="fw-bold">Group Savings Passbook</h5>
                    <p class="text-muted small">Joint passbook savings tailored for self-help groups and registered micro cooperatives.</p>
                    <ul class="list-unstyled small text-muted mb-4">
                        <li class="mb-1"><i class="bi bi-check text-success me-1"></i> Interest: 6.0% P.A.</li>
                        <li class="mb-1"><i class="bi bi-check text-success me-1"></i> Joint Member Signatures</li>
                    </ul>
                    <a href="{{ url('/contact') }}" class="btn btn-outline-info w-100 rounded-pill btn-sm fw-bold">Open Account</a>
                </x-ui.card>
            </div>
        </div>
    </div>
</section>

<!-- 6. DIGITAL SERVICES -->
<section class="container-xl py-5">
    <div class="text-center mx-auto mb-5" style="max-width: 700px;">
        <span class="text-uppercase small fw-bold text-primary tracking-wider mb-2 d-block">Modern Financial Tech</span>
        <h2 class="fw-bold text-dark">Digital Services & Field Solutions</h2>
        <p class="text-muted">Connecting field collections and counter operations directly with our central ERP database.</p>
    </div>

    <div class="row g-4">
        <div class="col-md-3">
            <x-ui.card class="h-100 p-4 text-center">
                <div class="bg-primary-subtle text-primary rounded-circle p-3 mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 56px; height: 56px;">
                    <i class="bi bi-globe fs-3"></i>
                </div>
                <h6 class="fw-bold mb-2">Internet Portal</h6>
                <p class="text-muted small mb-0">Online loan application submission and branch inquiry tracking.</p>
            </x-ui.card>
        </div>

        <div class="col-md-3">
            <x-ui.card class="h-100 p-4 text-center">
                <div class="bg-success-subtle text-success rounded-circle p-3 mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 56px; height: 56px;">
                    <i class="bi bi-phone fs-3"></i>
                </div>
                <h6 class="fw-bold mb-2">Mobile Banking</h6>
                <p class="text-muted small mb-0">Mobile balance inquiries and branch collection officer scheduling.</p>
            </x-ui.card>
        </div>

        <div class="col-md-3">
            <x-ui.card class="h-100 p-4 text-center">
                <div class="bg-info-subtle text-info rounded-circle p-3 mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 56px; height: 56px;">
                    <i class="bi bi-chat-text fs-3"></i>
                </div>
                <h6 class="fw-bold mb-2">SMS Alerts</h6>
                <p class="text-muted small mb-0">Instant SMS confirmation for every loan disbursement and repayment deposit.</p>
            </x-ui.card>
        </div>

        <div class="col-md-3">
            <x-ui.card class="h-100 p-4 text-center">
                <div class="bg-warning-subtle text-warning rounded-circle p-3 mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 56px; height: 56px;">
                    <i class="bi bi-receipt fs-3"></i>
                </div>
                <h6 class="fw-bold mb-2">Digital Collection</h6>
                <p class="text-muted small mb-0">Field officers issue live digital thermal receipts connected to branch vaults.</p>
            </x-ui.card>
        </div>
    </div>
</section>

<!-- 7. LOAN PROCESS WORKFLOW -->
<section class="bg-dark text-white py-5">
    <div class="container-xl py-3">
        <div class="text-center mx-auto mb-5" style="max-width: 700px;">
            <span class="text-uppercase small fw-bold text-primary tracking-wider mb-2 d-block">Simple 4-Step Journey</span>
            <h2 class="fw-bold text-white">How the Loan Process Works</h2>
            <p class="text-light opacity-75">From application submission to cash disbursement at your local branch counter.</p>
        </div>

        <div class="row g-4 text-center position-relative">
            <div class="col-md-3">
                <div class="p-3">
                    <div class="bg-primary text-white rounded-circle p-3 mx-auto mb-3 d-flex align-items-center justify-content-center fw-bold fs-4 shadow" style="width: 64px; height: 64px;">
                        1
                    </div>
                    <h5 class="fw-bold mb-2 text-white">Apply Online / Branch</h5>
                    <p class="text-light opacity-75 small">Submit initial loan application form online or visit a branch counter.</p>
                </div>
            </div>

            <div class="col-md-3">
                <div class="p-3">
                    <div class="bg-primary text-white rounded-circle p-3 mx-auto mb-3 d-flex align-items-center justify-content-center fw-bold fs-4 shadow" style="width: 64px; height: 64px;">
                        2
                    </div>
                    <h5 class="fw-bold mb-2 text-white">KYC & Verification</h5>
                    <p class="text-light opacity-75 small">Assigned loan officer verifies ID documents and conducts business assessment.</p>
                </div>
            </div>

            <div class="col-md-3">
                <div class="p-3">
                    <div class="bg-primary text-white rounded-circle p-3 mx-auto mb-3 d-flex align-items-center justify-content-center fw-bold fs-4 shadow" style="width: 64px; height: 64px;">
                        3
                    </div>
                    <h5 class="fw-bold mb-2 text-white">Approval Decision</h5>
                    <p class="text-light opacity-75 small">Branch Manager reviews committee approval and schedule parameters.</p>
                </div>
            </div>

            <div class="col-md-3">
                <div class="p-3">
                    <div class="bg-success text-white rounded-circle p-3 mx-auto mb-3 d-flex align-items-center justify-content-center fw-bold fs-4 shadow" style="width: 64px; height: 64px;">
                        4
                    </div>
                    <h5 class="fw-bold mb-2 text-white">Instant Disbursement</h5>
                    <p class="text-light opacity-75 small">Cashier disburses funds via branch vault counter or direct digital transfer.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 8. STATISTICS COUNTER -->
<section class="bg-white border-top border-bottom py-5 overflow-hidden">
    <div class="container-xl">
        <div class="row g-4 text-center">
            <div class="col-6 col-md-3">
                <h2 class="display-5 fw-bold text-primary mb-1">50,000+</h2>
                <span class="text-muted small fw-bold text-uppercase tracking-wider">Active Members</span>
            </div>
            <div class="col-6 col-md-3">
                <h2 class="display-5 fw-bold text-info mb-1">85+</h2>
                <span class="text-muted small fw-bold text-uppercase tracking-wider">Branch Offices</span>
            </div>
            <div class="col-6 col-md-3">
                <h2 class="display-5 fw-bold text-success mb-1">$120M+</h2>
                <span class="text-muted small fw-bold text-uppercase tracking-wider">Loans Disbursed</span>
            </div>
            <div class="col-6 col-md-3">
                <h2 class="display-5 fw-bold text-warning mb-1">99.2%</h2>
                <span class="text-muted small fw-bold text-uppercase tracking-wider">Recovery Rate</span>
            </div>
        </div>
    </div>
</section>

<!-- 9. BRANCH NETWORK & MAP -->
<section class="container-xl py-5">
    <div class="text-center mx-auto mb-5" style="max-width: 700px;">
        <span class="text-uppercase small fw-bold text-primary tracking-wider mb-2 d-block">Physical Presence</span>
        <h2 class="fw-bold text-dark">Nationwide Branch Network</h2>
        <p class="text-muted">Visit any of our 85+ branch offices for counter disbursements, deposits, and officer guidance.</p>
    </div>

    <div class="row g-4">
        <!-- Interactive Map Placeholder -->
        <div class="col-lg-12 mb-2">
            <x-ui.card class="p-5 bg-dark text-white text-center rounded-4 border-0">
                <i class="bi bi-map-fill fs-1 text-primary mb-2 opacity-75"></i>
                <h5 class="fw-bold text-white mb-1">Interactive Branch Network Map</h5>
                <p class="text-light opacity-75 small mb-0">[ Future CMS GIS Map Integration Placeholder ]</p>
            </x-ui.card>
        </div>

        <div class="col-md-4">
            <x-ui.card class="p-4 border-start border-4 border-primary h-100">
                <span class="badge bg-primary-subtle text-primary mb-2" style="width: fit-content;">Head Office</span>
                <h6 class="fw-bold mb-1">Central Head Office Branch</h6>
                <p class="text-muted small mb-2"><i class="bi bi-geo-alt text-primary me-1"></i> 100 Financial Avenue, Suite 500</p>
                <p class="text-muted small mb-0"><i class="bi bi-telephone text-primary me-1"></i> +1 (800) 555-0199</p>
            </x-ui.card>
        </div>

        <div class="col-md-4">
            <x-ui.card class="p-4 border-start border-4 border-success h-100">
                <span class="badge bg-success-subtle text-success mb-2" style="width: fit-content;">Metro Branch</span>
                <h6 class="fw-bold mb-1">Commercial Market Branch</h6>
                <p class="text-muted small mb-2"><i class="bi bi-geo-alt text-success me-1"></i> 45 Market Square Plaza</p>
                <p class="text-muted small mb-0"><i class="bi bi-telephone text-success me-1"></i> +1 (800) 555-0210</p>
            </x-ui.card>
        </div>

        <div class="col-md-4">
            <x-ui.card class="p-4 border-start border-4 border-info h-100">
                <span class="badge bg-info-subtle text-info mb-2" style="width: fit-content;">Regional Branch</span>
                <h6 class="fw-bold mb-1">Eastern Agricultural Branch</h6>
                <p class="text-muted small mb-2"><i class="bi bi-geo-alt text-info me-1"></i> 88 Rural Hub Highway</p>
                <p class="text-muted small mb-0"><i class="bi bi-telephone text-info me-1"></i> +1 (800) 555-0344</p>
            </x-ui.card>
        </div>
    </div>
</section>

<!-- 10. TESTIMONIALS -->
<section class="bg-light py-5 border-top border-bottom">
    <div class="container-xl">
        <div class="text-center mx-auto mb-5" style="max-width: 700px;">
            <span class="text-uppercase small fw-bold text-primary tracking-wider mb-2 d-block">Member Success Stories</span>
            <h2 class="fw-bold text-dark">What Our Borrowers Say</h2>
            <p class="text-muted">Real reviews from micro-entrepreneurs whose businesses grew with TG Microfinance.</p>
        </div>

        <div class="row g-4">
            <div class="col-md-4">
                <x-ui.card class="p-4 h-100">
                    <div class="text-warning mb-3">
                        <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
                    </div>
                    <p class="text-muted small fst-italic mb-3">"The Micro-Enterprise Loan enabled me to double my shop inventory before the festive season. The weekly collection officer visits saved me hours of travel!"</p>
                    <div class="d-flex align-items-center gap-2">
                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 36px; height: 36px;">S</div>
                        <div>
                            <h6 class="fw-bold mb-0 small">Sarah M.</h6>
                            <small class="text-muted" style="font-size: 0.75rem;">Retail Shop Owner</small>
                        </div>
                    </div>
                </x-ui.card>
            </div>

            <div class="col-md-4">
                <x-ui.card class="p-4 h-100">
                    <div class="text-warning mb-3">
                        <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
                    </div>
                    <p class="text-muted small fst-italic mb-3">"Our 10-member group received a solidarity loan with clear repayment terms. Transparent interest rates and zero hidden charges make them stand out."</p>
                    <div class="d-flex align-items-center gap-2">
                        <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 36px; height: 36px;">J</div>
                        <div>
                            <h6 class="fw-bold mb-0 small">Joseph K.</h6>
                            <small class="text-muted" style="font-size: 0.75rem;">Group Cooperative Leader</small>
                        </div>
                    </div>
                </x-ui.card>
            </div>

            <div class="col-md-4">
                <x-ui.card class="p-4 h-100">
                    <div class="text-warning mb-3">
                        <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
                    </div>
                    <p class="text-muted small fst-italic mb-3">"Opening a Fixed Term Deposit account was smooth. Getting monthly SMS credit alerts gives complete peace of mind for my family's savings."</p>
                    <div class="d-flex align-items-center gap-2">
                        <div class="bg-info text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 36px; height: 36px;">A</div>
                        <div>
                            <h6 class="fw-bold mb-0 small">Anita R.</h6>
                            <small class="text-muted" style="font-size: 0.75rem;">Savings Account Member</small>
                        </div>
                    </div>
                </x-ui.card>
            </div>
        </div>
    </div>
</section>

<!-- 11. LATEST NEWS (3 CARDS) -->
<section class="container-xl py-5">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end mb-5">
        <div>
            <span class="text-uppercase small fw-bold text-primary tracking-wider mb-2 d-block">Corporate News</span>
            <h2 class="fw-bold text-dark mb-0">Latest Announcements & Updates</h2>
        </div>
        <a href="{{ url('/resources/news') }}" class="btn btn-outline-primary rounded-pill px-3 py-1.5 small fw-bold mt-3 mt-md-0">
            View All Press Releases <i class="bi bi-arrow-right ms-1"></i>
        </a>
    </div>

    <div class="row g-4">
        <div class="col-md-4">
            <x-ui.card class="h-100 p-4 tg-hover-lift">
                <span class="badge bg-primary-subtle text-primary mb-2" style="width: fit-content;">Expansion</span>
                <h5 class="fw-bold mb-2">TG Microfinance Opens 5 New Regional Branch Offices</h5>
                <small class="text-muted d-block mb-3"><i class="bi bi-calendar me-1"></i> October 14, 2025</small>
                <p class="text-muted small mb-0">Expanding operational presence to serve micro-borrowers across eastern trade corridors.</p>
            </x-ui.card>
        </div>

        <div class="col-md-4">
            <x-ui.card class="h-100 p-4 tg-hover-lift">
                <span class="badge bg-success-subtle text-success mb-2" style="width: fit-content;">Financial Audit</span>
                <h5 class="fw-bold mb-2">Annual Financial Audit Confirms Outstanding Portfolio Health</h5>
                <small class="text-muted d-block mb-3"><i class="bi bi-calendar me-1"></i> September 28, 2025</small>
                <p class="text-muted small mb-0">Independent external audit results confirm a 99.2% repayment recovery rate across all branch ledgers.</p>
            </x-ui.card>
        </div>

        <div class="col-md-4">
            <x-ui.card class="h-100 p-4 tg-hover-lift">
                <span class="badge bg-info-subtle text-info mb-2" style="width: fit-content;">Community</span>
                <h5 class="fw-bold mb-2">Financial Literacy Workshops Reach Over 10,000 Members</h5>
                <small class="text-muted d-block mb-3"><i class="bi bi-calendar me-1"></i> September 15, 2025</small>
                <p class="text-muted small mb-0">Empowering self-help group members with cash flow budgeting and digital passbook tools.</p>
            </x-ui.card>
        </div>
    </div>
</section>

<!-- 12. FAQ ACCORDION -->
<section class="bg-light py-5 border-top border-bottom">
    <div class="container-xl">
        <div class="text-center mx-auto mb-5" style="max-width: 700px;">
            <span class="text-uppercase small fw-bold text-primary tracking-wider mb-2 d-block">Got Questions?</span>
            <h2 class="fw-bold text-dark">Frequently Asked Questions</h2>
            <p class="text-muted">Find quick answers to common questions about our loan products and savings accounts.</p>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-9">
                <div class="accordion accordion-flush bg-white rounded-4 p-4 shadow-sm border" id="homeFaqAccordion">
                    <div class="accordion-item border-bottom">
                        <h2 class="accordion-header" id="homeFaqOne">
                            <button class="accordion-button fw-bold text-dark shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#homeCollapseOne" aria-expanded="true">
                                What are the eligibility criteria for a Micro-Enterprise Loan?
                            </button>
                        </h2>
                        <div id="homeCollapseOne" class="accordion-collapse collapse show" data-bs-parent="#homeFaqAccordion">
                            <div class="accordion-body text-muted small">
                                Applicants must possess a valid National ID or Passport, proof of business address/stall location, and basic guarantor details.
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item border-bottom">
                        <h2 class="accordion-header" id="homeFaqTwo">
                            <button class="accordion-button collapsed fw-bold text-dark shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#homeCollapseTwo">
                                How fast are loan applications processed and disbursed?
                            </button>
                        </h2>
                        <div id="homeCollapseTwo" class="accordion-collapse collapse" data-bs-parent="#homeFaqAccordion">
                            <div class="accordion-body text-muted small">
                                Once submitted, digital KYC verification and loan officer field appraisals are completed within 24 to 48 hours.
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item border-bottom">
                        <h2 class="accordion-header" id="homeFaqThree">
                            <button class="accordion-button collapsed fw-bold text-dark shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#homeCollapseThree">
                                Are doorstep field collections supported?
                            </button>
                        </h2>
                        <div id="homeCollapseThree" class="accordion-collapse collapse" data-bs-parent="#homeFaqAccordion">
                            <div class="accordion-body text-muted small">
                                Yes. Authorized branch collection officers perform scheduled collection visits and issue instant digital receipts.
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <h2 class="accordion-header" id="homeFaqFour">
                            <button class="accordion-button collapsed fw-bold text-dark shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#homeCollapseFour">
                                How is interest calculated on fixed term savings deposits?
                            </button>
                        </h2>
                        <div id="homeCollapseFour" class="accordion-collapse collapse" data-bs-parent="#homeFaqAccordion">
                            <div class="accordion-body text-muted small">
                                Fixed deposit accounts accrue interest daily based on the selected tenure term (3, 6, 12, or 24 months) and pay out guaranteed maturity returns.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 13. FINAL CTA -->
<x-ui.cta
    title="Ready to Apply for Micro-Credit?"
    subtitle="Submit your initial loan request online in minutes, or visit your nearest branch counter today."
    primaryText="Apply for Loan Now"
    primaryUrl="/apply-loan"
    secondaryText="Contact Customer Support"
    secondaryUrl="/contact"
/>

@endsection
