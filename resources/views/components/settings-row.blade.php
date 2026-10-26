@props([
    'title' => null,
    'description' => null,
    'for' => null,
    'align' => 'center',
])

@php
    $alignClass = match($align) {
        'start' => 'sm:items-start',
        'end' => 'sm:items-end',
        default => 'sm:items-center',
    };
@endphp

<div {{ $attributes->merge(['class' => 'grid grid-cols-1 sm:grid-cols-[1fr_320px] gap-4 ' . $alignClass . ' px-5 py-4']) }}>
    <div class="min-w-0">
        @if($title)
            @if($for)
                <label for="{{ $for }}" class="block text-sm font-semibold text-gray-900 dark:text-white">{{ $title }}</label>
            @else
                <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ $title }}</div>
            @endif
        @endif
        @if($description)
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $description }}</p>
        @endif
    </div>
    <div class="min-w-0 sm:justify-self-end sm:w-[320px]">
        {{ $slot }}
    </div>
</div>
