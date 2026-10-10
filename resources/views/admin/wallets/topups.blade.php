<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">{{ __('Sales') }}</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">{{ __('Top-up Requests') }}</h2>
        </div>
    </x-slot>

    <div class="admin-page wallet-page">
        <div class="page-section-header">
            <div>
                <p class="section-kicker">{{ __('Manual top-ups') }}</p>
                <h3>{{ __('Wallet top-up requests') }}</h3>
                <p class="text-gray-500 mb-0">{{ __('Customers who paid by bank transfer or QR. Check the payslip, then approve to credit the wallet or reject.') }}</p>
            </div>
            <a href="{{ route('admin.wallets.index') }}" class="ghost-button ghost-button--panel">
                <i class="fa-solid fa-wallet"></i><span>{{ __('Customer wallets') }}</span>
            </a>
        </div>

        <nav class="review-tabs" aria-label="{{ __('Top-up status') }}" style="margin-bottom:18px">
            <a href="{{ route('admin.wallets.topups.index') }}" class="review-tab {{ $tab === 'pending' ? 'is-active' : '' }}">
                {{ __('Pending') }} <span>{{ $pendingTopups->count() }}</span>
            </a>
            <a href="{{ route('admin.wallets.topups.index', ['tab' => 'reviewed']) }}" class="review-tab {{ $tab === 'reviewed' ? 'is-active' : '' }}">
                {{ __('Reviewed') }}
            </a>
        </nav>

        @if ($tab === 'pending')
            @if ($pendingTopups->isNotEmpty())
        {{-- ── Pending top-ups ───────────────────────────────────────── --}}
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
            @else
                <div class="premium-card" style="padding:28px">
                    <x-admin.empty-state icon="fa-solid fa-circle-check" title="{{ __('No pending top-ups') }}"
                        message="{{ __('New bank / QR top-up requests will appear here for review.') }}" />
                </div>
            @endif
        @else
            <x-admin.table-card>
                <table class="premium-table wallet-table">
                    <thead>
                        <tr>
                            <th>{{ __('Requested') }}</th>
                            <th>{{ __('Customer') }}</th>
                            <th style="text-align:right">{{ __('Amount') }}</th>
                            <th>{{ __('Method / ref') }}</th>
                            <th>{{ __('Result') }}</th>
                            <th>{{ __('Reviewed') }}</th>
                            <th>{{ __('Payslip') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($reviewedTopups as $topup)
                            <tr>
                                <td>{{ $topup->created_at?->format('M j, Y') }}<small class="d-block text-gray-400">{{ $topup->created_at?->format('g:i A') }}</small></td>
                                <td>
                                    <strong>{{ $topup->user?->name ?? __('Unknown') }}</strong>
                                    <small class="d-block text-gray-400">{{ $topup->user?->email }}</small>
                                </td>
                                <td style="text-align:right;font-weight:700">${{ number_format((float) $topup->amount, 2) }}</td>
                                <td>
                                    <span class="wallet-topup-pill">{{ $topup->payment_method }}</span>
                                    <code class="d-block text-gray-400" style="font-size:11.5px">{{ $topup->tran_id }}</code>
                                </td>
                                <td>
                                    @if ($topup->status === 'completed')
                                        <span class="status-chip st-active"><i class="fa-solid fa-check me-1"></i>{{ __('Approved') }}</span>
                                    @else
                                        <span class="status-chip st-inactive"><i class="fa-solid fa-xmark me-1"></i>{{ __('Rejected') }}</span>
                                    @endif
                                    @if ($topup->admin_note)
                                        <small class="d-block text-gray-500" style="margin-top:4px">{{ $topup->admin_note }}</small>
                                    @endif
                                </td>
                                <td>
                                    {{ $topup->reviewed_at?->format('M j, Y g:i A') }}
                                    <small class="d-block text-gray-400">{{ $topup->approver?->name }}</small>
                                </td>
                                <td>
                                    @if ($topup->payslip)
                                        <a href="{{ Imageurl($topup->payslip, 'wallet-topups') }}" target="_blank" rel="noopener" style="font-weight:600">
                                            <i class="fa-regular fa-image"></i> {{ __('View') }}
                                        </a>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">
                                    <x-admin.empty-state icon="fa-solid fa-clock-rotate-left" title="{{ __('Nothing reviewed yet') }}"
                                        message="{{ __('Approved and rejected top-ups will appear here.') }}" />
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <x-slot:footer><x-table-footer :paginator="$reviewedTopups" label="{{ __('requests') }}" /></x-slot:footer>
            </x-admin.table-card>
        @endif
    </div>
</x-app-layout>
