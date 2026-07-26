@props([
    'title' => 'Page Title',
    'subtitle' => null,
    'badge' => null,
    'breadcrumbs' => []
])

<!-- Premium Corporate Page Banner Component -->
<section class="bg-dark text-white py-4 py-md-5 position-relative overflow-hidden border-bottom border-secondary">
    <div class="container-xl position-relative z-1">
        <!-- Integrated Breadcrumb Trail -->
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb mb-0 small fw-medium" style="letter-spacing: 0.3px;">
                <li class="breadcrumb-item">
                    <a href="{{ url('/') }}" class="text-decoration-none text-light opacity-75">
                        <i class="bi bi-house me-1"></i>Home
                    </a>
                </li>
                @foreach($breadcrumbs as $label => $url)
                    @if($loop->last || empty($url))
                        <li class="breadcrumb-item active text-primary fw-bold" aria-current="page">{{ $label }}</li>
                    @else
                        <li class="breadcrumb-item">
                            <a href="{{ url($url) }}" class="text-decoration-none text-light opacity-75">{{ $label }}</a>
                        </li>
                    @endif
                @endforeach
            </ol>
        </nav>

        <!-- Page Header Content -->
        <div class="row align-items-center g-3">
            <div class="col-lg-8">
                @if($badge)
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle fs-6 px-3 py-1.5 rounded-pill fw-semibold mb-2.5 d-inline-flex align-items-center gap-1">
                        <i class="bi bi-shield-check"></i> {{ $badge }}
                    </span>
                @endif
                <h1 class="display-5 fw-bold text-white mb-2">{{ $title }}</h1>
                @if($subtitle)
                    <p class="lead opacity-85 text-light mb-0" style="max-width: 680px;">{{ $subtitle }}</p>
                @endif
            </div>
            @if(isset($actions))
                <div class="col-lg-4 text-lg-end">
                    {{ $actions }}
                </div>
            @endif
        </div>
    </div>
</section>
