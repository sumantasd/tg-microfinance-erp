@extends('layouts.public')

@section('title', 'Interest Rates Schedule - TG Microfinance ERP')
@section('meta_description', 'Official transparent interest rates schedule for loans and savings products at TG Microfinance.')

@section('content')
<x-ui.page-banner
    title="Official Interest Rates Schedule"
    subtitle="Transparent rates, zero hidden charges, and clear calculation methods regulated by central microfinance standards."
    badge="Rate Transparency"
    :breadcrumbs="['Products' => '/products/loan', 'Interest Rates' => '']"
/>

<section class="container-xl py-5">
    <x-ui.card class="p-4 mb-5">
        <h5 class="fw-bold mb-3 text-dark"><i class="bi bi-percent text-primary me-2"></i>Micro-Finance Rates & Method Matrix</h5>
        <x-ui.data-table :headers="['Product Name', 'Amount Range', 'Tenure Options', 'Interest Rate (P.A.)', 'Calculation Method', 'Processing Fee']">
            @forelse($rates as $item)
                <tr>
                    <td class="fw-bold text-dark">
                        {{ $item->product_name }}
                        <span class="badge bg-secondary-subtle text-secondary small d-block" style="width: fit-content;">{{ strtoupper($item->product_type) }}</span>
                    </td>
                    <td class="fw-semibold text-secondary">{{ $item->amount_range ?? 'N/A' }}</td>
                    <td class="text-muted">{{ $item->tenure_options ?? 'N/A' }}</td>
                    <td><strong class="text-primary">{{ $item->interest_rate }}</strong></td>
                    <td><span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill">{{ $item->interest_method }}</span></td>
                    <td><span class="badge bg-light text-dark border">{{ $item->processing_fee ?? '0.0%' }}</span></td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center py-4 text-muted">No interest rate entries currently listed.</td>
                </tr>
            @endforelse
        </x-ui.data-table>
    </x-ui.card>
</section>

<x-ui.cta />
@endsection
