<!-- Corporate Public Website Footer -->
<footer class="public-footer overflow-hidden">
    <div class="container-xl">
        <div class="row g-4 g-lg-5">
            <!-- Company Info Column -->
            <div class="col-lg-4 col-md-6">
                <div class="d-flex align-items-center gap-2 mb-3">
                    @if(isset($footer) && $footer->footer_logo_url)
                        <img src="{{ $footer->footer_logo_url }}" alt="{{ $settings->company_name ?? 'Logo' }}" style="max-height: 42px; object-fit: contain;" class="rounded bg-white p-1">
                    @elseif(isset($settings) && $settings->logo_url)
                        <img src="{{ $settings->logo_url }}" alt="{{ $settings->company_name ?? 'Logo' }}" style="max-height: 42px; object-fit: contain;" class="rounded bg-white p-1">
                    @else
                        <div class="bg-primary text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                            <i class="bi bi-bank2 fs-6"></i>
                        </div>
                        <h5 class="mb-0 text-white fw-bold">{{ $settings->company_name ?? 'TG Microfinance' }}</h5>
                    @endif
                </div>
                <p class="small text-white opacity-90 mb-3" style="max-width: 320px;">
                    {{ $footer->about_text ?? 'Empowering individuals, micro-entrepreneurs, and small businesses with accessible credit solutions, high-yield savings schemes, and financial literacy.' }}
                </p>

                @php
                    $socials = (isset($footer) && is_array($footer->social_links)) ? array_filter($footer->social_links) : (isset($settings->social_links) ? array_filter($settings->social_links) : []);
                @endphp
                <div class="d-flex gap-2">
                    @if(isset($socials['facebook']))
                        <a href="{{ $socials['facebook'] }}" target="_blank" rel="noopener" class="btn btn-outline-light btn-sm rounded-circle"><i class="bi bi-facebook"></i></a>
                    @endif
                    @if(isset($socials['twitter']))
                        <a href="{{ $socials['twitter'] }}" target="_blank" rel="noopener" class="btn btn-outline-light btn-sm rounded-circle"><i class="bi bi-twitter-x"></i></a>
                    @endif
                    @if(isset($socials['linkedin']))
                        <a href="{{ $socials['linkedin'] }}" target="_blank" rel="noopener" class="btn btn-outline-light btn-sm rounded-circle"><i class="bi bi-linkedin"></i></a>
                    @endif
                    @if(isset($socials['instagram']))
                        <a href="{{ $socials['instagram'] }}" target="_blank" rel="noopener" class="btn btn-outline-light btn-sm rounded-circle"><i class="bi bi-instagram"></i></a>
                    @endif
                    @if(isset($socials['youtube']))
                        <a href="{{ $socials['youtube'] }}" target="_blank" rel="noopener" class="btn btn-outline-light btn-sm rounded-circle"><i class="bi bi-youtube"></i></a>
                    @endif
                </div>
            </div>

            <!-- Quick Links -->
            <div class="col-lg-2 col-md-6">
                <h6 class="mb-3 text-white fw-bold">Quick Links</h6>
                <ul class="list-unstyled small mb-0">
                    <li class="mb-2"><a href="{{ url('/about') }}"><i class="bi bi-chevron-right text-primary me-1"></i> About Us</a></li>
                    <li class="mb-2"><a href="{{ url('/products/loan') }}"><i class="bi bi-chevron-right text-primary me-1"></i> Loan Products</a></li>
                    <li class="mb-2"><a href="{{ url('/products/savings') }}"><i class="bi bi-chevron-right text-primary me-1"></i> Savings Schemes</a></li>
                    <li class="mb-2"><a href="{{ url('/products/interest-rates') }}"><i class="bi bi-chevron-right text-primary me-1"></i> Interest Rates</a></li>
                    <li class="mb-2"><a href="{{ url('/apply-loan') }}"><i class="bi bi-chevron-right text-primary me-1"></i> Apply Online</a></li>
                </ul>
            </div>

            <!-- Customer Support -->
            <div class="col-lg-3 col-md-6">
                <h6 class="mb-3 text-white fw-bold">Customer Support</h6>
                <ul class="list-unstyled small mb-0">
                    <li class="mb-2"><a href="{{ url('/branches') }}"><i class="bi bi-geo-alt text-primary me-1"></i> Branch Locator</a></li>
                    <li class="mb-2"><a href="{{ url('/resources/downloads') }}"><i class="bi bi-file-earmark-arrow-down text-primary me-1"></i> Forms & Downloads</a></li>
                    <li class="mb-2"><a href="{{ url('/resources/faq') }}"><i class="bi bi-question-circle text-primary me-1"></i> Frequently Asked Questions</a></li>
                    <li class="mb-2"><a href="{{ url('/resources/career') }}"><i class="bi bi-briefcase text-primary me-1"></i> Career Opportunities</a></li>
                    <li class="mb-2"><a href="{{ url('/contact') }}"><i class="bi bi-envelope text-primary me-1"></i> Contact Head Office</a></li>
                </ul>
            </div>

            <!-- Head Office Info -->
            <div class="col-lg-3 col-md-6">
                <h6 class="mb-3 text-white fw-bold">Head Office</h6>
                <p class="small text-white opacity-90 mb-2"><i class="bi bi-building text-primary me-2"></i> {{ $settings->company_name ?? 'TG Microfinance Headquarters' }}</p>
                <p class="small text-white opacity-90 mb-2"><i class="bi bi-geo-alt text-primary me-2"></i> {{ $footer->address ?? ($settings->address ?? '100 Financial Avenue, Suite 500') }}</p>
                <p class="small text-white opacity-90 mb-2"><i class="bi bi-telephone text-primary me-2"></i> {{ $footer->phone ?? ($settings->phone ?? '+1 (800) 555-0199') }}</p>
                <p class="small text-white opacity-90 mb-2"><i class="bi bi-envelope text-primary me-2"></i> {{ $footer->email ?? ($settings->email ?? 'info@tgmicrofinance.org') }}</p>
            </div>
        </div>

        <hr class="border-secondary my-4">

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center small text-white opacity-90 pb-3">
            <p class="mb-0">{{ $footer->copyright_text ?? ($settings->footer_text ?? ('© ' . date('Y') . ' Astha Welfare Society. Developed By Tech Googly')) }}</p>
            <div class="mt-2 mt-md-0">
                <a href="#" class="me-3 text-white text-decoration-none opacity-75">Privacy Policy</a>
                <a href="#" class="me-3 text-white text-decoration-none opacity-75">Terms of Service</a>
                <a href="#" class="text-white text-decoration-none opacity-75">Regulatory Compliance</a>
            </div>
        </div>
    </div>
</footer>
