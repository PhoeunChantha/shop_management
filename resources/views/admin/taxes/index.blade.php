<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">Configuration</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">{{ __('Tax Rules') }}</h2>
        </div>
    </x-slot>

    <div class="admin-page">
        <div class="page-section-header">
            <div>
                <p class="section-kicker">Tax rates</p>
                <h3>Tax rules</h3>
            </div>
            <a href="{{ route('admin.taxes.create') }}" class="premium-button premium-button--dark">
                <i class="fa-solid fa-plus"></i><span>New Tax Rule</span>
            </a>
        </div>

        <section class="premium-card mt-3 orders-panel" x-data="bulkSelect()">
            <x-table-loader />
            <x-bulk-bar :destroy="route('admin.taxes.bulk-destroy')" :status="route('admin.taxes.bulk-status')" noun="rule" />

            <x-table-toolbar>
                <x-slot:left>
                    <x-per-page-selector :current="$perPage" />
                </x-slot:left>
                <x-slot:right>
                    <x-search-input name="search" placeholder="Search tax rules..." />
                </x-slot:right>
            </x-table-toolbar>

            <div class="premium-table-wrap">
                <table class="dash-table">
                    <thead>
                        <tr>
                            <th class="bulk-check-col">
                                <input type="checkbox" class="bulk-check" @change="toggleAll($event)"
                                    :checked="allChecked" x-effect="$el.indeterminate = someChecked" aria-label="Select all">
                            </th>
                            <th>Name</th>
                            <th style="width:100px;">Rate</th>
                            <th style="width:150px;">Pricing</th>
                            <th>Country</th>
                            <th style="width:80px;">Order</th>
                            <th style="width:120px;">Status</th>
                            <th class="text-end" style="width:96px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rules as $rule)
                            <tr>
                                <td class="bulk-check-col">
                                    <input type="checkbox" class="bulk-check" data-row-check value="{{ $rule->id }}"
                                        x-model="selected" aria-label="Select row">
                                </td>
                                <td><div class="orders-cust__name">{{ $rule->name }}</div></td>
                                <td class="dash-table__amt">{{ rtrim(rtrim(number_format($rule->rate, 2), '0'), '.') }}%</td>
                                <td>
                                    <span class="status-chip {{ $rule->is_inclusive ? 'st-new' : 'st-draft' }}">
                                        {{ $rule->is_inclusive ? 'Inclusive' : 'Exclusive' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="text-sm text-gray-600 dark:text-slate-300">{{ $rule->country ?: 'Everywhere' }}</span>
                                </td>
                                <td style="font-variant-numeric:tabular-nums;">{{ $rule->sort_order }}</td>
                                <td>
                                    <span class="status-chip {{ $rule->status ? 'st-active' : 'st-inactive' }}">
                                        {{ $rule->status ? 'Enabled' : 'Disabled' }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="action-group">
                                        <x-table-actions>
                                            <a href="{{ route('admin.taxes.edit', $rule->id) }}"
                                                class="table-actions__item table-actions__item--edit" role="menuitem">
                                                <i class="fa-solid fa-pen"></i><span>Edit</span>
                                            </a>
                                            <button type="button" class="table-actions__item table-actions__item--danger" role="menuitem"
                                                data-delete-modal-target="deleteTaxModal"
                                                data-delete-action="{{ route('admin.taxes.destroy', $rule->id) }}"
                                                data-delete-name="{{ $rule->name }}">
                                                <i class="fa-solid fa-trash"></i><span>Delete</span>
                                            </button>
                                        </x-table-actions>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8">
                                    <div class="empty-state">
                                        <i class="fa-solid fa-percent"></i>
                                        <strong>No tax rules yet</strong>
                                        <span>Add a tax rate applied to orders at checkout.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <x-table-footer :paginator="$rules" label="rules" />
        </section>

        <x-delete-confirm-modal id="deleteTaxModal" title="Delete this tax rule?"
            message-after="from checkout. This cannot be undone." />
    </div>
</x-app-layout>
