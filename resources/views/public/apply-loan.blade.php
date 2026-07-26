@extends('layouts.public')

@section('title', 'Online Loan Application - TG Microfinance ERP')
@section('meta_description', 'Apply online for a micro-loan or business credit facility with TG Microfinance.')

@section('content')
<x-ui.page-banner
    title="Online Micro-Loan Application"
    subtitle="Submit your initial loan request online. An assigned branch loan officer will review your application within 24 hours."
    badge="Fast Credit Application"
    :breadcrumbs="['Apply Loan' => '']"
/>

<section class="container-xl py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <x-ui.card class="p-4 p-md-5">
                <h4 class="fw-bold mb-4">Loan Request Form</h4>
                <form>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">First Name</label>
                            <input type="text" class="form-control bg-light" placeholder="First Name">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Last Name</label>
                            <input type="text" class="form-control bg-light" placeholder="Last Name">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Mobile Phone Number</label>
                            <input type="text" class="form-control bg-light" placeholder="+1 (555) 000-0000">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Preferred Branch</label>
                            <select class="form-select bg-light">
                                <option>Central Head Office Branch</option>
                                <option>Commercial Market Branch</option>
                                <option>Eastern Agricultural Branch</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Requested Amount ($)</label>
                            <input type="number" class="form-control bg-light" placeholder="1000">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Loan Product Type</label>
                            <select class="form-select bg-light">
                                <option>Micro-Enterprise Loan</option>
                                <option>Group Solidarity Loan</option>
                                <option>SME Expansion Loan</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <button type="button" class="btn btn-primary btn-lg w-100 rounded-pill py-2.5 fw-bold shadow-sm">
                                <i class="bi bi-send-check me-1"></i> Submit Initial Loan Request
                            </button>
                        </div>
                    </div>
                </form>
            </x-ui.card>
        </div>
    </div>
</section>

<x-ui.cta />
@endsection
