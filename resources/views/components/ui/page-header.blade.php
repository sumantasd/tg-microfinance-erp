@props([
    'title' => 'Page Title',
    'subtitle' => null,
    'badge' => null,
    'breadcrumbs' => []
])

<x-ui.page-banner :title="$title" :subtitle="$subtitle" :badge="$badge" :breadcrumbs="$breadcrumbs">
    @if(isset($actions))
        <x-slot name="actions">
            {{ $actions }}
        </x-slot>
    @endif
</x-ui.page-banner>
