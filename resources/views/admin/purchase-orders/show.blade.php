<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">Restock</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">{{ $purchaseOrder->po_number }}</h2>
        </div>
    </x-slot>

    <div class="admin-page">
        <div class="page-section-header">
            <div>
                <p class="section-kicker">Purchase order</p>
                <h3>{{ $purchaseOrder->supplier?->name }} <span class="status-chip {{ $purchaseOrder->statusBadge() }}">{{ $purchaseOrder->statusLabel() }}</span></h3>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('admin.purchase-orders.index') }}" class="ghost-button ghost-button--panel"><i class="fa-solid fa-arrow-left"></i><span>Back</span></a>
                @if($purchaseOrder->status === 'draft')
                    <form method="POST" action="{{ route('admin.purchase-orders.ordered', $purchaseOrder) }}">@csrf @method('PATCH')
                        <button class="premium-button premium-button--dark"><i class="fa-solid fa-paper-plane"></i><span>Mark ordered</span></button>
                    </form>
                @endif
                @if(in_array($purchaseOrder->status, ['ordered', 'partial'], true))
                    <form method="POST" action="{{ route('admin.purchase-orders.receive', $purchaseOrder) }}">@csrf @method('PATCH')
                        <button class="premium-button premium-button--dark"><i class="fa-solid fa-truck-ramp-box"></i><span>Receive stock</span></button>
                    </form>
                    <form method="POST" action="{{ route('admin.purchase-orders.cancel', $purchaseOrder) }}">@csrf @method('PATCH')
                        <button class="ghost-button ghost-button--danger"><i class="fa-solid fa-ban"></i><span>Cancel</span></button>
                    </form>
                @endif
            </div>
        </div>

        <div class="restock-detail-grid">
            <section class="premium-card restock-summary-card">
                <span><i class="fa-solid fa-file-invoice-dollar"></i></span>
                <div>
                    <p>Order value</p>
                    <strong>${{ number_format((float) $purchaseOrder->subtotal, 2) }}</strong>
                </div>
            </section>
            <section class="premium-card restock-summary-card">
                <span><i class="fa-solid fa-calendar-day"></i></span>
                <div>
                    <p>Expected</p>
                    <strong>{{ $purchaseOrder->expected_at?->format('M d, Y') ?: 'Not set' }}</strong>
                </div>
            </section>
            <section class="premium-card restock-summary-card">
                <span><i class="fa-solid fa-user"></i></span>
                <div>
                    <p>Created by</p>
                    <strong>{{ $purchaseOrder->creator?->name ?? 'System' }}</strong>
                </div>
            </section>
        </div>

        <x-admin.table-card class="restock-table-card mt-3">
            <x-slot:toolbar>
                <div class="table-titlebar"><div><h3>Incoming items</h3><p>{{ $purchaseOrder->items->count() }} line item(s)</p></div></div>
            </x-slot:toolbar>
            <table class="premium-table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>SKU</th>
                        <th>Ordered</th>
                        <th>Received</th>
                        <th>Unit cost</th>
                        <th class="text-end">Line total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($purchaseOrder->items as $item)
                        <tr>
                            <td><strong>{{ $item->name }}</strong></td>
                            <td><span class="dash-table__id">{{ $item->sku ?: '-' }}</span></td>
                            <td>{{ number_format($item->quantity_ordered) }}</td>
                            <td>{{ number_format($item->quantity_received) }}</td>
                            <td>${{ number_format((float) $item->unit_cost, 2) }}</td>
                            <td class="text-end"><strong>${{ number_format((float) $item->line_total, 2) }}</strong></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </x-admin.table-card>

        @if($purchaseOrder->notes)
            <section class="premium-card restock-note-card mt-3">
                <p class="section-kicker">Internal notes</p>
                <p>{{ $purchaseOrder->notes }}</p>
            </section>
        @endif
    </div>
</x-app-layout>
