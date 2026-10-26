@props([
    'active' => false,
    'href' => null,
])

@php
    $pressed = $active ? 'true' : 'false';
    $hasDynamicPressed = $attributes->has('x-bind:aria-pressed') || $attributes->has(':aria-pressed');
@endphp

@if($href)
    <a
        href="{{ $href }}"
        @unless($hasDynamicPressed) aria-pressed="{{ $pressed }}" @endunless
        {{ $attributes->merge(['class' => 'mp-segment']) }}
    >
        <span>{{ $slot }}</span>
    </a>
@else
    <button
        type="button"
        @unless($hasDynamicPressed) aria-pressed="{{ $pressed }}" @endunless
        {{ $attributes->merge(['class' => 'mp-segment']) }}
    >
        <span>{{ $slot }}</span>
    </button>
@endif
