<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">Marketing</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">{{ __('Offers & Deals') }}</h2>
        </div>
    </x-slot>

    <div class="admin-page">
        <div class="deal-stat-strip">
            <div class="deal-stat"><span>Total</span><strong>{{ number_format($stats['total']) }}</strong></div>
            <div class="deal-stat deal-stat--active"><span>Active</span><strong>{{ number_format($stats['active']) }}</strong></div>
            <div class="deal-stat"><span>Scheduled</span><strong>{{ number_format($stats['scheduled']) }}</strong></div>
            <div class="deal-stat"><span>Expired</span><strong>{{ number_format($stats['expired']) }}</strong></div>
        </div>

        <div class="page-section-header">
            <div>
                <p class="section-kicker">Promotion campaigns</p>
                <h3>All Offers & Deals</h3>
            </div>
            <a href="{{ route('admin.deals.create') }}" class="premium-button premium-button--dark">
                <i class="fa-solid fa-plus"></i>
                <span>New Deal</span>
            </a>
        </div>

        <x-filter-card :action="route('admin.deals.index')">
            <x-select name="type" size="sm" :value="request('type')" placeholder="All deal types" :options="$types" />
            <x-select name="lifecycle" size="sm" :value="request('lifecycle')" placeholder="Any status"
                :options="['active' => 'Active now', 'scheduled' => 'Scheduled', 'expired' => 'Expired', 'disabled' => 'Disabled']" />
            <x-search-input name="search" placeholder="Search title, badge or summary..." />
        </x-filter-card>

        <x-admin.table-card bulk>
            <x-slot:bulkBar>
                <x-bulk-bar :destroy="route('admin.deals.bulk-destroy')" :status="route('admin.deals.bulk-status')" noun="deal campaign" />
            </x-slot:bulkBar>

            <x-slot:toolbar>
                <x-table-toolbar>
                    <x-slot:left>
                        <x-per-page-selector :current="$perPage" />
                    </x-slot:left>
                </x-table-toolbar>
            </x-slot:toolbar>

            <table class="premium-table">
                <thead>
                    <tr>
                        <th class="bulk-check-col">
                            <input type="checkbox" class="bulk-check" @change="toggleAll($event)"
                                :checked="allChecked" x-effect="$el.indeterminate = someChecked" aria-label="Select all">
                        </th>
                        <th>Campaign</th>
                        <th>Type</th>
                        <th>Discount</th>
                        <th>Window</th>
                        <th>Products</th>
                        <th>Status</th>
                        <th class="text-end" style="width:150px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($deals as $deal)
                        <tr>
                            <td class="bulk-check-col">
                                <input type="checkbox" class="bulk-check" data-row-check value="{{ $deal->id }}"
                                    x-model="selected" aria-label="Select row">
                            </td>
                            <td>
                                <div class="deal-table-campaign">
                                    @if ($deal->image_url)
                                        <img src="{{ $deal->image_url }}" alt="">
                                    @else
                                        <span><i class="fa-solid fa-tags"></i></span>
                                    @endif
                                    <div>
                                        <strong>{{ $deal->title }}</strong>
                                        <small>{{ $deal->badge ?: 'No badge' }}</small>
                                    </div>
                                </div>
                            </td>
                            <td><span class="deal-type-pill deal-type-pill--{{ $deal->type }}">{{ $deal->type_label }}</span></td>
                            <td>
                                @if ($deal->discount_type === 'percentage')
                                    <strong>{{ rtrim(rtrim(number_format((float) $deal->discount_value, 2), '0'), '.') }}%</strong>
                                @elseif ($deal->discount_type === 'fixed')
                                    <strong>${{ number_format((float) $deal->discount_value, 2) }}</strong>
                                @else
                                    <span class="text-muted">Campaign only</span>
                                @endif
                            </td>
                            <td>
                                <span class="date-text">
                                    {{ $deal->starts_at?->format('M d, Y H:i') ?? 'Any time' }}
                                    <br>
                                    {{ $deal->ends_at?->format('M d, Y H:i') ?? 'No end date' }}
                                </span>
                            </td>
                            <td><span class="status-pill status-pill--neutral">{{ $deal->products_count }} selected</span></td>
                            <td>
                                <span @class([
                                    'status-chip',
                                    'st-active' => $deal->lifecycle === 'active',
                                    'st-draft' => $deal->lifecycle === 'scheduled',
                                    'st-inactive' => $deal->lifecycle === 'expired',
                                    'st-archived' => $deal->lifecycle === 'disabled',
                                ])>{{ ucfirst($deal->lifecycle) }}</span>
                            </td>
                            <td>
                                <div class="action-group">
                                    <x-table-actions>
                                        <a href="{{ route('admin.deals.show', $deal) }}" class="table-actions__item" role="menuitem">
                                            <i class="fa-solid fa-eye"></i><span>View</span>
                                        </a>
                                        <a href="{{ route('admin.deals.edit', $deal) }}" class="table-actions__item table-actions__item--edit" role="menuitem">
                                            <i class="fa-solid fa-pen"></i><span>Edit</span>
                                        </a>
                                        <button type="button" class="table-actions__item table-actions__item--danger" role="menuitem"
                                            data-delete-modal-target="deleteDealModal"
                                            data-delete-action="{{ route('admin.deals.destroy', $deal) }}"
                                            data-delete-name="{{ $deal->title }}">
                                            <i class="fa-solid fa-trash"></i><span>Delete</span>
                                        </button>
                                    </x-table-actions>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <x-admin.empty-state icon="fa-solid fa-tags" title="No deals found"
                                    message="Create a flash deal, daily offer, featured campaign, or clearance sale." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <x-slot:footer>
                <x-table-footer :paginator="$deals" label="deals" />
            </x-slot:footer>
        </x-admin.table-card>

        <x-delete-confirm-modal
            id="deleteDealModal"
            title="Delete this deal?"
            message-after="from Offers & Deals. This cannot be undone." />
    </div>
</x-app-layout>
