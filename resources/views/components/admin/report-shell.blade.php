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

    $currentStart = $filters['start_date'] ?? null;
    $currentEnd = $filters['end_date'] ?? null;

    // Quick ranges resolve on the server so the active chip is exact; clicking
    // one only sets start/end dates, so it works for every report.
    $quickRanges = [
        ['today', __('Today'), __('Today')],
        ['last_7_days', __('7D'), __('Last 7 days')],
        ['last_30_days', __('30D'), __('Last 30 days')],
        ['this_month', __('Month'), __('This month')],
        ['this_year', __('Year'), __('This year')],
    ];
@endphp

<div class="admin-page finance-report-page">
    <x-admin.report-tabs :tabs="$tabs" />

    {{-- Period row (quick ranges, custom range, exports) plus, when the report
         has them, a filters row. The page title lives in the layout header. --}}
    @if ($hasFilters || $hasExports)
        <div class="report-controlbar">
            @if ($hasFilters)
                <form method="GET" action="{{ $action }}" class="report-controlbar__form" data-ajax-filter>
                    <div class="report-controlbar__period">
                        @if ($showRange)
                            <div class="report-quick" role="group" aria-label="{{ __('Quick ranges') }}">
                                @foreach ($quickRanges as [$preset, $short, $long])
                                    @php
                                        [$rangeFrom, $rangeTo] = \App\Enums\ReportPreset::from($preset)->range();
                                        $isActive = $currentStart === $rangeFrom->toDateString() && $currentEnd === $rangeTo->toDateString();
                                    @endphp
                                    <button type="button" class="report-quick__chip{{ $isActive ? ' is-active' : '' }}"
                                        data-report-range data-start="{{ $rangeFrom->toDateString() }}" data-end="{{ $rangeTo->toDateString() }}"
                                        title="{{ $long }}" aria-pressed="{{ $isActive ? 'true' : 'false' }}">{{ $short }}</button>
                                @endforeach
                            </div>

                            <div class="daterange-control report-range">
                                <i class="fa-regular fa-calendar"></i>
                                <input type="text" class="form-input" data-daterange data-daterange-submit data-daterange-opens="right"
                                    data-daterange-from="start_date" data-daterange-to="end_date"
                                    placeholder="{{ __('Custom range') }}" aria-label="{{ __('Date range') }}" readonly>
                                <i class="fa-solid fa-chevron-down report-range__caret" aria-hidden="true"></i>
                                <input type="hidden" name="start_date" value="{{ $currentStart }}">
                                <input type="hidden" name="end_date" value="{{ $currentEnd }}">
                            </div>
                        @endif

                        @if ($hasExports)
                            @include('components.admin.partials.report-exports')
                        @endif
                    </div>

                    @if ($hasSelects)
                        <div class="report-controlbar__filters">
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
                            <div class="report-controlbar__actions">
                                <a href="{{ $action }}" class="report-reset" data-ajax-link title="{{ __('Reset filters') }}">
                                    <i class="fa-solid fa-rotate-left"></i><span>{{ __('Reset') }}</span>
                                </a>
                                <button type="submit" class="report-apply">{{ __('Apply') }}</button>
                            </div>
                        </div>
                    @endif
                </form>
            @else
                <div class="report-controlbar__period">
                    @include('components.admin.partials.report-exports')
                </div>
            @endif
        </div>
    @endif

    {{ $slot }}
</div>

@once
    <script>
        // Quick-range chips: write the window into the form's dates and submit.
        document.addEventListener('click', (event) => {
            const chip = event.target.closest('[data-report-range]');
            const form = chip ? chip.closest('form') : null;
            if (!form) return;
            form.querySelector('input[name="start_date"]').value = chip.dataset.start;
            form.querySelector('input[name="end_date"]').value = chip.dataset.end;
            form.querySelectorAll('[data-report-range]').forEach((el) => {
                el.classList.toggle('is-active', el === chip);
                el.setAttribute('aria-pressed', el === chip ? 'true' : 'false');
            });
            form.requestSubmit();
        });
    </script>
@endonce
