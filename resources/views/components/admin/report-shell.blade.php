@props([
    'active',
    'title',
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
])

<div class="admin-page finance-report-page">
    <div class="page-section-header finance-report-heading">
        <div>
            <p class="section-kicker">{{ $kicker }}</p>
            <h3>{{ $title }}</h3>
        </div>
        @if ($exportUrl || $pdfUrl)
            <div class="finance-report-export-group">
                @if ($pdfUrl)
                    <a href="{{ $pdfUrl }}" class="ghost-button ghost-button--panel">
                        <i class="fa-solid fa-file-pdf"></i><span>{{ __('PDF') }}</span>
                    </a>
                @endif
                @if ($exportUrl)
                    <a href="{{ $exportUrl }}" class="premium-button premium-button--dark">
                        <i class="fa-solid fa-file-csv"></i><span>{{ $exportLabel }}</span>
                    </a>
                @endif
            </div>
        @endif
    </div>

    @if ($showRange || $showStatus || $showPayment)
        <form method="GET" action="{{ $action }}" class="finance-report-filter">
            @if ($showRange)
                <label class="finance-report-filter__range">
                    <span>{{ __('Date range') }}</span>
                    <div class="daterange-control finance-daterange-control">
                        <i class="fa-solid fa-calendar-days"></i>
                        <input type="text" class="form-input" data-daterange data-daterange-from="start_date" data-daterange-to="end_date" placeholder="{{ __('Select report range') }}" readonly>
                        <input type="hidden" name="start_date" value="{{ $filters['start_date'] ?? '' }}">
                        <input type="hidden" name="end_date" value="{{ $filters['end_date'] ?? '' }}">
                    </div>
                </label>
            @endif
            @if ($showStatus)
                <div class="finance-report-filter__field">
                    <span>{{ __('Order status') }}</span>
                    <x-select name="status" size="sm" :value="$filters['status'] ?? null" placeholder="{{ __('All statuses') }}" :options="$orderStatuses" />
                </div>
            @endif
            @if ($showPayment)
                <div class="finance-report-filter__field">
                    <span>{{ __('Payment status') }}</span>
                    <x-select name="payment_status" size="sm" :value="$filters['payment_status'] ?? null" placeholder="{{ __('All payments') }}" :options="$paymentStatuses" />
                </div>
            @endif
            <div class="finance-report-filter__actions">
                <a href="{{ $action }}" class="ghost-button">
                    <i class="fa-solid fa-rotate-left"></i><span>{{ __('Reset') }}</span>
                </a>
                <button type="submit" class="filter-button">
                    <i class="fa-solid fa-filter"></i><span>{{ __('Apply') }}</span>
                </button>
            </div>
        </form>
    @endif

    {{ $slot }}
</div>
