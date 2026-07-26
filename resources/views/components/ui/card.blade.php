@props([
    'title' => null,
    'subtitle' => null,
    'class' => '',
    'headerClass' => 'bg-white border-0 py-3',
    'bodyClass' => '',
    'footerClass' => 'bg-white border-top-0 py-3'
])

<div {{ $attributes->merge(['class' => 'card border-0 shadow-sm rounded-3 ' . $class]) }}>
    @if($title || isset($header) || isset($actions))
        <div class="card-header d-flex justify-content-between align-items-center {{ $headerClass }}">
            <div>
                @if($title)
                    <h6 class="fw-bold mb-0 text-dark">{{ $title }}</h6>
                @endif
                @if($subtitle)
                    <small class="text-muted">{{ $subtitle }}</small>
                @endif
                @if(isset($header))
                    {{ $header }}
                @endif
            </div>
            @if(isset($actions))
                <div class="card-actions">
                    {{ $actions }}
                </div>
            @endif
        </div>
    @endif

    <div class="card-body {{ $bodyClass }}">
        {{ $slot }}
    </div>

    @if(isset($footer))
        <div class="card-footer {{ $footerClass }}">
            {{ $footer }}
        </div>
    @endif
</div>
