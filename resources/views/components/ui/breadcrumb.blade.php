@props([
    'items' => []
])

<!-- Shared Public Breadcrumb Component -->
<div class="bg-white border-bottom py-2.5">
    <div class="container-xl">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 small fw-medium">
                <li class="breadcrumb-item">
                    <a href="{{ url('/') }}" class="text-decoration-none text-secondary">
                        <i class="bi bi-house me-1"></i>Home
                    </a>
                </li>
                @foreach($items as $label => $url)
                    @if($loop->last || empty($url))
                        <li class="breadcrumb-item active text-primary" aria-current="page">{{ $label }}</li>
                    @else
                        <li class="breadcrumb-item">
                            <a href="{{ $url }}" class="text-decoration-none text-secondary">{{ $label }}</a>
                        </li>
                    @endif
                @endforeach
            </ol>
        </nav>
    </div>
</div>
