@props([
    'id' => 'appModal',
    'title' => 'Modal Title',
    'size' => 'md', // sm, md, lg, xl
    'centered' => true
])

<div class="modal fade" id="{{ $id }}" tabindex="-1" aria-labelledby="{{ $id }}Label" aria-hidden="true">
    <div class="modal-dialog modal-{{ $size }} {{ $centered ? 'modal-dialog-centered' : '' }}">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header border-bottom py-3">
                <h5 class="modal-title fw-bold text-dark" id="{{ $id }}Label">{{ $title }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-4">
                {{ $slot }}
            </div>
            @if(isset($footer))
                <div class="modal-footer border-top py-3 bg-light">
                    {{ $footer }}
                </div>
            @endif
        </div>
    </div>
</div>
