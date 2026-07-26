@extends('layouts.public')

@section('title', 'Interest Rates Schedule - TG Microfinance ERP')
@section('meta_description', 'Official transparent interest rates schedule for loans and savings products at TG Microfinance.')

@section('content')
<x-ui.page-banner
    title="Official Interest Rates Schedule"
    subtitle="Transparent rates, zero hidden charges, and clear calculations regulated by central microfinance standards."
    badge="Rate Transparency"
    :breadcrumbs="['Products' => '/products/loan', 'Interest Rates' => '']"
/>

<section class="container-xl py-5">
    <x-ui.card class="p-4 mb-5">
        <h5 class="fw-bold mb-3 text-dark"><i class="bi bi-percent text-primary me-2"></i>Loan Products Rate Matrix</h5>
        <x-ui.data-table :headers="['Product Category', 'Min-Max Amount', 'Tenure Options', 'Interest Rate (P.A.)', 'Processing Fee']">
            <tr>
                <td class="fw-semibold">Micro-Enterprise Loan</td>
                <td>$500 – $5,000</td>
                <td>6 – 18 Months</td>
                <td><span class="badge bg-primary-subtle text-primary">12.5% Flat / Reducing</span></td>
                <td>1.0%</td>
            </tr>
            <tr>
                <td class="fw-semibold">Group Solidarity Loan</td>
                <td>$200 – $2,000</td>
                <td>12 Months</td>
                <td><span class="badge bg-success-subtle text-success">11.0% Reducing</span></td>
                <td>0.5%</td>
            </tr>
            <tr>
                <td class="fw-semibold">SME Expansion Loan</td>
                <td>$5,000 – $25,000</td>
                <td>12 – 36 Months</td>
                <td><span class="badge bg-info-subtle text-info">14.0% Reducing</span></td>
                <td>1.5%</td>
            </tr>
        </x-ui.data-table>
    </x-ui.card>
</section>

<x-ui.cta />
@endsection
