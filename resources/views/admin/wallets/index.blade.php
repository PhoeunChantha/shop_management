<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">{{ __('Sales') }}</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">{{ __('Customer Wallets') }}</h2>
        </div>
    </x-slot>

    <div class="admin-page wallet-page">

        {{-- ── Hero stats ────────────────────────────────────────────── --}}
        <div class="wallet-stats">
            <div class="wallet-stat wallet-stat--teal">
                <div class="wallet-stat__ring">
                    <i class="fa-solid fa-users"></i>
                </div>
                <div class="wallet-stat__body">
                    <span class="wallet-stat__label">{{ __('Total Customers') }}</span>
                    <strong class="wallet-stat__value">{{ number_format($customers->total()) }}</strong>
                </div>
                <div class="wallet-stat__bg"></div>
            </div>

            <div class="wallet-stat wallet-stat--emerald">
                <div class="wallet-stat__ring">
                    <i class="fa-solid fa-wallet"></i>
                </div>
                <div class="wallet-stat__body">
                    <span class="wallet-stat__label">{{ __('Total Balance Held') }}</span>
                    <strong class="wallet-stat__value">${{ number_format($totalBalance, 2) }}</strong>
                </div>
                <div class="wallet-stat__bg"></div>
            </div>

            @if($pendingCount > 0)
            <a href="{{ route('admin.wallets.topups.index') }}" class="wallet-stat wallet-stat--amber" style="text-decoration:none">
                <div class="wallet-stat__ring">
                    <i class="fa-solid fa-hourglass-half"></i>
                </div>
                <div class="wallet-stat__body">
                    <span class="wallet-stat__label">{{ __('Pending top-ups') }}</span>
                    <strong class="wallet-stat__value">{{ $pendingCount }}</strong>
                    <small style="font-weight:700;color:#b45309">{{ __('Review requests') }} <i class="fa-solid fa-arrow-right"></i></small>
                </div>
                <div class="wallet-stat__bg"></div>
            </a>
            @endif
        </div>

        {{-- ── Customer wallets ──────────────────────────────────────── --}}
        <div class="wallet-section-head">
            <div class="wallet-section-head__icon">
                <i class="fa-solid fa-scale-balanced"></i>
            </div>
            <div>
                <p class="section-kicker">{{ __('Store credit') }}</p>
                <h3>{{ __('Customer Wallets') }}</h3>
                <p class="text-gray-500 mb-0">{{ __("Credit or debit a customer's store-wallet balance. Every change is logged.") }}</p>
            </div>
            @can('edit wallets')
                <button type="button" class="premium-button premium-button--dark ms-auto" onclick="openWalletAdjust()">
                    <i class="fa-solid fa-plus-minus"></i>
                    <span>{{ __('Adjust balance') }}</span>
                </button>
            @endcan
        </div>

        <x-admin.table-card>
            <x-slot:toolbar>
                <x-table-toolbar>
                    <x-slot:left><x-per-page-selector :current="$perPage" /></x-slot:left>
                    <x-slot:right><x-search-input name="search" placeholder="{{ __('Search name or email...') }}" /></x-slot:right>
                </x-table-toolbar>
            </x-slot:toolbar>

            <table class="premium-table wallet-table">
                <thead>
                    <tr>
                        <th>{{ __('Customer') }}</th>
                        <th style="width:160px">{{ __('Balance') }}</th>
                        <th style="width:190px">{{ __('Last activity') }}</th>
                        <th class="text-end" style="width:200px">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($customers as $customer)
                    <tr>
                        <td>
                            <div class="wallet-cust-cell">
                                <div class="wallet-cust-avatar">{{ strtoupper(substr($customer->name, 0, 2)) }}</div>
                                <div>
                                    <strong>{{ $customer->name }}</strong>
                                    <small class="d-block text-gray-400">{{ $customer->email }}</small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="wallet-balance @if((float)$customer->wallet_balance > 0) wallet-balance--positive @endif">
                                ${{ number_format((float) $customer->wallet_balance, 2) }}
                            </span>
                        </td>
                        <td>
                            @if ($customer->last_wallet_activity)
                                {{ \Illuminate\Support\Carbon::parse($customer->last_wallet_activity)->format('M j, Y') }}
                                <small class="d-block text-gray-400">{{ \Illuminate\Support\Carbon::parse($customer->last_wallet_activity)->diffForHumans() }}</small>
                            @else
                                <span class="text-gray-400">{{ __('No activity yet') }}</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="wallet-row-actions">
                                @can('edit wallets')
                                    <button type="button" class="wallet-row-btn" onclick="openWalletAdjust({{ $customer->id }})"
                                        title="{{ __('Adjust balance') }}" aria-label="{{ __('Adjust balance for :name', ['name' => $customer->name]) }}">
                                        <i class="fa-solid fa-plus-minus"></i>
                                    </button>
                                @endcan
                                <a href="{{ request()->fullUrlWithQuery(['tx_search' => $customer->email, 'tx_type' => null, 'tx_page' => null]) }}#wallet-transactions"
                                   class="wallet-row-btn wallet-row-btn--text" title="{{ __('Show this customer\'s transactions') }}">
                                    <i class="fa-solid fa-clock-rotate-left"></i> {{ __('History') }}
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4">
                            <x-admin.empty-state icon="fa-solid fa-wallet" title="{{ __('No customers found') }}"
                                message="{{ __('Customer wallet balances will appear here.') }}" />
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>

            <x-slot:footer><x-table-footer :paginator="$customers" label="{{ __('customers') }}" /></x-slot:footer>
        </x-admin.table-card>

        {{-- ── Wallet transactions ───────────────────────────────────── --}}
        <div class="wallet-section-head" id="wallet-transactions" style="margin-top:28px">
            <div class="wallet-section-head__icon">
                <i class="fa-solid fa-clock-rotate-left"></i>
            </div>
            <div>
                <p class="section-kicker">{{ __('Activity') }}</p>
                <h3>{{ __('Wallet Transactions') }}</h3>
                <p class="text-gray-500 mb-0">{{ __('Every top-up, order payment, refund and admin adjustment, newest first.') }}</p>
            </div>
        </div>

        <x-admin.table-card>
            <x-slot:toolbar>
                <x-table-toolbar>
                    <x-slot:left>
                        <x-per-page-selector name="tx_per_page" page-name="tx_page" :current="$txPerPage" />
                        {{-- Type filter: same toolbar-form pattern, so the AJAX table refreshes in place. --}}
                        <form method="GET" action="{{ url()->current() }}" class="toolbar-form">
                            @foreach (request()->except(['tx_type', 'tx_page']) as $key => $val)
                                @unless (is_array($val))<input type="hidden" name="{{ $key }}" value="{{ $val }}">@endunless
                            @endforeach
                            <x-select name="tx_type" size="sm" :value="request('tx_type')" placeholder="{{ __('All types') }}"
                                :options="collect($txTypes)->map(fn ($label) => __($label))->all()" submit-on-change />
                        </form>
                    </x-slot:left>
                    <x-slot:right>
                        <x-search-input name="tx_search" page-name="tx_page" placeholder="{{ __('Search customer, email, order or note...') }}" />
                    </x-slot:right>
                </x-table-toolbar>
            </x-slot:toolbar>

            <table class="premium-table wallet-table">
                <thead>
                    <tr>
                        <th style="width:150px">{{ __('Date') }}</th>
                        <th>{{ __('Customer') }}</th>
                        <th>{{ __('Type') }}</th>
                        <th style="text-align:right">{{ __('Amount') }}</th>
                        <th style="text-align:right">{{ __('Balance after') }}</th>
                        <th>{{ __('Order / note') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($transactions as $tx)
                        @php($in = (float) $tx->amount >= 0)
                        <tr>
                            <td>{{ $tx->created_at?->format('M j, Y') }}<small class="d-block text-gray-400">{{ $tx->created_at?->format('g:i A') }}</small></td>
                            <td>
                                <strong>{{ $tx->user?->name ?? __('Deleted customer') }}</strong>
                                <small class="d-block text-gray-400">{{ $tx->user?->email }}</small>
                            </td>
                            <td>{{ __(app(\App\Services\Admin\WalletService::class)->typeLabel($tx->type)) }}</td>
                            <td style="text-align:right;font-weight:700;color:{{ $in ? '#047857' : '#b91c1c' }}">
                                {{ $in ? '+' : '−' }}${{ number_format(abs((float) $tx->amount), 2) }}
                            </td>
                            <td style="text-align:right">${{ number_format((float) $tx->balance_after, 2) }}</td>
                            <td>
                                @if ($tx->order)
                                    <a href="{{ route('admin.orders.show', $tx->order->id) }}" style="font-weight:600">{{ $tx->order->order_number }}</a>
                                @endif
                                @if ($tx->description && (! $tx->order || $tx->description !== 'Order '.$tx->order->order_number))
                                    <small class="d-block text-gray-500">{{ $tx->description }}</small>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <x-admin.empty-state icon="fa-solid fa-clock-rotate-left" title="{{ __('No wallet transactions') }}"
                                    message="{{ __('Top-ups, wallet payments and adjustments will appear here.') }}" />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <x-slot:footer><x-table-footer :paginator="$transactions" label="{{ __('transactions') }}" /></x-slot:footer>
        </x-admin.table-card>

    </div>

    @can('edit wallets')
        @include('admin.wallets._adjust_modal')
    @endcan
</x-app-layout>
