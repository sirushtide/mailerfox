@props([
    'type' => 'button',
    'variant' => 'primary',
    'size' => 'md',
    'icon' => null,
    'iconPosition' => 'left',
    'href' => null,
    'pill' => false,
])

@php
    $variantClasses = [
        'primary' => 'mp-btn--primary',
        'secondary' => '',
        'success' => 'mp-btn--success',
        'danger' => 'mp-btn--danger',
        'warning' => 'mp-btn--warning',
        'info' => 'mp-btn--info',
        'outline' => 'mp-btn--outline',
        'ghost' => 'mp-btn--ghost',
        'table' => '',
        'table-info' => 'mp-btn--info-soft',
        'table-danger' => 'mp-btn--danger',
    ];

    $sizeClasses = [
        'xs' => 'mp-btn--xs',
        'sm' => 'mp-btn--sm',
        'md' => 'mp-btn--md',
        'lg' => 'mp-btn--lg',
        'xl' => 'mp-btn--lg',
        'action' => 'mp-btn--sm',
    ];

    $userClass = $attributes->get('class');
    $hasPaddingOverride = is_string($userClass) && preg_match('/(?:^|\s)!?p[trblxy]?-(?:\d+(?:\.\d+)?|px|\[[^\]]+\]|[a-zA-Z0-9_-]+)/', $userClass);

    $sizeClass = $hasPaddingOverride ? '' : ($sizeClasses[$size] ?? 'mp-btn--md');
    $pillClass = $pill ? 'rounded-full' : '';

    $classes = trim(
        'mp-btn '
        . ($variantClasses[$variant] ?? 'mp-btn--primary')
        . ' ' . $sizeClass
        . ' ' . $pillClass
        . ' ' . (is_string($userClass) ? $userClass : '')
    );

    $mergedAttributes = $attributes->except('class')->merge(['class' => $classes]);
@endphp

@if($href)
    <a href="{{ $href }}" {{ $mergedAttributes }}>
        @if($icon && $iconPosition === 'left')
            <span class="mp-btn__icon-wrap">{!! $icon !!}</span>
        @endif

        {{ $slot }}

        @if($icon && $iconPosition === 'right')
            <span class="mp-btn__icon-wrap">{!! $icon !!}</span>
        @endif
    </a>
@else
    <button type="{{ $type }}" {{ $mergedAttributes }}>
        @if($icon && $iconPosition === 'left')
            <span class="mp-btn__icon-wrap">{!! $icon !!}</span>
        @endif

        {{ $slot }}

        @if($icon && $iconPosition === 'right')
            <span class="mp-btn__icon-wrap">{!! $icon !!}</span>
        @endif
    </button>
@endif
