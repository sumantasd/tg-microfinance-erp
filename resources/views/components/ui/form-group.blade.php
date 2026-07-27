@props([
    'label' => null,
    'name' => null,
    'required' => false,
    'help' => null,
    'error' => null,
    'class' => 'mb-3'
])

<div class="{{ $class }}">
    @if($label)
        <label @if($name) for="{{ $name }}" @endif class="form-label small fw-bold text-secondary mb-1">
            {{ $label }}
            @if($required) <span class="text-danger">*</span> @endif
        </label>
    @endif

    {{ $slot }}

    @if($help)
        <div class="form-text small text-muted mt-1">{{ $help }}</div>
    @endif

    @if($name && $errors->has($name))
        <div class="invalid-feedback d-block small mt-1">
            <i class="bi bi-exclamation-circle me-1"></i>{{ $errors->first($name) }}
        </div>
    @elseif($error)
        <div class="invalid-feedback d-block small mt-1">
            <i class="bi bi-exclamation-circle me-1"></i>{{ $error }}
        </div>
    @endif
</div>
