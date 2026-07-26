@extends('layouts.public')

@section('title', 'Frequently Asked Questions - TG Microfinance ERP')
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
            <div class="accordion accordion-flush bg-white rounded-3 p-4 shadow-sm border" id="faqPageAccordion">
                <div class="accordion-item border-bottom">
                    <h2 class="accordion-header" id="faqOne">
                        <button class="accordion-button fw-bold text-dark shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true">
                            What documents are required to apply for a Micro-Enterprise Loan?
                        </button>
                    </h2>
                    <div id="collapseOne" class="accordion-collapse collapse show" data-bs-parent="#faqPageAccordion">
                        <div class="accordion-body text-muted small">
                            You will need a valid government-issued National Identity Card or Passport, proof of business address/stall location, and basic guarantor details.
                        </div>
                    </div>
                </div>

                <div class="accordion-item border-bottom">
                    <h2 class="accordion-header" id="faqTwo">
                        <button class="accordion-button collapsed fw-bold text-dark shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo">
                            How fast are micro-loan applications approved?
                        </button>
                    </h2>
                    <div id="collapseTwo" class="accordion-collapse collapse" data-bs-parent="#faqPageAccordion">
                        <div class="accordion-body text-muted small">
                            Standard micro-loans undergo digital KYC and field officer verification within 24 to 48 hours following document submission.
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header" id="faqThree">
                        <button class="accordion-button collapsed fw-bold text-dark shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree">
                            Can field collection officers collect deposits directly from my business stall?
                        </button>
                    </h2>
                    <div id="collapseThree" class="accordion-collapse collapse" data-bs-parent="#faqPageAccordion">
                        <div class="accordion-body text-muted small">
                            Yes. Authorized branch collection officers carry mobile ERP devices and issue instant electronic receipts for all field repayments and deposits.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<x-ui.cta />
@endsection
