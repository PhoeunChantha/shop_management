<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">{{ __('Restock') }}</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">{{ __('Suppliers') }}</h2>
        </div>
    </x-slot>

    <div class="admin-page">
        <div class="restock-stat-strip">
            <div class="restock-stat"><span>{{ __('Total suppliers') }}</span><strong>{{ number_format($stats['total']) }}</strong></div>
            <div class="restock-stat restock-stat--active"><span>{{ __('Active') }}</span><strong>{{ number_format($stats['active']) }}</strong></div>
            <div class="restock-stat"><span>{{ __('Inactive') }}</span><strong>{{ number_format($stats['inactive']) }}</strong></div>
        </div>

        <div class="page-section-header">
            <div>
                <p class="section-kicker">{{ __('Vendor network') }}</p>
                <h3>{{ __('Supplier Directory') }}</h3>
            </div>
            <a href="{{ route('admin.suppliers.create') }}" class="premium-button premium-button--dark">
                <i class="fa-solid fa-plus"></i><span>{{ __('New Supplier') }}</span>
            </a>
        </div>

        <x-filter-card :action="route('admin.suppliers.index')" class="restock-filter-card" :grid="'grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-3'">
            <x-slot:hidden>
                <input type="hidden" name="search" value="{{ request('search') }}">
                <input type="hidden" name="per_page" value="{{ $perPage }}">
            </x-slot:hidden>

            <x-select name="status" size="sm" :label="__('Status')" :value="request('status')" :placeholder="__('Any status')"
                :options="['1' => __('Active'), '0' => __('Inactive')]" />

            <x-select name="orders" size="sm" :label="__('Purchase orders')" :value="request('orders')" :placeholder="__('Any activity')"
                :options="collect($orderFilters)->map(fn ($l) => __($l))->all()" />

            <x-select name="contact" size="sm" :label="__('Contact details')" :value="request('contact')" :placeholder="__('Any')"
                :options="collect($contactFilters)->map(fn ($l) => __($l))->all()" />

            <div class="form-field">
                <label>{{ __('Added') }}</label>
                <div class="daterange-control">
                    <i class="fa-regular fa-calendar"></i>
                    <input type="text" class="form-input" data-daterange placeholder="{{ __('Any date') }}" readonly autocomplete="off"
                        value="{{ request('date_from') && request('date_to') ? \Illuminate\Support\Carbon::parse(request('date_from'))->format('M d, Y').' – '.\Illuminate\Support\Carbon::parse(request('date_to'))->format('M d, Y') : '' }}">
                </div>
                <input type="hidden" name="date_from" value="{{ request('date_from') }}">
                <input type="hidden" name="date_to" value="{{ request('date_to') }}">
            </div>

            <x-select name="sort" size="sm" :label="__('Sort by')" :value="request('sort', 'newest')" placeholder=""
                :options="collect($sortOptions)->map(fn ($l) => __($l))->all()" />
        </x-filter-card>

        <x-admin.table-card class="restock-table-card">
            <x-slot:toolbar>
                <x-table-toolbar>
                    <x-slot:left><x-per-page-selector :current="$perPage" /></x-slot:left>
                    <x-slot:right><x-search-input name="search" placeholder="{{ __('Search supplier, contact, email...') }}" /></x-slot:right>
                </x-table-toolbar>
            </x-slot:toolbar>

            <table class="premium-table">
                <thead>
                    <tr>
                        <th>{{ __('Supplier') }}</th>
                        <th>{{ __('Contact') }}</th>
                        <th>{{ __('Purchase orders') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th class="text-end">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($suppliers as $supplier)
                        <tr>
                            <td><strong>{{ $supplier->name }}</strong><small class="d-block text-gray-400">{{ $supplier->address ?: 'No address' }}</small></td>
                            <td>{{ $supplier->contact_name ?: 'No contact' }}<small class="d-block text-gray-400">{{ $supplier->email ?: $supplier->phone }}</small></td>
                            <td><span class="count-pill">{{ $supplier->purchase_orders_count }}</span></td>
                            <td><span class="status-chip {{ $supplier->status ? 'st-active' : 'st-inactive' }}">{{ $supplier->status ? 'Active' : 'Inactive' }}</span></td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-2">
                                    <a href="{{ route('admin.suppliers.edit', $supplier) }}" class="ghost-button ghost-button--panel"><i class="fa-solid fa-pen"></i><span>{{ __('Edit') }}</span></a>
                                    <form method="POST" action="{{ route('admin.suppliers.destroy', $supplier) }}" onsubmit="return confirm('Delete this supplier?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="ghost-button ghost-button--danger"><i class="fa-solid fa-trash"></i><span>{{ __('Delete') }}</span></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><x-admin.empty-state icon="fa-solid fa-truck-field" title="{{ __('No suppliers found') }}" message="{{ __('Create suppliers before building purchase orders.') }}" /></td></tr>
                    @endforelse
                </tbody>
            </table>

            <x-slot:footer><x-table-footer :paginator="$suppliers" label="{{ __('suppliers') }}" /></x-slot:footer>
        </x-admin.table-card>
    </div>
</x-app-layout>
