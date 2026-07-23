@props([
    'bulk' => false,
    'loader' => true,
])

<section
    {{ $attributes->merge(['class' => 'premium-card admin-table-card']) }}
    @if ($bulk) x-data="bulkSelect()" @endif
>
    @if ($loader)
        <x-table-loader />
    @endif

    @isset($bulkBar)
        {{ $bulkBar }}
    @endisset

    @isset($toolbar)
        {{ $toolbar }}
    @endisset

    <div class="premium-table-wrap admin-table-card__scroll">
        {{ $slot }}
    </div>

    @isset($footer)
        {{ $footer }}
    @endisset
</section>
