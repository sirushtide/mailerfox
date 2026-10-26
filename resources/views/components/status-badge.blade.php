@props([
    'tone' => 'neutral',
    'dot' => false,
])

@php
    $tones = ['neutral', 'success', 'warning', 'danger', 'info'];
    $tone = in_array($tone, $tones, true) ? $tone : 'neutral';
@endphp

<span {{ $attributes->merge(['class' => "mp-badge mp-badge--{$tone}"]) }}>
    @if($dot)
        <span class="mp-badge__dot" aria-hidden="true"></span>
    @endif
    {{ $slot }}
</span>
