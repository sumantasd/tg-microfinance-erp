@extends('layouts.public')

@section('title', 'Financial Advisory Services - TG Microfinance ERP')
@section('meta_description', 'Free business advisory, cash flow budgeting, and financial literacy workshops for micro-entrepreneurs.')

@section('content')
<x-ui.page-banner
    title="Financial Advisory & Literacy"
    subtitle="Empowering micro-entrepreneurs with practical cash flow budgeting, debt management, and enterprise planning guidance."
    badge="Capacity Building"
    :breadcrumbs="['Services' => '/services/digital-banking', 'Financial Advisory' => '']"
/>

<section class="container-xl py-5">
    <div class="row g-4">
        <div class="col-md-4">
            <x-ui.card class="h-100 p-4">
                <div class="bg-primary-subtle text-primary rounded-circle p-3 mb-3 d-inline-flex align-items-center justify-content-center" style="width: 54px; height: 54px;">
                    <i class="bi bi-book fs-3"></i>
                </div>
                <h5 class="fw-bold">Financial Literacy Workshops</h5>
                <p class="text-muted small">Free monthly group workshops covering basic bookkeeping, interest calculation, and savings discipline.</p>
            </x-ui.card>
        </div>

        <div class="col-md-4">
            <x-ui.card class="h-100 p-4">
                <div class="bg-success-subtle text-success rounded-circle p-3 mb-3 d-inline-flex align-items-center justify-content-center" style="width: 54px; height: 54px;">
                    <i class="bi bi-graph-up-arrow fs-3"></i>
                </div>
                <h5 class="fw-bold">Business Cash Flow Coaching</h5>
                <p class="text-muted small">One-on-one guidance with loan officers to evaluate business cash flow and optimize borrowing limits.</p>
            </x-ui.card>
        </div>

        <div class="col-md-4">
            <x-ui.card class="h-100 p-4">
                <div class="bg-info-subtle text-info rounded-circle p-3 mb-3 d-inline-flex align-items-center justify-content-center" style="width: 54px; height: 54px;">
                    <i class="bi bi-shield-check fs-3"></i>
                </div>
                <h5 class="fw-bold">Debt Restructuring Counseling</h5>
                <p class="text-muted small">Supportive counseling for members experiencing economic shocks to restructure repayment schedules ethically.</p>
            </x-ui.card>
        </div>
    </div>
</section>

<x-ui.cta />
@endsection
