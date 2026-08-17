<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">{{ __('Analytics') }}</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">{{ __('Registration Report') }}</h2>
        </div>
    </x-slot>

    <x-admin.report-shell
        active="register"
        :title="__('Registration Report')"
        :filters="$filters"
        :action="route('admin.reports.register')"
        :export-url="route('admin.reports.register.export', request()->query())"
        :pdf-url="route('admin.reports.register.export', ['format' => 'pdf'] + request()->query())">

        <div class="finance-report-metrics">
            <div><span>{{ __('Total customers') }}</span><strong>{{ number_format($summary['total_customers']) }}</strong></div>
            <div><span>{{ __('New in range') }}</span><strong>{{ number_format($summary['in_range']) }}</strong></div>
            <div><span>{{ __('Verified') }}</span><strong>{{ number_format($summary['verified']) }}</strong></div>
        </div>

        <section class="finance-report-panel">
            <div class="finance-report-panel__head">
                <div>
                    <p class="section-kicker">{{ __('Signups') }}</p>
                    <h4>{{ __('Registrations by day') }}</h4>
                </div>
            </div>
            <div class="table-responsive">
                <table class="premium-table finance-report-table">
                    <thead>
                        <tr>
                            <th>{{ __('Date') }}</th>
                            <th>{{ __('Signups') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($signupsByDay as $day)
                            <tr>
                                <td><strong>{{ \Carbon\Carbon::parse($day['date'])->format('M d, Y') }}</strong></td>
                                <td>{{ number_format($day['signups']) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2">
                                    <x-admin.empty-state icon="fa-solid fa-user-plus" title="{{ __('No registrations') }}" message="{{ __('New customer signups in this range will appear here.') }}" />
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <x-admin.table-card ajax :loader="false">
            <x-slot:toolbar>
                <x-table-toolbar>
                    <x-slot:left><x-per-page-selector :current="$perPage" /></x-slot:left>
                    <x-slot:right><x-search-input name="search" placeholder="{{ __('Search name or email...') }}" /></x-slot:right>
                </x-table-toolbar>
            </x-slot:toolbar>

            <table class="premium-table finance-report-table">
                <thead>
                    <tr>
                        <th>{{ __('Customer') }}</th>
                        <th>{{ __('Email') }}</th>
                        <th>{{ __('Registered') }}</th>
                        <th>{{ __('Verified') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recent as $customer)
                        <tr>
                            <td><strong>{{ $customer['name'] }}</strong></td>
                            <td>{{ $customer['email'] }}</td>
                            <td>{{ $customer['registered'] }}</td>
                            <td>{{ $customer['verified'] === 'Yes' ? __('Verified') : __('Unverified') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">
                                <x-admin.empty-state icon="fa-solid fa-user-plus" title="{{ __('No registrations') }}" message="{{ __('New customer signups in this range will appear here.') }}" />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <x-slot:footer><x-table-footer :paginator="$recent" label="{{ __('registrations') }}" /></x-slot:footer>
        </x-admin.table-card>
    </x-admin.report-shell>
</x-app-layout>
