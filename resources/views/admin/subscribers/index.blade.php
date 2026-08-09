<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">{{ __('Marketing') }}</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">{{ __('Newsletter Subscribers') }}</h2>
        </div>
    </x-slot>

    <div class="admin-page">
        <div class="cart-recovery-strip">
            <div class="cart-recovery-stat">
                <span><i class="fa-solid fa-envelope"></i> {{ __('Total subscribers') }}</span>
                <strong>{{ number_format($stats['total']) }}</strong>
            </div>
            <div class="cart-recovery-stat cart-recovery-stat--active">
                <span><i class="fa-solid fa-user-plus"></i> {{ __('Today') }}</span>
                <strong>{{ number_format($stats['today']) }}</strong>
            </div>
            <div class="cart-recovery-stat">
                <span><i class="fa-solid fa-calendar-days"></i> {{ __('This month') }}</span>
                <strong>{{ number_format($stats['this_month']) }}</strong>
            </div>
        </div>

        <div class="page-section-header">
            <div>
                <p class="section-kicker">{{ __('Audience') }}</p>
                <h3>{{ __('Newsletter Subscribers') }}</h3>
            </div>
            <a href="{{ route('admin.subscribers.export', request()->query()) }}" class="premium-button premium-button--dark">
                <i class="fa-solid fa-file-export"></i><span>{{ __('Export CSV') }}</span>
            </a>
        </div>

        <x-admin.table-card>
            <x-slot:toolbar>
                <x-table-toolbar>
                    <x-slot:left><x-per-page-selector :current="$perPage" /></x-slot:left>
                    <x-slot:right><x-search-input name="search" placeholder="{{ __('Search by email...') }}" /></x-slot:right>
                </x-table-toolbar>
            </x-slot:toolbar>

            <table class="premium-table">
                <thead>
                    <tr>
                        <th>{{ __('Email') }}</th>
                        <th>{{ __('Subscribed') }}</th>
                        <th class="text-end">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($subscribers as $subscriber)
                        <tr>
                            <td><strong>{{ $subscriber->email }}</strong></td>
                            <td>
                                {{ ($subscriber->subscribed_at ?? $subscriber->created_at)?->format('M d, Y g:i A') }}
                                <small class="d-block text-gray-400">{{ ($subscriber->subscribed_at ?? $subscriber->created_at)?->diffForHumans() }}</small>
                            </td>
                            <td class="text-end">
                                <form method="POST" action="{{ route('admin.subscribers.destroy', $subscriber) }}"
                                    onsubmit="return confirm('Remove {{ $subscriber->email }} from the list?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="ghost-button ghost-button--panel">
                                        <i class="fa-solid fa-trash"></i><span>{{ __('Remove') }}</span>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3">
                                <x-admin.empty-state icon="fa-solid fa-envelope-open-text" title="{{ __('No subscribers yet') }}"
                                    message="{{ __('Subscribers captured from the storefront newsletter form will appear here.') }}" />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <x-slot:footer><x-table-footer :paginator="$subscribers" label="{{ __('subscribers') }}" /></x-slot:footer>
        </x-admin.table-card>
    </div>
</x-app-layout>
