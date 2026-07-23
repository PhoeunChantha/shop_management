<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">System</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">
                {{ __('Activity Log') }}
            </h2>
        </div>
    </x-slot>

    <div class="admin-page activity-log-page">
        <div class="page-section-header">
            <div>
                <p class="section-kicker">Audit trail</p>
                <h3>Activity Log</h3>
                <p class="activity-log-lede">Review order activity, admin actions, and system events from one searchable timeline.</p>
            </div>

            <a href="{{ route('admin.activity.export', request()->query()) }}" class="premium-button premium-button--dark">
                <i class="fa-solid fa-file-export"></i>
                <span>Export CSV</span>
            </a>
        </div>

        <div class="activity-stat-strip">
            <div class="activity-stat">
                <span>Total events</span>
                <strong>{{ number_format($stats['total']) }}</strong>
            </div>
            <div class="activity-stat">
                <span>Today</span>
                <strong>{{ number_format($stats['today']) }}</strong>
            </div>
            <div class="activity-stat">
                <span>Admin actions</span>
                <strong>{{ number_format($stats['manual']) }}</strong>
            </div>
        </div>

        <x-filter-card :action="route('admin.activity.index')" :grid="'grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3'">
            <x-slot:hidden>
                <input type="hidden" name="per_page" value="{{ $perPage }}">
            </x-slot:hidden>

            <x-select name="type" size="sm" label="Type" :value="request('type')" placeholder="All event types"
                :options="$types" />

            <x-select name="user_id" size="sm" label="Actor" :value="request('user_id')" placeholder="All actors"
                :options="$actors->mapWithKeys(fn ($actor) => [$actor->id => trim($actor->name.' - '.$actor->email)])->all()" searchable />

            <div class="form-field">
                <label for="activity_order_id">Order ID</label>
                <input id="activity_order_id" type="number" name="order_id" value="{{ request('order_id') }}"
                    class="form-input" placeholder="Any order">
            </div>

            <div class="form-field">
                <label for="activity_date_from">From date</label>
                <input id="activity_date_from" type="date" name="date_from" value="{{ request('date_from') }}"
                    class="form-input">
            </div>

            <div class="form-field">
                <label for="activity_date_to">To date</label>
                <input id="activity_date_to" type="date" name="date_to" value="{{ request('date_to') }}"
                    class="form-input">
            </div>

            <div class="form-field lg:col-span-3">
                <label for="activity_search">Search</label>
                <input id="activity_search" type="search" name="search" value="{{ request('search') }}"
                    class="form-input" placeholder="Search title, note, order number, actor name or email">
            </div>
        </x-filter-card>

        <x-admin.table-card class="activity-log-card" :loader="false">
            <x-slot:toolbar>
                <x-table-toolbar>
                    <x-slot:left>
                        <x-per-page-selector :current="$perPage" :options="[10, 25, 50, 100]" />
                    </x-slot:left>
                    <x-slot:right>
                        <span class="activity-export-note">
                            <i class="fa-solid fa-shield-halved"></i>
                            Export uses current filters
                        </span>
                    </x-slot:right>
                </x-table-toolbar>
            </x-slot:toolbar>

            @if ($events->count())
                <div class="activity-timeline-list">
                    @foreach ($events as $event)
                        <article class="activity-row">
                            <span class="activity-row__icon">
                                <i class="fa-solid {{ $event->icon() }}"></i>
                            </span>

                            <div class="activity-row__body">
                                <div class="activity-row__head">
                                    <div>
                                        <h4>{{ $event->title }}</h4>
                                        @if ($event->body)
                                            <p>{{ $event->body }}</p>
                                        @endif
                                    </div>
                                    <span class="activity-type-pill">{{ str($event->type)->headline() }}</span>
                                </div>

                                <div class="activity-row__meta">
                                    <span>
                                        <i class="fa-regular fa-clock"></i>
                                        {{ optional($event->created_at)->format('M d, Y H:i') }}
                                    </span>
                                    <span>
                                        <i class="fa-regular fa-user"></i>
                                        {{ $event->actor?->name ?? 'System' }}
                                    </span>
                                    @if ($event->actor?->email)
                                        <span>
                                            <i class="fa-regular fa-envelope"></i>
                                            {{ $event->actor->email }}
                                        </span>
                                    @endif
                                    @if ($event->order)
                                        <a href="{{ route('admin.orders.show', $event->order_id) }}">
                                            <i class="fa-solid fa-receipt"></i>
                                            {{ $event->order->order_number ?? '#'.$event->order_id }}
                                        </a>
                                    @else
                                        <span>
                                            <i class="fa-solid fa-receipt"></i>
                                            Order #{{ $event->order_id }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            @else
                <x-admin.empty-state
                    icon="fa-solid fa-clock-rotate-left"
                    title="No activity found"
                    message="Try changing the filters, or check again after orders are updated."
                />
            @endif

            <x-slot:footer>
                <x-table-footer :paginator="$events" label="activity events" />
            </x-slot:footer>
        </x-admin.table-card>
    </div>
</x-app-layout>
