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
            <x-admin.table-card>
                <table class="premium-table wallet-table">
                    <thead>
                        <tr>
                            <th>{{ __('Requested') }}</th>
                            <th>{{ __('Customer') }}</th>
                            <th style="text-align:right">{{ __('Amount') }}</th>
                            <th>{{ __('Method / ref') }}</th>
                            <th>{{ __('Payslip') }}</th>
                            @can('edit wallets')<th class="text-end">{{ __('Actions') }}</th>@endcan
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($pendingTopups as $topup)
                            <tr>
                                <td>{{ $topup->created_at?->format('M j, Y') }}<small class="d-block text-gray-400">{{ $topup->created_at?->format('g:i A') }} · {{ $topup->created_at?->diffForHumans() }}</small></td>
                                <td>
                                    <div class="wallet-cust-cell">
                                        <div class="wallet-cust-avatar">{{ strtoupper(substr($topup->user?->name ?? '?', 0, 2)) }}</div>
                                        <div>
                                            <strong>{{ $topup->user?->name ?? __('Unknown') }}</strong>
                                            <small class="d-block text-gray-400">{{ $topup->user?->email }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td style="text-align:right;font-weight:800;color:#047857;font-size:15px">${{ number_format((float) $topup->amount, 2) }}</td>
                                <td>
                                    <span class="wallet-topup-pill">{{ $topup->payment_method }}</span>
                                    <code class="d-block text-gray-400" style="font-size:11.5px;margin-top:4px">{{ $topup->tran_id }}</code>
                                </td>
                                <td>
                                    @if ($topup->payslip)
                                        <a href="{{ Imageurl($topup->payslip, 'wallet-topups') }}" target="_blank" rel="noopener" class="topup-slip" title="{{ __('Open payslip full size') }}">
                                            <img src="{{ Imageurl($topup->payslip, 'wallet-topups') }}" alt="{{ __('Payslip') }}">
                                        </a>
                                    @else
                                        <span class="wallet-topup-no-proof">{{ __('No proof') }}</span>
                                    @endif
                                </td>
                                @can('edit wallets')
                                    <td class="text-end">
                                        <div class="wallet-row-actions">
                                            <form method="POST" action="{{ route('admin.wallets.topups.approve', $topup) }}">
                                                @csrf
                                                <button type="submit" class="wallet-topup-approve"><i class="fa-solid fa-check"></i> {{ __('Approve') }}</button>
                                            </form>
                                            <button type="button" class="wallet-topup-reject"
                                                onclick="openTopupReject(@js(route('admin.wallets.topups.reject', $topup)), @js($topup->user?->name ?? ''), @js('$'.number_format((float) $topup->amount, 2)))">
                                                <i class="fa-solid fa-xmark"></i> {{ __('Reject') }}
                                            </button>
                                        </div>
                                    </td>
                                @endcan
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">
                                    <x-admin.empty-state icon="fa-solid fa-circle-check" title="{{ __('No pending top-ups') }}"
                                        message="{{ __('New bank / QR top-up requests will appear here for review.') }}" />
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </x-admin.table-card>
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
    @can('edit wallets')
        {{-- Reject a top-up: optional reason the customer will see. --}}
        <div class="modal-backdrop-premium" x-data="{ open: false, action: '', who: '', amount: '' }"
            x-show="open" x-cloak x-transition.opacity.duration.150ms
            @keydown.escape.window="open = false" @click.self="open = false"
            @topup-reject.window="action = $event.detail.action; who = $event.detail.who; amount = $event.detail.amount; open = true; $nextTick(() => $refs.note.focus())"
            style="display:none;">
            <div class="form-modal" style="max-width:480px" role="dialog" aria-modal="true" aria-labelledby="topupRejectTitle"
                x-show="open" x-transition:enter="fm-enter" x-transition:enter-start="fm-from" x-transition:enter-end="fm-to"
                x-transition:leave="fm-leave" x-transition:leave-start="fm-to" x-transition:leave-end="fm-from">
                <div class="form-modal__head">
                    <div class="form-modal__icon" style="color:#b91c1c"><i class="fa-solid fa-xmark"></i></div>
                    <div class="flex-grow-1">
                        <h3 id="topupRejectTitle">{{ __('Reject top-up') }}</h3>
                        <p><span x-text="amount"></span> · <span x-text="who"></span></p>
                    </div>
                    <button type="button" class="form-modal__close" @click="open = false" aria-label="{{ __('Close') }}"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <form :action="action" method="POST" class="form-modal__body">
                    @csrf
                    <div class="form-field">
                        <label for="topup_reject_note">{{ __('Reason') }} <span class="text-gray-400" style="font-weight:400">({{ __('optional') }})</span></label>
                        <input type="text" id="topup_reject_note" name="note" x-ref="note" maxlength="255" class="form-input"
                            placeholder="{{ __('e.g. Payment not received') }}">
                        <small class="text-gray-400 d-block mt-1">{{ __('The wallet is not credited.') }}</small>
                    </div>
                    <div class="form-modal__foot">
                        <button type="button" class="modal-cancel" @click="open = false">{{ __('Cancel') }}</button>
                        <button type="submit" class="form-submit-button" style="background:#b91c1c;border-color:#b91c1c">
                            <i class="fa-solid fa-xmark"></i> {{ __('Reject top-up') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        @push('js')
            <script>
                window.openTopupReject = function (action, who, amount) {
                    window.dispatchEvent(new CustomEvent('topup-reject', { detail: { action: action, who: who, amount: amount } }));
                };
            </script>
        @endpush
    @endcan
</x-app-layout>
