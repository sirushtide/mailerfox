@props([
    'name',
    'checked' => false,
    'disabled' => false,
    'includeHidden' => true,
])

<div class="flex items-center justify-end">
    @if($includeHidden && !$disabled)
        <input type="hidden" name="{{ $name }}" value="0">
    @endif
    <label class="mp-switch {{ $disabled ? 'mp-switch--disabled' : '' }}">
        <input
            type="checkbox"
            role="switch"
            @if(!$disabled) name="{{ $name }}" @endif
            value="1"
            @checked($checked)
            @disabled($disabled)
            class="mp-switch__input"
        >
        <span class="mp-switch__control" aria-hidden="true">
            <span class="mp-switch__thumb"></span>
        </span>
    </label>
</div>
