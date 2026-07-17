<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">Restock</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">{{ __('Purchase Orders') }}</h2>
        </div>
    </x-slot>

    <div class="admin-page">
        <div class="restock-stat-strip">
            <div class="restock-stat"><span>Total POs</span><strong>{{ number_format($stats['total']) }}</strong></div>
            <div class="restock-stat restock-stat--active"><span>Open</span><strong>{{ number_format($stats['open']) }}</strong></div>
            <div class="restock-stat"><span>Received</span><strong>{{ number_format($stats['received']) }}</strong></div>
            <div class="restock-stat"><span>Ordered value</span><strong>${{ number_format($stats['value'], 0) }}</strong></div>
        </div>

        <div class="page-section-header">
            <div>
                <p class="section-kicker">Purchasing workflow</p>
                <h3>Supplier Restock Orders</h3>
            </div>
            <a href="{{ route('admin.purchase-orders.create') }}" class="premium-button premium-button--dark">
                <i class="fa-solid fa-plus"></i><span>New Purchase Order</span>
            </a>
        </div>

        <x-filter-card :action="route('admin.purchase-orders.index')" class="restock-filter-card">
            <x-select name="status" size="sm" :value="request('status')" placeholder="Any status" :options="\App\Models\PurchaseOrder::STATUSES" />
            <x-select name="supplier_id" size="sm" :value="request('supplier_id')" placeholder="Any supplier" :options="$suppliers" optionValue="id" optionLabel="name" searchable />
        </x-filter-card>

        <x-admin.table-card class="restock-table-card">
            <x-slot:toolbar>
                <x-table-toolbar>
                    <x-slot:left><x-per-page-selector :current="$perPage" /></x-slot:left>
                    <x-slot:right><x-search-input name="search" placeholder="Search PO number or supplier..." /></x-slot:right>
                </x-table-toolbar>
            </x-slot:toolbar>

            <table class="premium-table">
                <thead>
                    <tr>
                        <th>PO</th>
                        <th>Supplier</th>
                        <th>Items</th>
                        <th>Expected</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($purchaseOrders as $po)
                        <tr>
                            <td><strong>{{ $po->po_number }}</strong><small class="d-block text-gray-400">{{ $po->ordered_at?->format('M d, Y') ?: 'Not ordered' }}</small></td>
                            <td>{{ $po->supplier?->name }}</td>
                            <td><span class="count-pill">{{ $po->items_count }}</span></td>
                            <td>{{ $po->expected_at?->format('M d, Y') ?: '-' }}</td>
                            <td><strong>${{ number_format((float) $po->subtotal, 2) }}</strong></td>
                            <td><span class="status-chip {{ $po->statusBadge() }}">{{ $po->statusLabel() }}</span></td>
                            <td class="text-end">
                                <a href="{{ route('admin.purchase-orders.show', $po) }}" class="ghost-button ghost-button--panel">
                                    <i class="fa-solid fa-eye"></i><span>View</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><x-admin.empty-state icon="fa-solid fa-clipboard-list" title="No purchase orders found" message="Create purchase orders to plan incoming inventory." /></td></tr>
                    @endforelse
                </tbody>
            </table>

            <x-slot:footer><x-table-footer :paginator="$purchaseOrders" label="purchase orders" /></x-slot:footer>
        </x-admin.table-card>
    </div>
</x-app-layout>
