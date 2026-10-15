@props([
    'active' => null,
    'title' => null,
    'kicker' => 'Analytics',
    'filters' => [],
    'action',
    'exportUrl' => null,
    'pdfUrl' => null,
    'exportLabel' => 'CSV',
    'showStatus' => false,
    'showPayment' => false,
    'showRange' => true,
    'orderStatuses' => [],
    'paymentStatuses' => [],
    'tabs' => [],
])

@php
    $hasSelects = $showStatus || $showPayment || isset($controls);
    $hasFilters = $showRange || $hasSelects;
    $hasExports = $exportUrl || $pdfUrl;
@endphp

<div class="admin-page finance-report-page">
    {{-- A page without filters gets no empty bar: its exports sit beside the tabs. --}}
    <x-admin.report-tabs :tabs="$tabs">
        @if ($hasExports && ! $hasFilters)
            <x-slot:actions>
                @include('components.admin.partials.report-exports', ['exportsButton' => true])
            </x-slot:actions>
        @endif
    </x-admin.report-tabs>

    {{-- One quiet row: range + filters on the left, exports on the right.
         Field labels are visually hidden; placeholders carry the meaning. --}}
    @if ($hasFilters)
        <div class="report-controlbar">
            <form method="GET" action="{{ $action }}" class="report-controlbar__form" data-ajax-filter>
                @if ($showRange)
                    <div class="daterange-control report-range">
                        <i class="fa-regular fa-calendar"></i>
                        <input type="text" class="form-input" data-daterange data-daterange-submit data-daterange-opens="right"
                            data-daterange-from="start_date" data-daterange-to="end_date"
                            placeholder="{{ __('Select date range') }}" aria-label="{{ __('Date range') }}" readonly>
                        <i class="fa-solid fa-chevron-down report-range__caret" aria-hidden="true"></i>
                        <input type="hidden" name="start_date" value="{{ $filters['start_date'] ?? '' }}">
                        <input type="hidden" name="end_date" value="{{ $filters['end_date'] ?? '' }}">
                    </div>
                @endif

                @if ($showStatus)
                    <div class="report-controlbar__field">
                        <span>{{ __('Order status') }}</span>
                        <x-select name="status" size="sm" :value="$filters['status'] ?? null" placeholder="{{ __('All statuses') }}" :options="$orderStatuses" />
                    </div>
                @endif
                @if ($showPayment)
                    <div class="report-controlbar__field">
                        <span>{{ __('Payment status') }}</span>
                        <x-select name="payment_status" size="sm" :value="$filters['payment_status'] ?? null" placeholder="{{ __('All payments') }}" :options="$paymentStatuses" />
                    </div>
                @endif
                {{ $controls ?? '' }}

                @if ($hasSelects)
                    <div class="report-controlbar__actions">
                        <a href="{{ $action }}" class="report-reset" data-ajax-link title="{{ __('Reset filters') }}" aria-label="{{ __('Reset filters') }}">
                            <i class="fa-solid fa-rotate-left"></i>
                        </a>
                        <button type="submit" class="report-apply">{{ __('Apply') }}</button>
                    </div>
                @endif

                @if ($hasExports)
                    @include('components.admin.partials.report-exports')
                @endif
            </form>
        </div>
    @endif

    {{ $slot }}
</div>
