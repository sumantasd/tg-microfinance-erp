@props([
    'title' => 'Dashboard',
    'breadcrumbs' => []
])

<!-- Admin Header & Breadcrumb Strip -->
<div class="bg-white border-bottom py-3 px-4 shadow-sm">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
        <div>
            <nav aria-label="breadcrumb" class="mb-1">
                <ol class="breadcrumb mb-0 small text-uppercase fw-semibold" style="font-size: 0.725rem; letter-spacing: 0.5px;">
                    <li class="breadcrumb-item">
                        <a href="{{ url('/admin') }}" class="text-decoration-none text-muted">
                            <i class="bi bi-speedometer2 me-1"></i>ERP Admin
                        </a>
                    </li>
                    @foreach($breadcrumbs as $label => $url)
                        @if($loop->last || empty($url))
                            <li class="breadcrumb-item active text-primary" aria-current="page">{{ $label }}</li>
                        @else
                            <li class="breadcrumb-item">
                                <a href="{{ url($url) }}" class="text-decoration-none text-muted">{{ $label }}</a>
                            </li>
                        @endif
                    @endforeach
                </ol>
            </nav>
            <h4 class="fw-bold mb-0 text-dark">{{ $title }}</h4>
        </div>

        @if(isset($actions))
            <div class="d-flex align-items-center gap-2">
                {{ $actions }}
            </div>
        @endif
    </div>
</div>
