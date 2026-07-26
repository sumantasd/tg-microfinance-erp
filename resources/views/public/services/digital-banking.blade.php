@extends('layouts.public')

@section('title', 'Digital Banking Services - TG Microfinance ERP')
@section('meta_description', 'Seamless mobile banking, digital receipts, and online account access with TG Microfinance.')

@section('content')
<x-ui.page-banner
    title="Digital Banking Services"
    subtitle="Access account balances, view repayment schedules, and receive instant transaction SMS alerts on your mobile device."
    badge="Digital Financial Technology"
    :breadcrumbs="['Services' => '/services/digital-banking', 'Digital Banking' => '']"
/>

<section class="container-xl py-5">
    <div class="row g-4">
        <div class="col-md-4">
            <x-ui.card class="h-100 p-4 text-center">
                <div class="bg-primary-subtle text-primary rounded-circle p-3 mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                    <i class="bi bi-phone fs-2"></i>
                </div>
                <h5 class="fw-bold">SMS Transaction Notifications</h5>
                <p class="text-muted small">Instant SMS receipts sent to your registered mobile phone for every deposit, loan repayment, and vault disbursement.</p>
            </x-ui.card>
        </div>
        <div class="col-md-4">
            <x-ui.card class="h-100 p-4 text-center">
                <div class="bg-success-subtle text-success rounded-circle p-3 mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                    <i class="bi bi-qr-code-scan fs-2"></i>
                </div>
                <h5 class="fw-bold">Field Digital Receipts</h5>
                <p class="text-muted small">Field officers issue digitally signed thermal receipts connected live to our central ERP database.</p>
            </x-ui.card>
        </div>
        <div class="col-md-4">
            <x-ui.card class="h-100 p-4 text-center">
                <div class="bg-info-subtle text-info rounded-circle p-3 mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                    <i class="bi bi-shield-lock fs-2"></i>
                </div>
                <h5 class="fw-bold">Biometric Customer KYC</h5>
                <p class="text-muted small">Enhanced identity security through national ID verification and biometric fingerprint validation.</p>
            </x-ui.card>
        </div>
    </div>
</section>

<x-ui.cta />
@endsection
