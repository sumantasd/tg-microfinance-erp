@props([
    'headers' => [],
    'emptyMessage' => 'No records found.'
])

<div class="table-responsive rounded-3 border bg-white">
    <table {{ $attributes->merge(['class' => 'table table-hover align-middle mb-0']) }}>
        @if(!empty($headers))
            <thead class="table-light text-uppercase small text-muted" style="font-size: 0.725rem; letter-spacing: 0.5px;">
                <tr>
                    @foreach($headers as $header)
                        <th scope="col" class="py-3 px-3">{{ $header }}</th>
                    @endforeach
                </tr>
            </thead>
        @endif
        <tbody>
            @if($slot->isEmpty())
                <tr>
                    <td colspan="{{ count($headers) ?: 1 }}" class="text-center py-5 text-muted">
                        <i class="bi bi-inbox fs-1 d-block mb-2 opacity-50"></i>
                        <p class="mb-0 small fw-medium">{{ $emptyMessage }}</p>
                    </td>
                </tr>
            @else
                {{ $slot }}
            @endif
        </tbody>
    </table>
</div>

@if(isset($pagination))
    <div class="mt-3 d-flex justify-content-between align-items-center">
        {{ $pagination }}
    </div>
@endif
