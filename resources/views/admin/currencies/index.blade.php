<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">{{ __('Configuration') }}</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">{{ __('Currencies') }}</h2>
        </div>
    </x-slot>

    <div class="admin-page">
        <div class="page-section-header">
            <div>
                <p class="section-kicker">{{ __('Display currencies') }}</p>
                <h3>{{ __('Currencies') }}</h3>
            </div>
            @can('create', \App\Models\Currency::class)
                <a href="{{ route('admin.currencies.create') }}" class="premium-button premium-button--dark">
                    <i class="fa-solid fa-plus"></i><span>{{ __('New Currency') }}</span>
                </a>
            @endcan
        </div>

        {{-- The base currency is the one orders are charged and recorded in. --}}
        <div class="premium-card" style="padding:14px 18px;margin-top:12px;display:flex;gap:12px;align-items:center;flex-wrap:wrap">
            <span class="status-chip st-active">{{ __('Base') }}</span>
            <strong>{{ $base['code'] }}</strong>
            <span class="text-sm text-gray-600 dark:text-slate-300">
                {{ __('Prices, orders, payments and invoices use :base. Shoppers can switch the prices they browse to any enabled currency below; checkout still charges :base.', ['base' => $base['code']]) }}
            </span>
            <a href="{{ route('admin.settings.index') }}" class="text-sm" style="margin-left:auto;text-decoration:underline">{{ __('Change base currency') }}</a>
        </div>

        <x-admin.table-card class="mt-3 orders-panel" bulk>
            <x-slot:bulkBar>
                <x-bulk-bar :destroy="route('admin.currencies.bulk-destroy')" :status="route('admin.currencies.bulk-status')" noun="currency" />
            </x-slot:bulkBar>

            <x-slot:toolbar>
                <x-table-toolbar>
                    <x-slot:left>
                        <x-per-page-selector :current="$perPage" />
                    </x-slot:left>
                    <x-slot:right>
                        <x-search-input name="search" placeholder="{{ __('Search currencies...') }}" />
                    </x-slot:right>
                </x-table-toolbar>
            </x-slot:toolbar>

                <table class="dash-table">
                    <thead>
                        <tr>
                            <th class="bulk-check-col">
                                <input type="checkbox" class="bulk-check" @change="toggleAll($event)"
                                    :checked="allChecked" x-effect="$el.indeterminate = someChecked" aria-label="{{ __('Select all') }}">
                            </th>
                            <th>{{ __('Currency') }}</th>
                            <th style="width:90px;">{{ __('Symbol') }}</th>
                            <th style="width:170px;">{{ __('Rate') }}</th>
                            <th style="width:150px;">{{ __('Example') }}</th>
                            <th style="width:80px;">{{ __('Order') }}</th>
                            <th style="width:120px;">{{ __('Status') }}</th>
                            <th class="text-end" style="width:96px;">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($currencies as $currency)
                            <tr>
                                <td class="bulk-check-col">
                                    <input type="checkbox" class="bulk-check" data-row-check value="{{ $currency->id }}"
                                        x-model="selected" aria-label="{{ __('Select row') }}">
                                </td>
                                <td>
                                    <div class="orders-cust__name">{{ $currency->code }}</div>
                                    <div class="text-sm text-gray-500 dark:text-slate-400">{{ $currency->name }}</div>
                                </td>
                                <td>{{ $currency->symbol }}</td>
                                <td class="dash-table__amt" style="font-variant-numeric:tabular-nums;">
                                    1 {{ $base['code'] }} = {{ rtrim(rtrim(number_format((float) $currency->exchange_rate, 6), '0'), '.') }}
                                </td>
                                <td style="font-variant-numeric:tabular-nums;">{{ money(10) }} → {{ $currency->format(10) }}</td>
                                <td style="font-variant-numeric:tabular-nums;">{{ $currency->sort_order }}</td>
                                <td>
                                    <span class="status-chip {{ $currency->status ? 'st-active' : 'st-inactive' }}">
                                        {{ $currency->status ? __('Enabled') : __('Disabled') }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="action-group">
                                        <x-table-actions>
                                            <a href="{{ route('admin.currencies.edit', $currency->id) }}"
                                                class="table-actions__item table-actions__item--edit" role="menuitem">
                                                <i class="fa-solid fa-pen"></i><span>{{ __('Edit') }}</span>
                                            </a>
                                            <button type="button" class="table-actions__item table-actions__item--danger" role="menuitem"
                                                data-delete-modal-target="deleteCurrencyModal"
                                                data-delete-action="{{ route('admin.currencies.destroy', $currency->id) }}"
                                                data-delete-name="{{ $currency->code }}">
                                                <i class="fa-solid fa-trash"></i><span>{{ __('Delete') }}</span>
                                            </button>
                                        </x-table-actions>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8">
                                    <x-admin.empty-state icon="fa-solid fa-money-bill-transfer" title="{{ __('No extra currencies yet') }}"
                                        message="{{ __('Add a currency so shoppers can browse prices in it, e.g. KHR.') }}" />
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

            <x-slot:footer>
                <x-table-footer :paginator="$currencies" label="{{ __('currencies') }}" />
            </x-slot:footer>
        </x-admin.table-card>

        <x-delete-confirm-modal id="deleteCurrencyModal" title="{{ __('Delete this currency?') }}"
            message-after="{{ __('from the storefront currency switcher. This cannot be undone.') }}" />
    </div>
</x-app-layout>
