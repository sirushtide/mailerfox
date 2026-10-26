@props([
    'title' => null,
    'subtitle' => null,
    'header' => null,
    'footer' => null,
    'padding' => true,
])

@php
    $paddingEnabled = !in_array($padding, [false, 0, '0', 'false'], true);
@endphp

<div {{ $attributes->merge(['class' => 'mp-card']) }}>
    @if($title || $subtitle || $header)
        <div class="mp-card__header">
            @if($header)
                {{ $header }}
            @else
                <div>
                    @if($title)
                        <h3 class="mp-card__title">{{ $title }}</h3>
                    @endif
                    @if($subtitle)
                        <p class="mp-card__desc">{{ $subtitle }}</p>
                    @endif
                </div>
            @endif
        </div>
    @endif

    <div class="{{ $paddingEnabled ? 'mp-card__body' : '' }}">
        {{ $slot }}
    </div>

    @if($footer)
        <div class="mp-card__footer">
            {{ $footer }}
        </div>
    @endif
</div>
