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

        <div class="finance-report-grid">
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

            <section class="finance-report-panel">
                <div class="finance-report-panel__head">
                    <div>
                        <p class="section-kicker">{{ __('New Customers') }}</p>
                        <h4>{{ __('Recent registrations') }}</h4>
                    </div>
                </div>
                <div class="finance-report-list">
                    @forelse ($recent as $customer)
                        <div>
                            <div>
                                <strong>{{ $customer['name'] }}</strong>
                                <span>{{ $customer['email'] }} - {{ $customer['registered'] }}</span>
                            </div>
                            <b>{{ $customer['verified'] === 'Yes' ? __('Verified') : __('Unverified') }}</b>
                        </div>
                    @empty
                        <x-admin.empty-state icon="fa-solid fa-user-plus" title="{{ __('No registrations') }}" message="{{ __('New customer signups in this range will appear here.') }}" />
                    @endforelse
                </div>
            </section>
        </div>
    </x-admin.report-shell>
</x-app-layout>
