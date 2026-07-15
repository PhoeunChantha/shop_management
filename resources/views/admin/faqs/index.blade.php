<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">Content</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">{{ __('FAQ') }}</h2>
        </div>
    </x-slot>

    <div class="admin-page">
        <div class="page-section-header">
            <div>
                <p class="section-kicker">Questions &amp; answers</p>
                <h3>FAQ</h3>
            </div>
            <a href="{{ route('admin.faqs.create') }}" class="premium-button premium-button--dark">
                <i class="fa-solid fa-plus"></i><span>New FAQ</span>
            </a>
        </div>

        <x-admin.table-card class="mt-3 orders-panel" bulk>
            <x-slot:bulkBar>
                <x-bulk-bar :destroy="route('admin.faqs.bulk-destroy')" :status="route('admin.faqs.bulk-status')" noun="FAQ" />
            </x-slot:bulkBar>

            <x-slot:toolbar>
                <x-table-toolbar>
                    <x-slot:left>
                        <x-per-page-selector :current="$perPage" />
                    </x-slot:left>
                    <x-slot:right>
                        <x-search-input name="search" placeholder="Search questions..." />
                    </x-slot:right>
                </x-table-toolbar>
            </x-slot:toolbar>

                <table class="dash-table">
                    <thead>
                        <tr>
                            <th class="bulk-check-col">
                                <input type="checkbox" class="bulk-check" @change="toggleAll($event)"
                                    :checked="allChecked" x-effect="$el.indeterminate = someChecked" aria-label="Select all">
                            </th>
                            <th>Question</th>
                            <th style="width:150px;">Category</th>
                            <th style="width:80px;">Order</th>
                            <th style="width:120px;">Status</th>
                            <th class="text-end" style="width:96px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($faqs as $faq)
                            <tr>
                                <td class="bulk-check-col">
                                    <input type="checkbox" class="bulk-check" data-row-check value="{{ $faq->id }}"
                                        x-model="selected" aria-label="Select row">
                                </td>
                                <td>
                                    <div class="orders-cust__name">{{ $faq->question }}</div>
                                    <div class="orders-cust__email">{{ Str::limit($faq->answer, 70) }}</div>
                                </td>
                                <td>
                                    @if ($faq->category)
                                        <span class="status-chip st-draft">{{ $faq->category }}</span>
                                    @else
                                        <span class="text-gray-400 dark:text-slate-500 text-xs italic">Uncategorized</span>
                                    @endif
                                </td>
                                <td style="font-variant-numeric:tabular-nums;">{{ $faq->sort_order }}</td>
                                <td>
                                    <span class="status-chip {{ $faq->status ? 'st-active' : 'st-inactive' }}">
                                        {{ $faq->status ? 'Enabled' : 'Disabled' }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="action-group">
                                        <x-table-actions>
                                            <a href="{{ route('admin.faqs.edit', $faq->id) }}"
                                                class="table-actions__item table-actions__item--edit" role="menuitem">
                                                <i class="fa-solid fa-pen"></i><span>Edit</span>
                                            </a>
                                            <button type="button" class="table-actions__item table-actions__item--danger" role="menuitem"
                                                data-delete-modal-target="deleteFaqModal"
                                                data-delete-action="{{ route('admin.faqs.destroy', $faq->id) }}"
                                                data-delete-name="{{ Str::limit($faq->question, 30) }}">
                                                <i class="fa-solid fa-trash"></i><span>Delete</span>
                                            </button>
                                        </x-table-actions>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">
                                    <x-admin.empty-state icon="fa-solid fa-circle-question" title="No FAQs yet"
                                        message="Add common questions and answers for your customers." />
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

            <x-slot:footer>
                <x-table-footer :paginator="$faqs" label="faqs" />
            </x-slot:footer>
        </x-admin.table-card>

        <x-delete-confirm-modal id="deleteFaqModal" title="Delete this FAQ?"
            message-after="from the storefront. This cannot be undone." />
    </div>
</x-app-layout>
