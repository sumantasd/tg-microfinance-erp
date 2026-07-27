@extends('layouts.public')

@section('title', 'Frequently Asked Questions - ' . ($settings->company_name ?? 'TG Microfinance ERP'))
@section('meta_description', 'Find answers to common questions regarding loan eligibility, repayment schedules, and savings interest rates.')

@section('content')
<x-ui.page-banner
    title="Frequently Asked Questions (FAQ)"
    subtitle="Find clear answers to common questions about loan application requirements, interest rates, and branch deposits."
    badge="Help Center"
    :breadcrumbs="['Resources' => '/resources/faq', 'FAQ' => '']"
/>

<section class="container-xl py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            @if(isset($faqs) && $faqs->count() > 0)
                <div class="accordion accordion-flush bg-white rounded-4 p-4 shadow-sm border" id="faqPageAccordion">
                    @foreach($faqs as $index => $faq)
                        <div class="accordion-item {{ $index < $faqs->count() - 1 ? 'border-bottom' : '' }}">
                            <h2 class="accordion-header" id="faqHeading{{ $faq->id }}">
                                <button class="accordion-button {{ $index === 0 ? '' : 'collapsed' }} fw-bold text-dark shadow-none py-3" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFaq{{ $faq->id }}" aria-expanded="{{ $index === 0 ? 'true' : 'false' }}">
                                    <i class="bi bi-question-circle text-primary me-2 fs-5"></i> {{ $faq->question }}
                                </button>
                            </h2>
                            <div id="collapseFaq{{ $faq->id }}" class="accordion-collapse collapse {{ $index === 0 ? 'show' : '' }}" data-bs-parent="#faqPageAccordion">
                                <div class="accordion-body text-secondary lh-relaxed pb-4 ps-4">
                                    {!! nl2br(e($faq->answer)) !!}
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-5">
                    <div class="bg-light rounded-4 p-5 max-w-md mx-auto border">
                        <i class="bi bi-question-circle fs-1 text-muted opacity-50 mb-3 d-block"></i>
                        <h5 class="fw-bold text-dark">No FAQs Available</h5>
                        <p class="text-muted small mb-0">Check back soon for answers to common questions.</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</section>

<x-ui.cta />
@endsection
