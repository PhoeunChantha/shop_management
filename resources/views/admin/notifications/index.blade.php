<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">System</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">{{ __('Notifications') }}</h2>
        </div>
    </x-slot>

    <div class="admin-page">
        <div class="notification-stat-strip">
            <div class="notification-stat"><span>Total</span><strong>{{ number_format($stats['total']) }}</strong></div>
            <div class="notification-stat notification-stat--active"><span>Unread</span><strong>{{ number_format($stats['unread']) }}</strong></div>
            <div class="notification-stat"><span>Critical</span><strong>{{ number_format($stats['critical']) }}</strong></div>
            <div class="notification-stat"><span>Today</span><strong>{{ number_format($stats['today']) }}</strong></div>
        </div>

        <div class="page-section-header">
            <div>
                <p class="section-kicker">Operations center</p>
                <h3>Admin Notifications</h3>
            </div>
            <form method="POST" action="{{ route('admin.notifications.mark-all-read') }}">
                @csrf
                <button type="submit" class="premium-button premium-button--dark">
                    <i class="fa-solid fa-check-double"></i><span>Mark all read</span>
                </button>
            </form>
        </div>

        <x-filter-card :action="route('admin.notifications.index')" class="notification-filter-card">
            <x-select name="type" size="sm" :value="request('type')" placeholder="Any alert type" :options="\App\Models\AdminNotification::TYPES" />
            <x-select name="priority" size="sm" :value="request('priority')" placeholder="Any priority" :options="\App\Models\AdminNotification::PRIORITIES" />
            <x-select name="state" size="sm" :value="request('state')" placeholder="Any state" :options="['unread' => 'Unread', 'read' => 'Read']" />
        </x-filter-card>

        <x-admin.table-card class="notification-table-card">
            <x-slot:toolbar>
                <x-table-toolbar>
                    <x-slot:left><x-per-page-selector :current="$perPage" /></x-slot:left>
                    <x-slot:right>
                        <x-search-input name="search" placeholder="Search alerts, products, orders..." />
                    </x-slot:right>
                </x-table-toolbar>
            </x-slot:toolbar>

            <table class="premium-table notification-table">
                <thead>
                    <tr>
                        <th>Alert</th>
                        <th>Type</th>
                        <th>Priority</th>
                        <th>Created</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($notifications as $notification)
                        <tr class="{{ $notification->isUnread() ? 'notification-row--unread' : '' }}">
                            <td>
                                <div class="notification-row-title">
                                    <span class="notification-row-icon {{ $notification->tone() }}">
                                        <i class="fa-solid {{ $notification->icon() }}"></i>
                                    </span>
                                    <div class="min-w-0">
                                        <strong>{{ $notification->title }}</strong>
                                        @if($notification->body)
                                            <small>{{ $notification->body }}</small>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td>{{ $notification->typeLabel() }}</td>
                            <td><span class="status-chip {{ $notification->priorityBadge() }}">{{ $notification->priorityLabel() }}</span></td>
                            <td>
                                {{ $notification->created_at?->format('M d, Y') }}
                                <small class="d-block text-gray-400">{{ $notification->created_at?->diffForHumans() }}</small>
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-2 flex-wrap justify-content-end">
                                    @if($notification->url)
                                        <a href="{{ $notification->url }}" class="ghost-button ghost-button--panel">
                                            <i class="fa-solid fa-arrow-up-right-from-square"></i><span>Open</span>
                                        </a>
                                    @endif
                                    @if($notification->isUnread())
                                        <form method="POST" action="{{ route('admin.notifications.read', $notification) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="ghost-button ghost-button--panel">
                                                <i class="fa-solid fa-check"></i><span>Read</span>
                                            </button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('admin.notifications.unread', $notification) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="ghost-button ghost-button--panel">
                                                <i class="fa-regular fa-envelope"></i><span>Unread</span>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <x-admin.empty-state icon="fa-regular fa-circle-check" title="No notifications found"
                                    message="The admin alert center will show orders, stock, returns, reviews, media, and deal warnings here." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <x-slot:footer>
                <x-table-footer :paginator="$notifications" label="notifications" />
            </x-slot:footer>
        </x-admin.table-card>
    </div>
</x-app-layout>
