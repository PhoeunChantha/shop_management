<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">Operations</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">{{ __('Setup Health') }}</h2>
        </div>
    </x-slot>

    <div class="admin-page setup-health-page">
        <div class="page-section-header setup-health-heading">
            <div>
                <p class="section-kicker">Launch Control</p>
                <h3>Admin Setup Health</h3>
            </div>
            <a href="{{ route('admin.settings.index') }}" class="premium-button premium-button--dark">
                <i class="fa-solid fa-gear"></i><span>Store Settings</span>
            </a>
        </div>

        <section class="setup-health-hero">
            <div class="setup-health-score" style="--score: {{ $score }};">
                <div class="setup-health-score__ring">
                    <strong>{{ $score }}%</strong>
                    <span>Ready</span>
                </div>
                <div>
                    <p class="section-kicker">System Readiness</p>
                    <h4>{{ $critical > 0 ? 'Critical work remains' : ($attention > 0 ? 'Almost ready' : 'Ready to operate') }}</h4>
                    <p>{{ $ready }} of {{ $total }} checks are clean. Use the priority list to finish the highest-impact admin setup tasks first.</p>
                </div>
            </div>

            <div class="setup-health-kpis">
                <div>
                    <span>Ready</span>
                    <strong>{{ $ready }}</strong>
                </div>
                <div>
                    <span>Attention</span>
                    <strong>{{ $attention }}</strong>
                </div>
                <div>
                    <span>Critical</span>
                    <strong>{{ $critical }}</strong>
                </div>
            </div>
        </section>

        <div class="setup-health-grid">
            <section class="setup-health-panel setup-health-panel--priority">
                <div class="setup-health-panel__head">
                    <div>
                        <p class="section-kicker">Priority Queue</p>
                        <h4>Next actions</h4>
                    </div>
                    <i class="fa-solid fa-bolt"></i>
                </div>

                <div class="setup-health-priority-list">
                    @forelse ($priorityChecks as $check)
                        <a href="{{ $check['url'] }}" class="setup-health-priority setup-health-priority--{{ $check['status'] }}">
                            <span>
                                <strong>{{ $check['title'] }}</strong>
                                <small>{{ $check['description'] }}</small>
                            </span>
                            <b>{{ number_format($check['count']) }}</b>
                            <i class="fa-solid fa-arrow-right"></i>
                        </a>
                    @empty
                        <x-admin.empty-state icon="fa-solid fa-circle-check" title="No priority actions" message="All setup health checks are currently clean." />
                    @endforelse
                </div>
            </section>

            <section class="setup-health-panel">
                <div class="setup-health-panel__head">
                    <div>
                        <p class="section-kicker">Operating Rhythm</p>
                        <h4>Quick links</h4>
                    </div>
                    <i class="fa-solid fa-compass"></i>
                </div>

                <div class="setup-health-quick-links">
                    <a href="{{ route('admin.orders.index') }}"><i class="fa-solid fa-receipt"></i><span>Orders</span></a>
                    <a href="{{ route('admin.inventory.index') }}"><i class="fa-solid fa-warehouse"></i><span>Inventory</span></a>
                    <a href="{{ route('admin.deals.index') }}"><i class="fa-solid fa-tags"></i><span>Deals</span></a>
                    <a href="{{ route('admin.media.index') }}"><i class="fa-solid fa-photo-film"></i><span>Media</span></a>
                    <a href="{{ route('admin.seo.index') }}"><i class="fa-solid fa-magnifying-glass-chart"></i><span>SEO</span></a>
                    <a href="{{ route('admin.notifications.index') }}"><i class="fa-solid fa-bell"></i><span>Alerts</span></a>
                </div>
            </section>
        </div>

        <section class="setup-health-checks">
            @foreach ($groups as $group)
                <article class="setup-health-group">
                    <div class="setup-health-group__head">
                        <span><i class="fa-solid {{ $group['icon'] }}"></i></span>
                        <div>
                            <h4>{{ $group['label'] }}</h4>
                            <p>{{ $group['ready'] }} / {{ count($group['checks']) }} checks ready</p>
                        </div>
                    </div>

                    <div class="setup-health-check-list">
                        @foreach ($group['checks'] as $check)
                            <a href="{{ $check['url'] }}" class="setup-health-check setup-health-check--{{ $check['status'] }}">
                                <div class="setup-health-check__status">
                                    <i class="fa-solid {{ $check['status'] === 'ready' ? 'fa-circle-check' : ($check['status'] === 'critical' ? 'fa-triangle-exclamation' : 'fa-circle-info') }}"></i>
                                </div>
                                <div class="setup-health-check__copy">
                                    <strong>{{ $check['title'] }}</strong>
                                    <span>{{ $check['description'] }}</span>
                                </div>
                                <div class="setup-health-check__meta">
                                    <b>{{ number_format($check['count']) }}</b>
                                    <small>{{ $check['positiveCount'] ? 'found' : 'open' }}</small>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </article>
            @endforeach
        </section>
    </div>
</x-app-layout>
