@props([
    'title',
    'subtitle' => null,
])

<div {{ $attributes->merge(['class' => 'ut-page-header mb-4']) }}>
    <div>
        <p class="header-kicker mb-1">Admin Console</p>
        <h1 class="ut-page-header__title">{{ $title }}</h1>
        @if ($subtitle)
            <p class="ut-page-header__subtitle">{{ $subtitle }}</p>
        @endif
    </div>

    @if ($slot->isNotEmpty())
        <div class="ut-page-header__actions">
            {{ $slot }}
        </div>
    @endif
</div>
