<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">Marketing</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">{{ __('Deal Details') }}</h2>
        </div>
    </x-slot>

    <div class="admin-page">
        <div class="deal-show-hero">
            <div class="deal-show-media">
                @if ($deal->image_url)
                    <img src="{{ $deal->image_url }}" alt="{{ $deal->title }}">
                @else
                    <i class="fa-solid fa-tags"></i>
                @endif
            </div>
            <div class="deal-show-copy">
                <span class="deal-type-pill deal-type-pill--{{ $deal->type }}">{{ $deal->type_label }}</span>
                <h3>{{ $deal->title }}</h3>
                <p>{{ $deal->summary ?: 'No campaign summary has been added yet.' }}</p>
                <div class="deal-show-meta">
                    <span><i class="fa-solid fa-calendar"></i>{{ $deal->starts_at?->format('M d, Y H:i') ?? 'Any start' }}</span>
                    <span><i class="fa-solid fa-flag-checkered"></i>{{ $deal->ends_at?->format('M d, Y H:i') ?? 'No end date' }}</span>
                    <span><i class="fa-solid fa-box"></i>{{ $deal->products_count }} products</span>
                </div>
            </div>
            <div class="deal-show-actions">
                <a href="{{ route('admin.deals.edit', $deal) }}" class="premium-button premium-button--dark">
                    <i class="fa-solid fa-pen"></i><span>Edit</span>
                </a>
                <a href="{{ route('admin.deals.index') }}" class="ghost-button ghost-button--panel">
                    <i class="fa-solid fa-arrow-left"></i><span>Back</span>
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mt-4">
            <section class="premium-card deal-detail-card lg:col-span-1">
                <p class="section-kicker mb-3">Campaign settings</p>
                <dl class="deal-detail-list">
                    <div><dt>Status</dt><dd>{{ ucfirst($deal->lifecycle) }}</dd></div>
                    <div><dt>Badge</dt><dd>{{ $deal->badge ?: 'None' }}</dd></div>
                    <div><dt>Discount</dt><dd>
                        @if ($deal->discount_type === 'percentage')
                            {{ rtrim(rtrim(number_format((float) $deal->discount_value, 2), '0'), '.') }}%
                        @elseif ($deal->discount_type === 'fixed')
                            ${{ number_format((float) $deal->discount_value, 2) }}
                        @else
                            Campaign only
                        @endif
                    </dd></div>
                    <div><dt>Priority</dt><dd>{{ $deal->priority }}</dd></div>
                    <div><dt>CTA</dt><dd>{{ $deal->cta_text ?: 'None' }}</dd></div>
                    <div><dt>CTA URL</dt><dd>{{ $deal->cta_url ?: 'None' }}</dd></div>
                </dl>
            </section>

            <section class="premium-card deal-detail-card lg:col-span-2">
                <div class="page-section-header mb-3">
                    <div>
                        <p class="section-kicker">Products</p>
                        <h3>Products in this deal</h3>
                    </div>
                </div>
                <div class="deal-product-grid">
                    @forelse ($products as $product)
                        <article class="deal-product-card">
                            @if ($product->thumbnail_url)
                                <img src="{{ $product->thumbnail_url }}" alt="{{ $product->name }}">
                            @else
                                <span><i class="fa-solid fa-box"></i></span>
                            @endif
                            <div>
                                <strong>{{ $product->name }}</strong>
                                <small>{{ $product->category?->name ?? 'No category' }}</small>
                            </div>
                            <em>${{ number_format((float) $product->price, 2) }}</em>
                        </article>
                    @empty
                        <x-admin.empty-state icon="fa-solid fa-box-open" title="No products attached"
                            message="Edit this campaign and select products to promote." />
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
