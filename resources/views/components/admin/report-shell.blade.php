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

@php
    $tabs = [
        ['key' => 'overview', 'label' => __('Overview'), 'icon' => 'fa-gauge-high', 'route' => 'admin.reports.index', 'permission' => 'view reports'],
        ['key' => 'sales', 'label' => __('Sales'), 'icon' => 'fa-chart-line', 'route' => 'admin.reports.sales', 'permission' => 'view sales reports'],
        ['key' => 'products', 'label' => __('Products'), 'icon' => 'fa-box-open', 'route' => 'admin.reports.products', 'permission' => 'view product reports'],
        ['key' => 'stock', 'label' => __('Stock'), 'icon' => 'fa-warehouse', 'route' => 'admin.reports.stock', 'permission' => 'view stock reports'],
        ['key' => 'payments', 'label' => __('Payments'), 'icon' => 'fa-credit-card', 'route' => 'admin.reports.payments', 'permission' => 'view payment reports'],
        ['key' => 'customers', 'label' => __('Customers'), 'icon' => 'fa-user-group', 'route' => 'admin.reports.customers', 'permission' => 'view customer reports'],
        ['key' => 'purchasing', 'label' => __('Purchasing'), 'icon' => 'fa-clipboard-list', 'route' => 'admin.reports.purchasing', 'permission' => 'view purchasing reports'],
        ['key' => 'register', 'label' => __('Registrations'), 'icon' => 'fa-user-plus', 'route' => 'admin.reports.register', 'permission' => 'view register reports'],
        ['key' => 'returns', 'label' => __('Returns'), 'icon' => 'fa-rotate-left', 'route' => 'admin.reports.returns', 'permission' => 'view return reports'],
    ];
@endphp

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

    <nav class="finance-report-export-group finance-report-tabs">
        @foreach ($tabs as $tab)
            @can($tab['permission'])
                <a href="{{ route($tab['route']) }}"
                    class="{{ $active === $tab['key'] ? 'premium-button premium-button--dark' : 'ghost-button ghost-button--panel' }}">
                    <i class="fa-solid {{ $tab['icon'] }}"></i><span>{{ $tab['label'] }}</span>
                </a>
            @endcan
        @endforeach
    </nav>

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
                <label>
                    <span>{{ __('Order status') }}</span>
                    <select name="status">
                        <option value="">{{ __('All statuses') }}</option>
                        @foreach ($orderStatuses as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['status'] ?? null) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
            @endif
            @if ($showPayment)
                <label>
                    <span>{{ __('Payment status') }}</span>
                    <select name="payment_status">
                        <option value="">{{ __('All payments') }}</option>
                        @foreach ($paymentStatuses as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['payment_status'] ?? null) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
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
