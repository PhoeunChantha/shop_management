<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">Restock</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">{{ __('Suppliers') }}</h2>
        </div>
    </x-slot>

    <div class="admin-page">
        <div class="restock-stat-strip">
            <div class="restock-stat"><span>Total suppliers</span><strong>{{ number_format($stats['total']) }}</strong></div>
            <div class="restock-stat restock-stat--active"><span>Active</span><strong>{{ number_format($stats['active']) }}</strong></div>
            <div class="restock-stat"><span>Inactive</span><strong>{{ number_format($stats['inactive']) }}</strong></div>
        </div>

        <div class="page-section-header">
            <div>
                <p class="section-kicker">Vendor network</p>
                <h3>Supplier Directory</h3>
            </div>
            <a href="{{ route('admin.suppliers.create') }}" class="premium-button premium-button--dark">
                <i class="fa-solid fa-plus"></i><span>New Supplier</span>
            </a>
        </div>

        <x-filter-card :action="route('admin.suppliers.index')" class="restock-filter-card">
            <x-select name="status" size="sm" :value="request('status')" placeholder="Any status" :options="['1' => 'Active', '0' => 'Inactive']" />
        </x-filter-card>

        <x-admin.table-card class="restock-table-card">
            <x-slot:toolbar>
                <x-table-toolbar>
                    <x-slot:left><x-per-page-selector :current="$perPage" /></x-slot:left>
                    <x-slot:right><x-search-input name="search" placeholder="Search supplier, contact, email..." /></x-slot:right>
                </x-table-toolbar>
            </x-slot:toolbar>

            <table class="premium-table">
                <thead>
                    <tr>
                        <th>Supplier</th>
                        <th>Contact</th>
                        <th>Purchase orders</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
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
                                    <a href="{{ route('admin.suppliers.edit', $supplier) }}" class="ghost-button ghost-button--panel"><i class="fa-solid fa-pen"></i><span>Edit</span></a>
                                    <form method="POST" action="{{ route('admin.suppliers.destroy', $supplier) }}" onsubmit="return confirm('Delete this supplier?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="ghost-button ghost-button--danger"><i class="fa-solid fa-trash"></i><span>Delete</span></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><x-admin.empty-state icon="fa-solid fa-truck-field" title="No suppliers found" message="Create suppliers before building purchase orders." /></td></tr>
                    @endforelse
                </tbody>
            </table>

            <x-slot:footer><x-table-footer :paginator="$suppliers" label="suppliers" /></x-slot:footer>
        </x-admin.table-card>
    </div>
</x-app-layout>
