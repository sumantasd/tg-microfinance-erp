@props([
    'title' => 'Ready to Grow Your Enterprise?',
    'subtitle' => 'Apply for a tailored micro-loan online or visit any of our nationwide branch counters today.',
    'primaryText' => 'Apply Online Now',
    'primaryUrl' => '/apply-loan',
    'secondaryText' => 'Contact Head Office',
    'secondaryUrl' => '/contact'
])

<!-- Shared Call To Action Component -->
<section class="container-xl py-5">
    <div class="bg-dark text-white rounded-4 p-4 p-md-5 text-center position-relative overflow-hidden shadow-lg">
        <div class="position-relative z-1 py-3">
            <h2 class="fw-bold display-6 mb-3 text-white">{{ $title }}</h2>
            <p class="lead opacity-80 mx-auto mb-4" style="max-width: 620px;">{{ $subtitle }}</p>
            <div class="d-flex justify-content-center gap-3 flex-wrap">
                <a href="{{ url($primaryUrl) }}" class="btn btn-primary btn-lg rounded-pill px-4 py-2.5 fw-bold shadow">
                    {{ $primaryText }}
                </a>
                <a href="{{ url($secondaryUrl) }}" class="btn btn-outline-light btn-lg rounded-pill px-4 py-2.5 fw-semibold">
                    {{ $secondaryText }}
                </a>
            </div>
        </div>
    </div>
</section>
