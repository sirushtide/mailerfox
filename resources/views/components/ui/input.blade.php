@props([
    'icon' => null,
    'name' => null,
    'type' => 'text',
    'value' => null,
    'placeholder' => null,
])

@php
    $hasTrailing = isset($trailing) && !$trailing->isEmpty();

    $classes = 'mp-input w-full'
        . ($icon ? ' mp-input--icon' : '')
        . ($hasTrailing ? ' mp-input--trailing' : '');
@endphp

<div class="mp-input-wrap">
    @if($icon)
        <span class="mp-input-wrap__icon" aria-hidden="true">
            <x-lucide :name="$icon" class="h-4 w-4" />
        </span>
    @endif

    <input
        type="{{ $type }}"
        @if($name) name="{{ $name }}" @endif
        @if(!is_null($value)) value="{{ $value }}" @endif
        placeholder="{{ $placeholder }}"
        {{ $attributes->merge(['class' => $classes]) }}
    >

    @if($hasTrailing)
        <span class="mp-input-wrap__trailing">{{ $trailing }}</span>
    @endif
</div>
