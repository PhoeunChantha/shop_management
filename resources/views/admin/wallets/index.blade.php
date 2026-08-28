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

            @if($pendingTopups->isNotEmpty())
            <div class="wallet-stat wallet-stat--amber">
                <div class="wallet-stat__ring">
                    <i class="fa-solid fa-hourglass-half"></i>
                </div>
                <div class="wallet-stat__body">
                    <span class="wallet-stat__label">{{ __('Pending Requests') }}</span>
                    <strong class="wallet-stat__value">{{ $pendingTopups->count() }}</strong>
                </div>
                <div class="wallet-stat__bg"></div>
            </div>
            @endif
        </div>

        {{-- ── Pending top-ups ───────────────────────────────────────── --}}
        @if($pendingTopups->isNotEmpty())
        <div class="wallet-pending">
            <div class="wallet-pending__header">
                <div class="wallet-pending__alert-dot"></div>
                <div class="wallet-pending__titles">
                    <p class="wallet-pending__kicker">{{ __('Manual top-ups') }}</p>
                    <h3 class="wallet-pending__heading">
                        {{ __('Pending top-up requests') }}
                        <span class="wallet-pending__count">{{ $pendingTopups->count() }}</span>
                    </h3>
                    <p class="wallet-pending__sub">{{ __('Approve to credit the wallet, or reject if payment was not received.') }}</p>
                </div>
            </div>

            <div class="wallet-topup-cards">
                @foreach($pendingTopups as $topup)
                <div class="wallet-topup-card">
                    {{-- customer --}}
                    <div class="wallet-topup-card__customer">
                        <div class="wallet-topup-avatar">{{ strtoupper(substr($topup->user?->name ?? '?', 0, 2)) }}</div>
                        <div class="wallet-topup-card__who">
                            <strong>{{ $topup->user?->name ?? __('Unknown') }}</strong>
                            <small>{{ $topup->user?->email }}</small>
                        </div>
                    </div>

                    {{-- amount --}}
                    <div class="wallet-topup-card__amount">
                        <span class="wallet-topup-card__amount-label">{{ __('Amount') }}</span>
                        <strong>${{ number_format((float) $topup->amount, 2) }}</strong>
                    </div>

                    {{-- method + ref --}}
                    <div class="wallet-topup-card__meta">
                        <span class="wallet-topup-pill">{{ $topup->payment_method }}</span>
                        <code class="wallet-topup-ref">{{ $topup->tran_id }}</code>
                    </div>

                    {{-- proof image --}}
                    <div class="wallet-topup-card__proof">
                        @if($topup->payslip)
                            <a href="{{ Imageurl($topup->payslip, 'wallet-topups') }}" target="_blank" rel="noopener" class="wallet-topup-proof">
                                <img src="{{ Imageurl($topup->payslip, 'wallet-topups') }}" alt="{{ __('Payslip') }}">
                                <div class="wallet-topup-proof__overlay"><i class="fa-solid fa-arrow-up-right-from-square"></i></div>
                            </a>
                        @else
                            <span class="wallet-topup-no-proof">{{ __('No proof') }}</span>
                        @endif
                    </div>

                    {{-- date --}}
                    <div class="wallet-topup-card__date">
                        <i class="fa-regular fa-calendar-check"></i>
                        <span>
                            {{ $topup->created_at?->format('M j, Y') }}<br>
                            <small>{{ $topup->created_at?->format('g:i A') }}</small>
                        </span>
                    </div>

                    {{-- actions --}}
                    <div class="wallet-topup-card__actions">
                        <form method="POST" action="{{ route('admin.wallets.topups.approve', $topup) }}">
                            @csrf
                            <button type="submit" class="wallet-topup-approve">
                                <i class="fa-solid fa-check"></i> {{ __('Approve') }}
                            </button>
                        </form>
                        <form method="POST" action="{{ route('admin.wallets.topups.reject', $topup) }}" class="wallet-topup-reject-form">
                            @csrf
                            <input type="text" name="note" placeholder="{{ __('Reason (optional)') }}" class="wallet-topup-reject-note">
                            <button type="submit" class="wallet-topup-reject">
                                <i class="fa-solid fa-xmark"></i> {{ __('Reject') }}
                            </button>
                        </form>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

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
                        <th>{{ __('Adjust Balance') }}</th>
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
                            <form method="POST" action="{{ route('admin.wallets.adjust', $customer) }}" class="wallet-adjust">
                                @csrf
                                <input type="number" name="amount" min="0.01" step="0.01" placeholder="0.00" required class="wallet-adjust__amount">
                                <input type="text" name="note" placeholder="{{ __('Note…') }}" class="wallet-adjust__note">
                                <button type="submit" name="direction" value="credit" class="wallet-adjust__credit">
                                    <i class="fa-solid fa-plus"></i> {{ __('Credit') }}
                                </button>
                                <button type="submit" name="direction" value="debit" class="wallet-adjust__debit">
                                    <i class="fa-solid fa-minus"></i> {{ __('Debit') }}
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3">
                            <x-admin.empty-state icon="fa-solid fa-wallet" title="{{ __('No customers found') }}"
                                message="{{ __('Customer wallet balances will appear here.') }}" />
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>

            <x-slot:footer><x-table-footer :paginator="$customers" label="{{ __('customers') }}" /></x-slot:footer>
        </x-admin.table-card>

    </div>
</x-app-layout>
