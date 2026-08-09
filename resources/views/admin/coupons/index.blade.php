<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">{{ __('Marketing') }}</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">
                {{ __('Coupons') }}
            </h2>
        </div>
    </x-slot>

    <div class="admin-page">
        <div class="page-section-header">
            <div>
                <p class="section-kicker">{{ __('Coupon table') }}</p>
                <h3>{{ __('All Coupons') }}</h3>
            </div>
            <a href="{{ route('admin.coupons.create') }}" class="premium-button premium-button--dark">
                <i class="fa-solid fa-plus"></i>
                <span>{{ __('New Coupon') }}</span>
            </a>
        </div>

        <x-admin.table-card bulk>
            <x-slot:bulkBar>
                <x-bulk-bar :destroy="route('admin.coupons.bulk-destroy')" :status="route('admin.coupons.bulk-status')" noun="coupon" />
            </x-slot:bulkBar>

            <x-slot:toolbar>
                <x-table-toolbar>
                    <x-slot:left>
                        <x-per-page-selector :current="$perPage" />
                    </x-slot:left>
                    <x-slot:right>
                        <x-search-input name="search" :placeholder="__('Search by code...')" />
                    </x-slot:right>
                </x-table-toolbar>
            </x-slot:toolbar>

                <table class="premium-table">
                    <thead>
                        <tr>
                            <th class="bulk-check-col">
                                <input type="checkbox" class="bulk-check" @change="toggleAll($event)"
                                    :checked="allChecked" x-effect="$el.indeterminate = someChecked" aria-label="{{ __('Select all') }}">
                            </th>
                            <th style="width:70px;">{{ __('ID') }}</th>
                            <th>{{ __('Code') }}</th>
                            <th>{{ __('Discount') }}</th>
                            <th>{{ __('Min Spend') }}</th>
                            <th>{{ __('Usage') }}</th>
                            <th>{{ __('Validity') }}</th>
                            <th style="width:120px;">{{ __('Status') }}</th>
                            <th class="text-end" style="width:150px;">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($coupons as $coupon)
                        <tr>
                            <td class="bulk-check-col">
                                <input type="checkbox" class="bulk-check" data-row-check value="{{ $coupon->id }}"
                                    x-model="selected" aria-label="{{ __('Select row') }}">
                            </td>
                            <td>
                                <span class="muted-id">#{{ $coupon->id }}</span>
                            </td>
                            <td>
                                <strong class="text-gray-900 dark:text-slate-100 font-mono">{{ $coupon->code }}</strong>
                            </td>
                            <td>
                                <span class="text-sm text-gray-700 dark:text-slate-300">
                                    @if ($coupon->type === \App\Enums\CouponType::Percentage)
                                        {{ rtrim(rtrim(number_format($coupon->value, 2), '0'), '.') }}%
                                        @if ($coupon->max_discount)
                                            <span class="text-xs text-gray-400">({{ __('max') }} {{ money($coupon->max_discount) }})</span>
                                        @endif
                                    @else
                                        {{ money($coupon->value) }}
                                    @endif
                                </span>
                            </td>
                            <td>
                                <span class="text-sm text-gray-500 dark:text-slate-400">
                                    {{ $coupon->min_spend ? money($coupon->min_spend) : '—' }}
                                </span>
                            </td>
                            <td>
                                <span class="text-sm text-gray-600 dark:text-slate-300">
                                    {{ $coupon->used_count }}<span class="text-gray-400"> / {{ $coupon->usage_limit ?? '∞' }}</span>
                                </span>
                            </td>
                            <td>
                                <span class="text-xs text-gray-500 dark:text-slate-400">
                                    {{ $coupon->starts_at?->format('M d, Y') ?? __('Any') }}
                                    →
                                    {{ $coupon->expires_at?->format('M d, Y') ?? __('Never') }}
                                </span>
                            </td>
                            <td>
                                @php($valid = $coupon->isValid())
                                @if ($valid)
                                    <span class="status-chip st-active">{{ __('Active') }}</span>
                                @elseif (!$coupon->status)
                                    <span class="status-chip st-archived">{{ __('Disabled') }}</span>
                                @elseif ($coupon->hasExpired())
                                    <span class="status-chip st-inactive">{{ __('Expired') }}</span>
                                @elseif ($coupon->reachedLimit())
                                    <span class="status-chip st-inactive">{{ __('Used up') }}</span>
                                @else
                                    <span class="status-chip st-draft">{{ __('Scheduled') }}</span>
                                @endif
                            </td>
                            <td>
                                <div class="action-group">
                                    <x-table-actions>
                                        <a href="{{ route('admin.coupons.edit', $coupon->id) }}" class="table-actions__item table-actions__item--edit" role="menuitem">
                                            <i class="fa-solid fa-pen"></i>
                                            <span>{{ __('Edit') }}</span>
                                        </a>

                                        <button type="button" class="table-actions__item table-actions__item--danger" role="menuitem"
                                            data-delete-modal-target="deleteCouponModal"
                                            data-delete-action="{{ route('admin.coupons.destroy', $coupon->id) }}"
                                            data-delete-name="{{ $coupon->code }}">
                                            <i class="fa-solid fa-trash"></i>
                                            <span>{{ __('Delete') }}</span>
                                        </button>
                                    </x-table-actions>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9">
                                <x-admin.empty-state icon="fa-solid fa-ticket" :title="__('No coupons found')"
                                    :message="__('Create your first discount coupon or adjust the current search.')" />
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>

            <x-slot:footer>
                <x-table-footer :paginator="$coupons" :label="__('coupons')" />
            </x-slot:footer>
        </x-admin.table-card>

        <x-delete-confirm-modal
            id="deleteCouponModal"
            :title="__('Delete this coupon?')"
            :message-after="__('from the system. This cannot be undone.')" />
    </div>
</x-app-layout>
