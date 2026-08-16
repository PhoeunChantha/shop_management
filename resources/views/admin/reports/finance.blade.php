<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">{{ __('Analytics') }}</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">{{ __('Reports Overview') }}</h2>
        </div>
    </x-slot>

    <x-admin.report-shell
        active="overview"
        :title="__('Reports Overview')"
        :filters="$filters"
        :action="route('admin.reports.index')"
        show-status
        show-payment
        :order-statuses="$orderStatuses"
        :payment-statuses="$paymentStatuses">

        <div class="finance-report-rail">
            <div>
                <span>{{ __('Gross sales') }}</span>
                <strong>${{ number_format($summary['gross_sales'], 2) }}</strong>
            </div>
            <div>
                <span>{{ __('Refunds') }}</span>
                <strong>${{ number_format($summary['refunds'], 2) }}</strong>
            </div>
            <div class="is-positive">
                <span>{{ __('Net sales') }}</span>
                <strong>${{ number_format($summary['net_sales'], 2) }}</strong>
            </div>
            <div>
                <span>{{ __('Average order') }}</span>
                <strong>${{ number_format($summary['average_order'], 2) }}</strong>
            </div>
        </div>

        <div class="finance-report-metrics">
            <div><span>{{ __('Orders') }}</span><strong>{{ number_format($summary['orders']) }}</strong></div>
            <div><span>{{ __('Paid orders') }}</span><strong>{{ number_format($summary['paid_orders']) }}</strong></div>
            <div><span>{{ __('Tax collected') }}</span><strong>${{ number_format($summary['tax_total'], 2) }}</strong></div>
            <div><span>{{ __('Shipping') }}</span><strong>${{ number_format($summary['shipping_total'], 2) }}</strong></div>
            <div><span>{{ __('Discounts') }}</span><strong>${{ number_format($summary['discount_total'], 2) }}</strong></div>
            <div><span>{{ __('Purchase cost') }}</span><strong>${{ number_format($summary['purchase_cost'], 2) }}</strong></div>
        </div>

        <div class="finance-report-grid">
            <section class="finance-report-panel">
                <div class="finance-report-panel__head">
                    <div>
                        <p class="section-kicker">{{ __('Daily Sales') }}</p>
                        <h4>{{ __('Revenue by day') }}</h4>
                    </div>
                    <a href="{{ route('admin.reports.export', ['type' => 'sales'] + request()->query()) }}" class="ghost-button ghost-button--panel">
                        <i class="fa-solid fa-download"></i><span>{{ __('Export') }}</span>
                    </a>
                </div>
                <div class="table-responsive">
                    <table class="premium-table finance-report-table">
                        <thead>
                            <tr>
                                <th>{{ __('Date') }}</th>
                                <th>{{ __('Orders') }}</th>
                                <th>{{ __('Gross') }}</th>
                                <th>{{ __('Tax') }}</th>
                                <th>{{ __('Shipping') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($salesByDay as $day)
                                <tr>
                                    <td><strong>{{ \Carbon\Carbon::parse($day['date'])->format('M d, Y') }}</strong></td>
                                    <td>{{ number_format($day['orders']) }}</td>
                                    <td>${{ number_format($day['gross_sales'], 2) }}</td>
                                    <td>${{ number_format($day['tax'], 2) }}</td>
                                    <td>${{ number_format($day['shipping'], 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5">
                                        <x-admin.empty-state icon="fa-solid fa-chart-line" title="{{ __('No sales in range') }}" message="{{ __('Try a different date range or payment filter.') }}" />
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="finance-report-panel">
                <div class="finance-report-panel__head">
                    <div>
                        <p class="section-kicker">{{ __('Product Revenue') }}</p>
                        <h4>{{ __('Top products') }}</h4>
                    </div>
                    <a href="{{ route('admin.reports.export', ['type' => 'products'] + request()->query()) }}" class="ghost-button ghost-button--panel">
                        <i class="fa-solid fa-download"></i><span>{{ __('Export') }}</span>
                    </a>
                </div>
                <div class="finance-report-list">
                    @forelse ($topProducts as $product)
                        <div>
                            <div>
                                <strong>{{ $product['name'] }}</strong>
                                <span>{{ $product['sku'] ?: 'No SKU' }} - {{ number_format($product['quantity']) }} sold</span>
                            </div>
                            <b>${{ number_format($product['revenue'], 2) }}</b>
                        </div>
                    @empty
                        <x-admin.empty-state icon="fa-solid fa-box-open" title="{{ __('No product sales') }}" message="{{ __('Paid order lines will appear here.') }}" />
                    @endforelse
                </div>
            </section>
        </div>

        <div class="finance-report-grid">
            <section class="finance-report-panel">
                <div class="finance-report-panel__head">
                    <div>
                        <p class="section-kicker">{{ __('Customer Value') }}</p>
                        <h4>{{ __('Top customers') }}</h4>
                    </div>
                    <a href="{{ route('admin.reports.export', ['type' => 'customers'] + request()->query()) }}" class="ghost-button ghost-button--panel">
                        <i class="fa-solid fa-download"></i><span>{{ __('Export') }}</span>
                    </a>
                </div>
                <div class="finance-report-list">
                    @forelse ($customerSpend as $customer)
                        <div>
                            <div>
                                <strong>{{ $customer['customer_name'] }}</strong>
                                <span>{{ $customer['customer_email'] }} - {{ number_format($customer['orders']) }} orders</span>
                            </div>
                            <b>${{ number_format($customer['spend'], 2) }}</b>
                        </div>
                    @empty
                        <x-admin.empty-state icon="fa-solid fa-users" title="{{ __('No customer spend') }}" message="{{ __('Paid customer orders will appear here.') }}" />
                    @endforelse
                </div>
            </section>

            <section class="finance-report-panel">
                <div class="finance-report-panel__head">
                    <div>
                        <p class="section-kicker">{{ __('Purchasing') }}</p>
                        <h4>{{ __('Purchase orders') }}</h4>
                    </div>
                    <a href="{{ route('admin.reports.export', ['type' => 'purchases'] + request()->query()) }}" class="ghost-button ghost-button--panel">
                        <i class="fa-solid fa-download"></i><span>{{ __('Export') }}</span>
                    </a>
                </div>
                <div class="finance-report-list">
                    @forelse ($purchaseOrders as $purchase)
                        <div>
                            <div>
                                <strong>{{ $purchase['po_number'] }}</strong>
                                <span>{{ $purchase['supplier'] }} - {{ $purchase['status'] }}</span>
                            </div>
                            <b>${{ number_format($purchase['subtotal'], 2) }}</b>
                        </div>
                    @empty
                        <x-admin.empty-state icon="fa-solid fa-clipboard-list" title="{{ __('No purchase costs') }}" message="{{ __('Purchase orders in this range will appear here.') }}" />
                    @endforelse
                </div>
            </section>
        </div>

        <section class="finance-report-panel">
            <div class="finance-report-panel__head">
                <div>
                    <p class="section-kicker">{{ __('Payment Mix') }}</p>
                    <h4>{{ __('Order payment states') }}</h4>
                </div>
            </div>
            <div class="finance-report-payment-row">
                @forelse ($paymentMix as $payment)
                    <div>
                        <span>{{ $payment['payment_status'] }}</span>
                        <strong>{{ number_format($payment['count']) }}</strong>
                    </div>
                @empty
                    <x-admin.empty-state icon="fa-solid fa-credit-card" title="{{ __('No payment data') }}" message="{{ __('Orders in this range will appear here.') }}" />
                @endforelse
            </div>
        </section>
    </x-admin.report-shell>
</x-app-layout>
