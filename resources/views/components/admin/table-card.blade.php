@props([
    'bulk' => false,
    'loader' => true,
    'ajax' => true,
])

<section
    {{ $attributes->merge(['class' => 'premium-card admin-table-card']) }}
    @if ($bulk) x-data="bulkSelect()" @endif
    @if ($ajax) data-ajax-table @endif
>
    {{-- AJAX tables use the in-region spinner, so the full-card reload overlay
         is only shown when AJAX is explicitly disabled. --}}
    @if ($loader && ! $ajax)
        <x-table-loader />
    @endif

    @isset($bulkBar)
        {{ $bulkBar }}
    @endisset

    @isset($toolbar)
        {{ $toolbar }}
    @endisset

    @if ($ajax)
        {{-- Only this region is swapped on AJAX search/per-page/pagination, so the
             toolbar (and the focused search input) is never re-rendered. --}}
        <div data-ajax-region>
            <div class="premium-table-wrap admin-table-card__scroll">
                {{ $slot }}
            </div>

            @isset($footer)
                {{ $footer }}
            @endisset
        </div>
    @else
        <div class="premium-table-wrap admin-table-card__scroll">
            {{ $slot }}
        </div>

        @isset($footer)
            {{ $footer }}
        @endisset
    @endif
</section>
