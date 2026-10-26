@props([
    'name' => null,
    'checked' => false,
    'value' => '1',
    'label' => null,
    'size' => 'md',
    'disabled' => false,
])

<label class="mp-switch {{ $size === 'sm' ? 'mp-switch--sm' : '' }} {{ $disabled ? 'mp-switch--disabled' : '' }}">
    <input
        type="checkbox"
        role="switch"
        @if($name) name="{{ $name }}" @endif
        value="{{ $value }}"
        @checked($checked)
        @disabled($disabled)
        {{ $attributes->merge(['class' => 'mp-switch__input']) }}
    >
    <span class="mp-switch__control" aria-hidden="true">
        <span class="mp-switch__thumb"></span>
    </span>
    @if($label)
        <span class="mp-switch__label">{{ $label }}</span>
    @elseif(!$attributes->has('aria-label') && !$attributes->has('aria-labelledby'))
        <span class="sr-only">{{ $name ?: __('Toggle') }}</span>
    @endif
</label>
