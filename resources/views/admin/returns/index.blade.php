<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">{{ __('Sales') }}</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">{{ __('Returns & Refunds') }}</h2>
        </div>
    </x-slot>

    <div class="admin-page">
        <div class="return-stat-strip">
            <div class="return-stat"><span>{{ __('Total returns') }}</span><strong>{{ number_format($stats['total']) }}</strong></div>
            <div class="return-stat"><span>{{ __('Requested') }}</span><strong>{{ number_format($stats['requested']) }}</strong></div>
            <div class="return-stat return-stat--active"><span>{{ __('In progress') }}</span><strong>{{ number_format($stats['approved']) }}</strong></div>
            <div class="return-stat"><span>{{ __('Refunded') }}</span><strong>${{ number_format($stats['refunds'], 2) }}</strong></div>
        </div>

        <div class="page-section-header">
            <div>
                <p class="section-kicker">{{ __('Support workflow') }}</p>
                <h3>{{ __('All Return Requests') }}</h3>
            </div>
            <a href="{{ route('admin.returns.create') }}" class="premium-button premium-button--dark">
                <i class="fa-solid fa-plus"></i><span>{{ __('New Return') }}</span>
            </a>
        </div>

        @include('admin.saved-views._bar', ['scope' => 'returns', 'icon' => 'fa-rotate-left', 'color' => '#dc2626'])

        <x-filter-card :action="route('admin.returns.index')" class="return-filter-card">
            <x-select name="status" size="sm" :value="request('status')" placeholder="{{ __('Any return status') }}" :options="\App\Models\ReturnRequest::STATUSES" />
            <x-select name="refund_status" size="sm" :value="request('refund_status')" placeholder="{{ __('Any refund status') }}" :options="\App\Models\ReturnRequest::REFUND_STATUSES" />
        </x-filter-card>

        <x-admin.table-card class="return-table-card">
            <x-slot:toolbar>
                <x-table-toolbar>
                    <x-slot:left><x-per-page-selector :current="$perPage" /></x-slot:left>
                    <x-slot:right>
                        <x-search-input name="search" placeholder="{{ __('Search return, order or customer...') }}" />
                    </x-slot:right>
                </x-table-toolbar>
            </x-slot:toolbar>

            <table class="premium-table">
                <thead>
                    <tr>
                        <th>{{ __('Return') }}</th>
                        <th>{{ __('Order') }}</th>
                        <th>{{ __('Customer') }}</th>
                        <th>{{ __('Items') }}</th>
                        <th>{{ __('Requested') }}</th>
                        <th>{{ __('Refund') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th class="text-end">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($returns as $return)
                        <tr>
                            <td>
                                <strong class="text-gray-900 dark:text-slate-100">{{ $return->return_number }}</strong>
                                <div class="text-xs text-gray-400">{{ $return->reasonLabel() }}</div>
                            </td>
                            <td>
                                <a href="{{ route('admin.orders.show', $return->order_id) }}" class="font-semibold text-gray-900 dark:text-slate-100">
                                    {{ $return->order?->order_number }}
                                </a>
                            </td>
                            <td>
                                <span class="text-sm font-semibold">{{ $return->order?->customer_name }}</span>
                                <small class="d-block text-gray-400">{{ $return->order?->customer_email }}</small>
                            </td>
                            <td><span class="status-pill status-pill--neutral">{{ $return->items_count }} line(s)</span></td>
                            <td>${{ number_format((float) $return->requested_amount, 2) }}</td>
                            <td>
                                <span class="status-chip {{ $return->refundBadge() }}">{{ $return->refundStatusLabel() }}</span>
                                <div class="text-xs text-gray-400 mt-1">${{ number_format((float) $return->refund_amount, 2) }}</div>
                            </td>
                            <td><span class="status-chip {{ $return->statusBadge() }}">{{ $return->statusLabel() }}</span></td>
                            <td class="text-end">
                                <a href="{{ route('admin.returns.show', $return) }}" class="ghost-button ghost-button--panel">
                                    <i class="fa-solid fa-eye"></i><span>{{ __('View') }}</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <x-admin.empty-state icon="fa-solid fa-rotate-left" title="{{ __('No returns found') }}"
                                    message="{{ __('Create a return request from an order when support needs refund tracking.') }}" />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <x-slot:footer>
                <x-table-footer :paginator="$returns" label="{{ __('returns') }}" />
            </x-slot:footer>
        </x-admin.table-card>
    </div>
</x-app-layout>
