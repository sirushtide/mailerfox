@props([
    'title' => null,
    'description' => null,
    'eyebrow' => null,
])

<header {{ $attributes->merge(['class' => 'mp-page-header']) }}>
    <div class="mp-page-header__main">
        @if($eyebrow)
            <p class="mp-eyebrow">{{ $eyebrow }}</p>
        @endif

        @if($title)
            <h1 class="mp-page-title">{!! __($title) !!}</h1>
        @endif

        @if(isset($meta))
            <div class="mt-2">{{ $meta }}</div>
        @endif

        @if($description)
            <p class="mp-page-desc">{{ $description }}</p>
        @endif

        {{ $slot }}
    </div>

    @if(isset($actions) && !$actions->isEmpty())
        <div class="mp-page-header__actions">
            {{ $actions }}
        </div>
    @endif
</header>
