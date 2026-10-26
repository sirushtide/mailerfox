@props([
    'label' => null,
])

<div
    role="group"
    @if($label) aria-label="{{ $label }}" @endif
    {{ $attributes->merge(['class' => 'mp-segmented w-fit']) }}
>
    {{ $slot }}
</div>
