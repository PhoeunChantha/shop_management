<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">Marketing</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">
                {{ __('Coupons') }}
            </h2>
        </div>
    </x-slot>

    <div class="admin-page">
        <div class="page-section-header">
            <div>
                <p class="section-kicker">Coupon table</p>
                <h3>All Coupons</h3>
            </div>
            <a href="{{ route('admin.coupons.create') }}" class="premium-button premium-button--dark">
                <i class="fa-solid fa-plus"></i>
                <span>New Coupon</span>
            </a>
        </div>

        <section class="premium-card">
            <x-table-loader />

            <form method="GET" action="{{ route('admin.coupons.index') }}" class="table-toolbar">
                <div class="table-toolbar__left">
                    <div class="result-badge">
                        <i class="fa-solid fa-ticket"></i>
                        <span>{{ $coupons->total() }} result{{ $coupons->total() === 1 ? '' : 's' }}</span>
                    </div>

                    <label class="per-page-control">
                        <span>Show</span>
                        <select name="per_page" onchange="this.form.requestSubmit()">
                            @foreach ([5, 10, 25, 50] as $size)
                            <option value="{{ $size }}" @selected($perPage===$size)>{{ $size }}</option>
                            @endforeach
                        </select>
                        <span>per page</span>
                    </label>
                </div>

                <label class="search-control">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="search" name="search" value="{{ request('search') }}" placeholder="Search by code..."
                        autocomplete="off" data-auto-search>
                </label>
            </form>

            <div class="premium-table-wrap">
                <table class="premium-table">
                    <thead>
                        <tr>
                            <th style="width:70px;">ID</th>
                            <th>Code</th>
                            <th>Discount</th>
                            <th>Min Spend</th>
                            <th>Usage</th>
                            <th>Validity</th>
                            <th style="width:120px;">Status</th>
                            <th class="text-end" style="width:150px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($coupons as $coupon)
                        <tr>
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
                                            <span class="text-xs text-gray-400">(max ${{ number_format($coupon->max_discount, 2) }})</span>
                                        @endif
                                    @else
                                        ${{ number_format($coupon->value, 2) }}
                                    @endif
                                </span>
                            </td>
                            <td>
                                <span class="text-sm text-gray-500 dark:text-slate-400">
                                    {{ $coupon->min_spend ? '$' . number_format($coupon->min_spend, 2) : '—' }}
                                </span>
                            </td>
                            <td>
                                <span class="text-sm text-gray-600 dark:text-slate-300">
                                    {{ $coupon->used_count }}<span class="text-gray-400"> / {{ $coupon->usage_limit ?? '∞' }}</span>
                                </span>
                            </td>
                            <td>
                                <span class="text-xs text-gray-500 dark:text-slate-400">
                                    {{ $coupon->starts_at?->format('M d, Y') ?? 'Any' }}
                                    →
                                    {{ $coupon->expires_at?->format('M d, Y') ?? 'Never' }}
                                </span>
                            </td>
                            <td>
                                @php($valid = $coupon->isValid())
                                @if ($valid)
                                <span class="text-green-600 bg-green-50 px-2 py-1 rounded text-xs font-medium border border-green-200 dark:text-emerald-300 dark:bg-emerald-500/10 dark:border-emerald-500/20">Active</span>
                                @elseif (!$coupon->status)
                                <span class="text-red-600 bg-red-50 px-2 py-1 rounded text-xs font-medium border border-red-200 dark:text-red-300 dark:bg-red-500/10 dark:border-red-500/20">Disabled</span>
                                @elseif ($coupon->hasExpired())
                                <span class="text-amber-600 bg-amber-50 px-2 py-1 rounded text-xs font-medium border border-amber-200 dark:text-amber-300 dark:bg-amber-500/10 dark:border-amber-500/20">Expired</span>
                                @elseif ($coupon->reachedLimit())
                                <span class="text-amber-600 bg-amber-50 px-2 py-1 rounded text-xs font-medium border border-amber-200 dark:text-amber-300 dark:bg-amber-500/10 dark:border-amber-500/20">Used up</span>
                                @else
                                <span class="text-gray-500 bg-gray-50 px-2 py-1 rounded text-xs font-medium border border-gray-200 dark:text-slate-300 dark:bg-white/5 dark:border-white/10">Scheduled</span>
                                @endif
                            </td>
                            <td>
                                <div class="action-group">
                                    <a href="{{ route('admin.coupons.edit', $coupon->id) }}" class="table-action table-action--edit">
                                        <i class="fa-solid fa-pen"></i>
                                        <span>Edit</span>
                                    </a>

                                    <button type="button" class="table-action table-action--delete"
                                        data-delete-modal-target="deleteCouponModal"
                                        data-delete-action="{{ route('admin.coupons.destroy', $coupon->id) }}"
                                        data-delete-name="{{ $coupon->code }}">
                                        <i class="fa-solid fa-trash"></i>
                                        <span>Delete</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8">
                                <div class="empty-state">
                                    <i class="fa-solid fa-ticket"></i>
                                    <strong>No coupons found</strong>
                                    <span>Create your first discount coupon or adjust the current search.</span>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <x-table-footer :paginator="$coupons" label="coupons" />
        </section>

        <x-delete-confirm-modal
            id="deleteCouponModal"
            title="Delete this coupon?"
            message-after="from the system. This cannot be undone." />
    </div>
</x-app-layout>
