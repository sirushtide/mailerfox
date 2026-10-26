@props([])

<div {{ $attributes->merge(['class' => 'rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700']) }}>
    {{ $slot }}
</div>
