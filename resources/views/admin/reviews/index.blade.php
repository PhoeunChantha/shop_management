<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">Catalog</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">{{ __('Reviews') }}</h2>
        </div>
    </x-slot>

    <div class="admin-page" x-data="{ viewOpen: false, vr: {} }">
        <div class="page-section-header">
            <div>
                <p class="section-kicker">Moderation</p>
                <h3>Product reviews</h3>
            </div>
        </div>

        {{-- Status tabs --}}
        @php($current = request('status'))
        <div class="review-tabs">
            <a href="{{ route('admin.reviews.index') }}" class="review-tab {{ $current ? '' : 'is-active' }}">
                All <span>{{ $counts->sum() }}</span>
            </a>
            @foreach (\App\Enums\ReviewStatus::cases() as $st)
                <a href="{{ route('admin.reviews.index', ['status' => $st->value]) }}"
                    class="review-tab {{ $current === $st->value ? 'is-active' : '' }}">
                    <i class="fa-solid {{ $st->icon() }}"></i> {{ $st->label() }}
                    <span>{{ $counts[$st->value] ?? 0 }}</span>
                </a>
            @endforeach
        </div>

        <section class="premium-card mt-3 orders-panel" x-data="bulkSelect()">
            <x-table-loader />

            {{-- Bulk moderation bar --}}
            <div class="bulk-bar" x-show="count > 0" x-cloak>
                <span class="bulk-bar__count"><i class="fa-solid fa-check-double"></i> <span x-text="count"></span> selected</span>
                <form method="POST" action="{{ route('admin.reviews.bulk-moderate') }}" class="bulk-bar__form">
                    @csrf @method('PATCH')
                    <template x-for="id in selected" :key="'ap-' + id"><input type="hidden" name="ids[]" :value="id"></template>
                    <input type="hidden" name="status" value="approved">
                    <button type="submit" class="bulk-btn"><i class="fa-solid fa-circle-check"></i> Approve</button>
                </form>
                <form method="POST" action="{{ route('admin.reviews.bulk-moderate') }}" class="bulk-bar__form">
                    @csrf @method('PATCH')
                    <template x-for="id in selected" :key="'rj-' + id"><input type="hidden" name="ids[]" :value="id"></template>
                    <input type="hidden" name="status" value="rejected">
                    <button type="submit" class="bulk-btn"><i class="fa-solid fa-circle-xmark"></i> Reject</button>
                </form>
                <form method="POST" action="{{ route('admin.reviews.bulk-destroy') }}" class="bulk-bar__form"
                    onsubmit="return confirm('Delete the selected reviews? This cannot be undone.')">
                    @csrf @method('DELETE')
                    <template x-for="id in selected" :key="'dl-' + id"><input type="hidden" name="ids[]" :value="id"></template>
                    <button type="submit" class="bulk-btn bulk-btn--danger"><i class="fa-solid fa-trash"></i> Delete</button>
                </form>
                <button type="button" class="bulk-bar__clear" @click="clear()"><i class="fa-solid fa-xmark"></i> Clear</button>
            </div>

            <x-table-toolbar>
                <x-slot:left>
                    <x-per-page-selector :current="$perPage" />
                </x-slot:left>
                <x-slot:right>
                    <x-search-input name="search" placeholder="Search reviews..." />
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
                            <th>Product</th>
                            <th>Review</th>
                            <th style="width:120px;">Rating</th>
                            <th style="width:120px;">Status</th>
                            <th style="width:110px;">Date</th>
                            <th class="text-end" style="width:96px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($reviews as $review)
                            <tr>
                                <td class="bulk-check-col">
                                    <input type="checkbox" class="bulk-check" data-row-check value="{{ $review->id }}"
                                        x-model="selected" aria-label="Select row">
                                </td>
                                <td>
                                    <div class="orders-cust">
                                        @if ($review->product?->thumbnail)
                                            <img src="{{ Imageurl($review->product->thumbnail, 'products') }}" alt=""
                                                class="rounded-lg object-cover border dark:border-white/10" style="width:36px;height:36px;">
                                        @else
                                            <span class="orders-avatar" style="background:linear-gradient(135deg,#64748b,#334155);border-radius:10px;"><i class="fa-solid fa-box"></i></span>
                                        @endif
                                        <div>
                                            <div class="orders-cust__name">{{ $review->product?->name ?? '—' }}</div>
                                            <div class="orders-cust__email">
                                                {{ $review->author_name }}
                                                @if ($review->is_verified)<i class="fa-solid fa-circle-check text-emerald-500" title="Verified purchase"></i>@endif
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @if ($review->title)<div class="orders-cust__name">{{ $review->title }}</div>@endif
                                    <div class="orders-cust__email">{{ Str::limit($review->body, 70) }}</div>
                                </td>
                                <td>
                                    <span class="review-stars">
                                        @for ($i = 1; $i <= 5; $i++)
                                            <i class="fa-{{ $i <= $review->rating ? 'solid' : 'regular' }} fa-star"></i>
                                        @endfor
                                    </span>
                                </td>
                                <td><span class="status-chip {{ $review->status->badge() }}">{{ $review->status->label() }}</span></td>
                                <td class="dash-table__date">{{ $review->created_at?->format('M d, Y') }}</td>
                                <td class="text-end">
                                    <div class="action-group">
                                        <x-table-actions>
                                            <button type="button" class="table-actions__item" role="menuitem"
                                                @click="vr = {{ Illuminate\Support\Js::from([
                                                    'product' => $review->product?->name ?? '—',
                                                    'author' => $review->author_name,
                                                    'verified' => (bool) $review->is_verified,
                                                    'rating' => (int) $review->rating,
                                                    'title' => $review->title,
                                                    'body' => $review->body,
                                                    'status' => $review->status->label(),
                                                    'date' => $review->created_at?->format('M d, Y g:i A'),
                                                ]) }}; viewOpen = true">
                                                <i class="fa-solid fa-eye"></i><span>View</span>
                                            </button>
                                            @if ($review->status->value !== 'approved')
                                                <form method="POST" action="{{ route('admin.reviews.moderate', $review->id) }}" class="mb-0">
                                                    @csrf @method('PATCH')<input type="hidden" name="status" value="approved">
                                                    <button type="submit" class="table-actions__item" role="menuitem"><i class="fa-solid fa-circle-check text-emerald-500"></i><span>Approve</span></button>
                                                </form>
                                            @endif
                                            @if ($review->status->value !== 'rejected')
                                                <form method="POST" action="{{ route('admin.reviews.moderate', $review->id) }}" class="mb-0">
                                                    @csrf @method('PATCH')<input type="hidden" name="status" value="rejected">
                                                    <button type="submit" class="table-actions__item" role="menuitem"><i class="fa-solid fa-circle-xmark text-amber-500"></i><span>Reject</span></button>
                                                </form>
                                            @endif
                                            <button type="button" class="table-actions__item table-actions__item--danger" role="menuitem"
                                                data-delete-modal-target="deleteReviewModal"
                                                data-delete-action="{{ route('admin.reviews.destroy', $review->id) }}"
                                                data-delete-name="{{ $review->author_name }}'s review">
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
                                        <i class="fa-solid fa-star"></i>
                                        <strong>No reviews found</strong>
                                        <span>Customer reviews will appear here for moderation.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <x-table-footer :paginator="$reviews" label="reviews" />
        </section>

        {{-- View review modal --}}
        <div class="modal-backdrop-premium" x-show="viewOpen" x-cloak style="display:none;"
            @keydown.escape.window="viewOpen = false" @click.self="viewOpen = false">
            <div class="form-modal">
                <div class="form-modal__head">
                    <div class="form-modal__icon"><i class="fa-solid fa-star"></i></div>
                    <div class="flex-grow-1">
                        <h3 x-text="vr.title || 'Review'"></h3>
                        <p><span x-text="vr.product"></span> · <span x-text="vr.status"></span></p>
                    </div>
                    <button type="button" class="form-modal__close" @click="viewOpen = false"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <div class="form-modal__body">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="review-stars" style="font-size:15px;">
                            <template x-for="i in 5" :key="i"><i class="fa-star" :class="i <= vr.rating ? 'fa-solid' : 'fa-regular'"></i></template>
                        </div>
                        <div class="text-sm text-gray-500 dark:text-slate-400">
                            <span x-text="vr.author"></span>
                            <template x-if="vr.verified"><i class="fa-solid fa-circle-check text-emerald-500 ms-1"></i></template>
                            · <span x-text="vr.date"></span>
                        </div>
                    </div>
                    <p class="text-sm text-gray-700 dark:text-slate-300" style="white-space:pre-wrap;" x-text="vr.body"></p>
                </div>
            </div>
        </div>

        <x-delete-confirm-modal id="deleteReviewModal" title="Delete this review?"
            message-after="permanently. The product rating will be recalculated." />
    </div>
</x-app-layout>
