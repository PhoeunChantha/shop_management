<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">Sales recovery</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">{{ __('Abandoned Cart') }}</h2>
        </div>
    </x-slot>

    <div class="admin-page">
        <div class="page-section-header">
            <div>
                <p class="section-kicker">Cart lead</p>
                <h3>{{ $cart->customer_name ?: 'Guest customer' }} <span class="status-chip {{ $cart->statusBadge() }}">{{ $cart->statusLabel() }}</span></h3>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('admin.abandoned-carts.index') }}" class="ghost-button ghost-button--panel">
                    <i class="fa-solid fa-arrow-left"></i><span>Back</span>
                </a>
                <form method="POST" action="{{ route('admin.abandoned-carts.destroy', $cart) }}" onsubmit="return confirm('Delete this abandoned cart?')">
                    @csrf
                    @method('DELETE')
                    <button class="ghost-button ghost-button--danger"><i class="fa-solid fa-trash"></i><span>Delete</span></button>
                </form>
            </div>
        </div>

        <div class="cart-recovery-detail-grid">
            <section class="premium-card cart-recovery-profile">
                <span><i class="fa-solid fa-user"></i></span>
                <div>
                    <p>Contact</p>
                    <strong>{{ $cart->customer_email ?: 'No email' }}</strong>
                    <small>{{ $cart->customer_phone ?: 'No phone captured' }}</small>
                </div>
            </section>
            <section class="premium-card cart-recovery-profile">
                <span><i class="fa-solid fa-sack-dollar"></i></span>
                <div>
                    <p>Cart value</p>
                    <strong>${{ number_format((float) $cart->subtotal, 2) }}</strong>
                    <small>{{ $cart->item_count }} item(s)</small>
                </div>
            </section>
            <section class="premium-card cart-recovery-profile">
                <span><i class="fa-solid fa-clock"></i></span>
                <div>
                    <p>Last activity</p>
                    <strong>{{ $cart->last_activity_at?->format('M d, Y') ?: 'Unknown' }}</strong>
                    <small>{{ $cart->last_activity_at?->diffForHumans() }}</small>
                </div>
            </section>
        </div>

        <div class="cart-recovery-layout">
            <x-admin.table-card class="cart-recovery-table-card">
                <x-slot:toolbar>
                    <div class="table-titlebar"><div><h3>Cart items</h3><p>Products left behind before checkout</p></div></div>
                </x-slot:toolbar>
                <table class="premium-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>SKU</th>
                            <th>Qty</th>
                            <th>Unit price</th>
                            <th class="text-end">Line total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($cart->items as $item)
                            <tr>
                                <td><strong>{{ $item->name }}</strong></td>
                                <td><span class="dash-table__id">{{ $item->sku ?: '-' }}</span></td>
                                <td>{{ number_format($item->quantity) }}</td>
                                <td>${{ number_format((float) $item->unit_price, 2) }}</td>
                                <td class="text-end"><strong>${{ number_format((float) $item->line_total, 2) }}</strong></td>
                            </tr>
                        @empty
                            <tr><td colspan="5"><x-admin.empty-state icon="fa-solid fa-cart-shopping" title="No cart lines saved" message="Future storefront tracking will populate item-level details." /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </x-admin.table-card>

            <section class="premium-card cart-recovery-workflow">
                <div class="form-section__header">
                    <span class="form-section__icon"><i class="fa-solid fa-headset"></i></span>
                    <div>
                        <p class="section-kicker">Recovery status</p>
                        <h3>Workflow notes</h3>
                    </div>
                </div>
                <form method="POST" action="{{ route('admin.abandoned-carts.update', $cart) }}" class="d-flex flex-column gap-3">
                    @csrf
                    @method('PATCH')
                    <x-select name="status" label="Status" :options="\App\Models\AbandonedCart::STATUSES" :value="$cart->status" required />
                    <div class="form-field">
                        <label for="admin_note">Admin note</label>
                        <textarea name="admin_note" id="admin_note" class="form-input" rows="6" placeholder="Call notes, discount offer, recovery result...">{{ old('admin_note', $cart->admin_note) }}</textarea>
                    </div>
                    <button class="premium-button premium-button--dark w-100 justify-content-center">
                        <i class="fa-solid fa-floppy-disk"></i><span>Save workflow</span>
                    </button>
                </form>
            </section>
        </div>
    </div>
</x-app-layout>
