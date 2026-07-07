<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">Catalog</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">
                {{ __('Attributes') }}
            </h2>
        </div>
    </x-slot>

    <div class="admin-page">
        <div class="page-section-header">
            <div>
                <p class="section-kicker">Attribute table</p>
                <h3>Product Attributes</h3>
            </div>
            <a href="{{ route('admin.attributes.create') }}" class="premium-button premium-button--dark">
                <i class="fa-solid fa-plus"></i>
                <span>New Attribute</span>
            </a>
        </div>

        <section class="premium-card" x-data="bulkSelect()">
            <x-table-loader />
            <x-bulk-bar :destroy="route('admin.attributes.bulk-destroy')" :status="route('admin.attributes.bulk-status')" noun="attribute" />

            <x-table-toolbar>
                <x-slot:left>
                    <x-per-page-selector :current="$perPage" />
                </x-slot:left>
                <x-slot:right>
                    <x-search-input name="search" placeholder="Search attributes..." />
                </x-slot:right>
            </x-table-toolbar>

            <div class="premium-table-wrap">
                <table class="premium-table">
                    <thead>
                        <tr>
                            <th class="bulk-check-col">
                                <input type="checkbox" class="bulk-check" @change="toggleAll($event)"
                                    :checked="allChecked" x-effect="$el.indeterminate = someChecked" aria-label="Select all">
                            </th>
                            <th style="width:70px;">ID</th>
                            <th>Attribute</th>
                            <th>Values</th>
                            <th style="width:110px;">Count</th>
                            <th style="width:120px;">Status</th>
                            <th class="text-end" style="width:150px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($attributes as $attribute)
                        <tr>
                            <td class="bulk-check-col">
                                <input type="checkbox" class="bulk-check" data-row-check value="{{ $attribute->id }}"
                                    x-model="selected" aria-label="Select row">
                            </td>
                            <td><span class="muted-id">#{{ $attribute->id }}</span></td>
                            <td><strong class="text-gray-900 dark:text-slate-100">{{ $attribute->name }}</strong></td>
                            <td>
                                <div class="d-flex flex-wrap gap-1" style="max-width: 520px;">
                                    @foreach ($attribute->values->take(10) as $value)
                                        <span class="attr-value-pill">
                                            @if ($value->color_hex)
                                                <span class="attr-swatch" style="background: {{ $value->color_hex }};"></span>
                                            @endif
                                            {{ $value->value }}
                                        </span>
                                    @endforeach
                                    @if ($attribute->values_count > 10)
                                        <span class="attr-value-pill">+{{ $attribute->values_count - 10 }}</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <span class="px-2 py-1 bg-gray-100 text-gray-800 text-xs font-semibold rounded border border-gray-200 dark:bg-white/10 dark:text-slate-200 dark:border-white/10">
                                    {{ $attribute->values_count }}
                                </span>
                            </td>
                            <td>
                                @if($attribute->status)
                                <span class="text-green-600 bg-green-50 px-2 py-1 rounded text-xs font-medium border border-green-200 dark:text-emerald-300 dark:bg-emerald-500/10 dark:border-emerald-500/20">Enabled</span>
                                @else
                                <span class="text-red-600 bg-red-50 px-2 py-1 rounded text-xs font-medium border border-red-200 dark:text-red-300 dark:bg-red-500/10 dark:border-red-500/20">Disabled</span>
                                @endif
                            </td>
                            <td>
                                <div class="action-group">
                                    <x-table-actions>
                                        <a href="{{ route('admin.attributes.edit', $attribute->id) }}" class="table-actions__item table-actions__item--edit" role="menuitem">
                                            <i class="fa-solid fa-pen"></i><span>Edit</span>
                                        </a>
                                        <button type="button" class="table-actions__item table-actions__item--danger" role="menuitem"
                                            data-delete-modal-target="deleteAttributeModal"
                                            data-delete-action="{{ route('admin.attributes.destroy', $attribute->id) }}"
                                            data-delete-name="{{ $attribute->name }}">
                                            <i class="fa-solid fa-trash"></i><span>Delete</span>
                                        </button>
                                    </x-table-actions>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7">
                                <div class="empty-state">
                                    <i class="fa-solid fa-tags"></i>
                                    <strong>No attributes found</strong>
                                    <span>Create attributes like Size, Color, Material or Storage to build product variants.</span>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <x-table-footer :paginator="$attributes" label="attributes" />
        </section>

        <x-delete-confirm-modal
            id="deleteAttributeModal"
            title="Delete this attribute?"
            message-after="and all of its values. Variants using it will lose that attribute. This cannot be undone." />
    </div>
</x-app-layout>
