<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">{{ __('Marketing') }}</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">
                {{ __('Customer Notifications') }}
            </h2>
        </div>
    </x-slot>

    <div class="admin-page">
        <div class="page-section-header">
            <div>
                <p class="section-kicker">{{ __('Bulk messaging') }}</p>
                <h3>{{ __('Customer Notifications') }}</h3>
                <p class="customers-lede">{{ __('Send an announcement to all customers, or a filtered segment — by email, and in-app for customers with an account.') }}</p>
            </div>
            <a href="{{ route('admin.customer-notifications.create') }}" class="premium-button premium-button--dark">
                <i class="fa-solid fa-paper-plane"></i><span>{{ __('New Notification') }}</span>
            </a>
        </div>

        <x-message />

        <div style="display:flex;gap:14px;flex-wrap:wrap;margin-bottom:18px">
            <div style="flex:1;min-width:180px;padding:16px 18px;border:1px solid #e5e7eb;border-radius:12px;background:#fff">
                <span style="display:block;font-size:12.5px;color:#6b7280;margin-bottom:4px">{{ __('Notifications sent') }}</span>
                <strong style="font-size:22px">{{ number_format($stats['campaigns']) }}</strong>
            </div>
            <div style="flex:1;min-width:180px;padding:16px 18px;border:1px solid #e5e7eb;border-radius:12px;background:#fff">
                <span style="display:block;font-size:12.5px;color:#6b7280;margin-bottom:4px">{{ __('Total recipients reached') }}</span>
                <strong style="font-size:22px">{{ number_format($stats['recipients']) }}</strong>
            </div>
        </div>

        <x-admin.table-card class="mt-3">
            <x-slot:toolbar>
                <x-table-toolbar>
                    <x-slot:left>
                        <x-per-page-selector :current="$perPage" />
                    </x-slot:left>
                    <x-slot:right>
                        <x-search-input name="search" placeholder="{{ __('Search by title...') }}" />
                    </x-slot:right>
                </x-table-toolbar>
            </x-slot:toolbar>

            <table class="dash-table">
                <thead>
                    <tr>
                        <th>{{ __('Title') }}</th>
                        <th>{{ __('Audience') }}</th>
                        <th style="width:120px;">{{ __('Recipients') }}</th>
                        <th style="width:120px;">{{ __('In-app') }}</th>
                        <th>{{ __('Sent by') }}</th>
                        <th style="width:160px;">{{ __('Sent') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($campaigns as $campaign)
                        <tr>
                            <td>
                                <div class="orders-cust__name d-flex align-items-center gap-2">
                                    <i class="fa-solid fa-paper-plane text-gray-300 dark:text-slate-500"></i>
                                    <div>
                                        <div style="font-weight:600">{{ $campaign->title }}</div>
                                        <div class="muted" style="font-size:12.5px;max-width:360px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $campaign->message }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>{{ $campaign->audience_summary ?: ucfirst($campaign->audience_type) }}</td>
                            <td style="font-variant-numeric:tabular-nums;">{{ number_format($campaign->recipient_count) }}</td>
                            <td style="font-variant-numeric:tabular-nums;">{{ number_format($campaign->registered_recipient_count) }}</td>
                            <td>{{ $campaign->sentBy?->name ?: __('System') }}</td>
                            <td>{{ $campaign->created_at?->format('M j, Y g:ia') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <x-admin.empty-state icon="fa-solid fa-paper-plane" title="{{ __('No notifications sent yet') }}"
                                    message="{{ __('Send your first announcement to customers.') }}" />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <x-slot:footer>
                <x-table-footer :paginator="$campaigns" label="{{ __('notifications') }}" />
            </x-slot:footer>
        </x-admin.table-card>
    </div>
</x-app-layout>
