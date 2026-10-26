@props([
    'label' => null,
    'align' => 'left',
    'width' => 'w-52',
    'icon' => null,
])

<div class="relative inline-block text-left" x-data="{ open: false }" @keydown.escape.window="open = false" @click.outside="open = false">
    <x-ui.button
        type="button"
        aria-haspopup="menu"
        x-on:click="open = !open"
        x-bind:aria-expanded="open ? 'true' : 'false'"
        :icon="$icon"
        trailing="chevron-down"
    >{{ $label ?? $slot }}</x-ui.button>

    <div
        x-cloak
        x-show="open"
        x-transition.origin.top
        class="absolute z-40 mt-1.5 {{ $align === 'right' ? 'right-0' : 'left-0' }} {{ $width }} rounded-lg border py-1 shadow-lg"
        style="border-color: var(--mp-border); background: var(--mp-surface)"
    >
        @isset($menu)
            {{ $menu }}
        @endisset
    </div>
</div>
