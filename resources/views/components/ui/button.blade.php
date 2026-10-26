@props([
    'variant' => 'neutral',
    'size' => 'md',
    'href' => null,
    'type' => 'button',
    'icon' => null,
    'trailing' => null,
    'iconOnly' => false,
])

@php
    $variantClass = match ($variant) {
        'primary' => 'mp-btn--primary',
        'ghost' => 'mp-btn--ghost',
        'danger' => 'mp-btn--danger',
        default => '',
    };

    $sizeClass = match ($size) {
        'xs' => 'mp-btn--xs',
        'sm' => 'mp-btn--sm',
        'lg' => 'mp-btn--lg',
        default => 'mp-btn--md',
    };

    $classes = trim('mp-btn ' . $variantClass . ' ' . $sizeClass . ($iconOnly ? ' mp-btn--icon' : ''));
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if($icon)<x-lucide :name="$icon" class="mp-btn__icon" />@endif
        @if(!$iconOnly){{ $slot }}@endif
        @if($trailing)<x-lucide :name="$trailing" class="mp-btn__icon" />@endif
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if($icon)<x-lucide :name="$icon" class="mp-btn__icon" />@endif
        @if(!$iconOnly){{ $slot }}@endif
        @if($trailing)<x-lucide :name="$trailing" class="mp-btn__icon" />@endif
    </button>
@endif
