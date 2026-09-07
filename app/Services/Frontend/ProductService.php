<?php

namespace App\Services\Frontend;

use App\Enums\OrderStatus;
use App\Helpers\ImageManager;
use App\Models\Category;
use App\Models\Color;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\Size;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ProductService
{
    /**
     * @return array<int, string>
     */
    public function relations(): array
    {
        return [
            'brand:id,name',
            'category:id,name',
            'subCategory:id,name',
            'images:id,product_id,image,is_primary,sort_order',
            'variants:id,product_id,color_id,size_id,price,stock,status',
            'variants.color:id,name,code,hex_code',
            'variants.size:id,name,code',
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function mappedActiveProducts(?int $limit = null): Collection
    {
        $query = Product::query()
            ->with($this->relations())
            ->withSum('variants', 'stock')
            ->where('status', 'active')
            ->orderBy('sort_order')
            ->latest();

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get()
            ->map(fn (Product $product): array => $this->map($product))
            ->values();
    }

    /**
     * Cross-sell picks for cart/upsell blocks: best sellers and featured
     * products first, then the newest active items.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function crossSell(int $limit = 4): Collection
    {
        return Product::query()
            ->with($this->relations())
            ->withSum('variants', 'stock')
            ->where('status', 'active')
            ->orderByDesc('is_best_seller')
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (Product $product): array => $this->map($product))
            ->values();
    }

    /**
     * "You may also like" picks for the PDP: real co-purchase signal first
     * (other products that shipped in the same completed orders as this
     * one), topped up with same-category products when there isn't enough
     * purchase history yet (e.g. a brand-new product).
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function relatedProducts(Product $product, int $limit = 4): Collection
    {
        $picks = $this->frequentlyBoughtWith($product, $limit);

        if ($picks->count() >= $limit) {
            return $picks;
        }

        $excludeIds = $picks->pluck('id')->push($product->id)->all();

        $fallback = Product::query()
            ->with($this->relations())
            ->withSum('variants', 'stock')
            ->where('status', 'active')
            ->where('category_id', $product->category_id)
            ->whereNotIn('id', $excludeIds)
            ->orderBy('sort_order')
            ->latest()
            ->limit($limit - $picks->count())
            ->get()
            ->map(fn (Product $item): array => $this->map($item));

        return $picks->concat($fallback)->values();
    }

    /**
     * Other products most often bought in the same order as $product, across
     * every completed (non-pending, non-cancelled/refunded) order. Empty
     * when the product has no such order history yet.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function frequentlyBoughtWith(Product $product, int $limit = 4): Collection
    {
        $orderIds = OrderDetail::query()
            ->where('product_id', $product->id)
            ->whereHas('order', fn (Builder $query) => $query->whereNotIn('status', [
                OrderStatus::Pending->value,
                OrderStatus::Cancelled->value,
                OrderStatus::Refunded->value,
            ]))
            ->pluck('order_id');

        if ($orderIds->isEmpty()) {
            return collect();
        }

        $productIds = OrderDetail::query()
            ->whereIn('order_id', $orderIds)
            ->where('product_id', '!=', $product->id)
            ->selectRaw('product_id, COUNT(DISTINCT order_id) as co_purchase_count')
            ->groupBy('product_id')
            ->orderByDesc('co_purchase_count')
            ->limit($limit)
            ->pluck('product_id');

        if ($productIds->isEmpty()) {
            return collect();
        }

        $products = Product::query()
            ->with($this->relations())
            ->withSum('variants', 'stock')
            ->whereIn('id', $productIds)
            ->where('status', 'active')
            ->get()
            ->keyBy('id');

        return $productIds
            ->map(fn (int $id) => $products->get($id))
            ->filter()
            ->map(fn (Product $item): array => $this->map($item))
            ->values();
    }

    /**
     * Server-side, paginated storefront listing. Applies search + facet filters
     * in the database (so the full catalog is never loaded into memory) and maps
     * only the current page to the client shape.
     *
     * @param  array<string, mixed>  $filters
     */
    public function filteredProducts(array $filters, int $perPage = 24): LengthAwarePaginator
    {
        return $this->filteredQuery($filters)
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (Product $product): array => $this->map($product));
    }

    /**
     * Total number of active products (catalog size, ignoring filters).
     */
    public function activeCount(): int
    {
        return Product::query()->where('status', 'active')->count();
    }

    /**
     * Category facet tree with per-category and per-subcategory counts across
     * the whole active catalog (stable totals, independent of the active filter).
     * Subcategories come from the admin-managed tree (`parent_id`) — every real
     * sub-category is listed, even ones with zero products yet — not just the
     * ones a product happens to reference.
     *
     * @return array<string, array{count: int, subcategories: array<string, int>}>
     */
    public function categoryFacets(): array
    {
        // Names are translatable JSON, so group by id in SQL and resolve the
        // display name (current locale) through the model afterwards.
        $categories = Category::query()->get(['id', 'parent_id', 'name']);
        $names = $categories->mapWithKeys(fn (Category $c) => [$c->id => (string) $c->name]);
        $childrenByParent = $categories->whereNotNull('parent_id')->groupBy('parent_id');

        $rows = Product::query()
            ->where('products.status', 'active')
            ->selectRaw('products.category_id as category_id, products.sub_category_id as sub_category_id, COUNT(*) as total')
            ->whereNotNull('products.category_id')
            ->groupBy('products.category_id', 'products.sub_category_id')
            ->get()
            ->filter(fn ($row): bool => $names->has((int) $row->category_id));

        $subCategoryCounts = $rows
            ->filter(fn ($row): bool => $row->sub_category_id !== null)
            ->groupBy('sub_category_id')
            ->map(fn (Collection $group): int => (int) $group->sum('total'));

        return $rows
            ->groupBy(fn ($row): string => $names[(int) $row->category_id])
            ->map(function (Collection $group) use ($childrenByParent, $names, $subCategoryCounts): array {
                $categoryId = (int) $group->first()->category_id;

                return [
                    'count' => (int) $group->sum('total'),
                    'subcategories' => $childrenByParent->get($categoryId, collect())
                        ->mapWithKeys(fn (Category $child): array => [
                            $names[$child->id] => (int) ($subCategoryCounts[$child->id] ?? 0),
                        ])
                        ->all(),
                ];
            })
            ->all();
    }

    /**
     * Brand facet counts across the whole active catalog.
     *
     * @return array<string, int>
     */
    public function brandFacets(): array
    {
        return Product::query()
            ->where('products.status', 'active')
            ->join('brands as b', 'b.id', '=', 'products.brand_id')
            ->selectRaw('b.name as brand, COUNT(*) as total')
            ->groupBy('b.name')
            ->orderBy('b.name')
            ->pluck('total', 'brand')
            ->map(fn ($total): int => (int) $total)
            ->all();
    }

    /**
     * Min/max list price across the active catalog, as whole dollars.
     *
     * @return array{0: int, 1: int}
     */
    public function priceRange(): array
    {
        $row = Product::query()
            ->where('status', 'active')
            ->selectRaw('MIN(price) as mn, MAX(price) as mx')
            ->first();

        $min = max(0, (int) floor((float) ($row->mn ?? 0)));
        $max = max($min, (int) ceil((float) ($row->mx ?? 120)));

        return [$min, $max];
    }

    /**
     * Build the filtered listing query from validated storefront filters.
     *
     * @param  array<string, mixed>  $filters
     */
    /**
     * A fresh listing-shaped query (relations + variant-stock sum) scoped to
     * active products. Shared by the listing, home pool and search so the eager
     * loads stay consistent in one place.
     */
    private function activeQuery(): Builder
    {
        return Product::query()
            ->with($this->relations())
            ->withSum('variants', 'stock')
            ->where('status', 'active');
    }

    /**
     * A bounded, deduplicated pool of active products for the homepage rails —
     * best sellers, new, featured, on-sale and newest — so the home page never
     * loads the whole catalog into memory. The PHP sectioning in HomeController
     * curates the individual rails from this pool.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function homePool(int $perBucket = 12): Collection
    {
        return collect([
            $this->activeQuery()->where('is_best_seller', true)->orderBy('sort_order')->latest()->limit($perBucket)->get(),
            $this->activeQuery()->where('is_new', true)->latest()->limit($perBucket)->get(),
            $this->activeQuery()->where('is_featured', true)->orderBy('sort_order')->latest()->limit($perBucket)->get(),
            $this->activeQuery()->where('is_on_sale', true)->latest()->limit($perBucket)->get(),
            $this->activeQuery()->orderBy('sort_order')->latest()->limit($perBucket)->get(),
        ])
            ->flatten(1)
            ->unique('id')
            ->map(fn (Product $product): array => $this->map($product))
            ->values();
    }

    /**
     * Lightweight live-search results for the header search dropdown (AJAX).
     *
     * @return array<int, array{name: string, url: string, price: string, image: ?string}>
     */
    public function searchSuggestions(string $term, int $limit = 6): array
    {
        $term = trim($term);

        if ($term === '') {
            return [];
        }

        return $this->activeQuery()
            ->where(function (Builder $q) use ($term): void {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('sku', 'like', "%{$term}%")
                    ->orWhereHas('brand', fn (Builder $b) => $b->where('name', 'like', "%{$term}%"))
                    ->orWhereHas('category', fn (Builder $c) => $c->where('name', 'like', "%{$term}%"));
            })
            ->orderByDesc('is_best_seller')
            ->orderByDesc('is_featured')
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (Product $product): array => [
                'name' => $product->name,
                'url' => route('frontend.shop.show', $this->slug($product)),
                'price' => dprice((float) $product->final_price),
                'image' => $product->thumbnail_url,
            ])
            ->values()
            ->all();
    }

    private function filteredQuery(array $filters): Builder
    {
        $query = $this->activeQuery();

        if (filled($filters['q'] ?? null)) {
            $term = trim((string) $filters['q']);
            $query->where(function (Builder $q) use ($term): void {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('sku', 'like', "%{$term}%")
                    ->orWhereHas('brand', fn (Builder $b) => $b->where('name', 'like', "%{$term}%"))
                    ->orWhereHas('category', fn (Builder $c) => $c->where('name', 'like', "%{$term}%"));
            });
        }

        // Category URLs may carry a slug (navigation) or a display name (facets, any language).
        if (filled($filters['category'] ?? null) && $filters['category'] !== 'All') {
            $query->whereHas('category', fn (Builder $c) => $c->whereSlugOrName((string) $filters['category']));
        }

        if (filled($filters['subcategory'] ?? null) && $filters['subcategory'] !== 'All') {
            $query->whereHas('subCategory', fn (Builder $c) => $c->whereSlugOrName((string) $filters['subcategory']));
        }

        if (filled($filters['brand'] ?? null) && $filters['brand'] !== 'All') {
            $query->whereHas('brand', fn (Builder $b) => $b->where('name', $filters['brand']));
        }

        if (! empty($filters['sale'])) {
            $query->where(fn (Builder $q) => $q
                ->where('is_on_sale', true)
                ->orWhere(fn (Builder $q2) => $q2
                    ->whereIn('discount_type', ['fixed', 'percentage'])
                    ->where('discount_amount', '>', 0)));
        }

        if (! empty($filters['new'])) {
            $query->where('is_new', true);
        }

        if (! empty($filters['best'])) {
            $query->where('is_best_seller', true);
        }

        if (filled($filters['max_price'] ?? null)) {
            $query->where('price', '<=', (float) $filters['max_price']);
        }

        if ($sizes = array_filter((array) ($filters['sizes'] ?? []))) {
            $query->whereHas('variants.size', fn (Builder $s) => $s->whereIn('code', $sizes));
        }

        if ($colors = array_filter((array) ($filters['colors'] ?? []))) {
            $keys = array_map(fn ($c): string => mb_strtolower((string) $c), $colors);
            $query->whereHas('variants.color', function (Builder $c) use ($keys): void {
                $c->where(function (Builder $q) use ($keys): void {
                    foreach ($keys as $key) {
                        $q->orWhereRaw('LOWER(code) = ?', [$key])
                            ->orWhereRaw('LOWER(name) = ?', [$key]);
                    }
                });
            });
        }

        return match ($filters['sort'] ?? 'featured') {
            'newest' => $query->latest(),
            'low' => $query->orderBy('price'),
            'high' => $query->orderByDesc('price'),
            'rated' => $query->orderByDesc('rating_avg'),
            default => $query->orderBy('sort_order')->latest(),
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function map(Product $product): array
    {
        $variantColors = $product->variants
            ->filter(fn ($variant) => $variant->color)
            ->pluck('color')
            ->unique('id')
            ->values();

        $colorKeys = $variantColors
            ->map(fn (Color $color): string => $this->colorKey($color))
            ->filter()
            ->values()
            ->all();

        $colorMap = $variantColors
            ->mapWithKeys(fn (Color $color): array => [
                $this->colorKey($color) => [
                    'name' => $color->name,
                    'hex' => $color->hex_code ?: '#1a1a1d',
                ],
            ])
            ->all();

        $sizes = $product->variants
            ->filter(fn ($variant) => $variant->size)
            ->pluck('size.code')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $images = $this->images($product);
        $slug = $this->slug($product);

        // Lookup so the client can resolve the exact variant id from the chosen
        // size code + colour key (keys match the `sizes`/`colors` used in the UI).
        $variantIndex = $product->variants
            ->filter(fn ($variant) => $variant->size && $variant->color)
            ->mapWithKeys(fn ($variant): array => [
                mb_strtolower((string) $variant->size->code).'|'.$this->colorKey($variant->color) => $variant->id,
            ])
            ->all();

        return [
            'id' => $product->id,
            'slug' => $slug,
            'url' => route('frontend.shop.show', $slug),
            'name' => $product->name,
            'price' => (float) $product->final_price,
            'was' => $product->has_discount ? (float) $product->price : null,
            'tint' => $this->gradientFor($product->id),
            'dark' => $product->id % 5 === 0,
            'cat' => $product->category?->name ?: 'Products',
            'subcat' => $product->subCategory?->name ?: $product->category?->name ?: 'General',
            'brand' => $product->brand?->name ?: config('app.name'),
            'tag' => $product->is_on_sale || $product->has_discount ? 'sale' : ($product->is_new ? 'new' : null),
            'colors' => $colorKeys ?: array_keys($this->colors()),
            'color_map' => $colorMap ?: $this->colors(),
            'sizes' => $sizes ?: ['One Size'],
            'rating' => (float) ($product->rating_avg ?: 0),
            'reviews' => (int) $product->rating_count,
            'badge' => $product->is_best_seller ? 'Best Seller' : ($product->is_featured ? 'Featured' : null),
            'featured' => $product->is_featured,
            'desc' => $product->short_description ?: $product->description,
            'gallery' => max(1, count($images)),
            'images' => $images,
            'image_url' => $product->thumbnail_url,
            'image_thumb_url' => $product->thumbnail_preview_url,
            'variant_index' => $variantIndex,
        ];
    }

    /**
     * @return array<string, array{name: string, hex: string}>
     */
    public function colors(): array
    {
        $colors = Color::query()
            ->where('status', true)
            ->orderBy('sort_order')
            ->get(['name', 'code', 'hex_code'])
            ->mapWithKeys(fn (Color $color): array => [
                $this->colorKey($color) => [
                    'name' => $color->name,
                    'hex' => $color->hex_code ?: '#1a1a1d',
                ],
            ])
            ->all();

        return $colors ?: $this->defaultColors();
    }

    /**
     * @param  Collection<int, array<string, mixed>>|null  $products
     * @return array<int, string>
     */
    public function sizes(?Collection $products = null): array
    {
        $sizes = Size::query()
            ->where('status', true)
            ->orderBy('sort_order')
            ->pluck('code')
            ->filter()
            ->values()
            ->all();

        if ($sizes) {
            return $sizes;
        }

        $productSizes = $products
            ? $products->pluck('sizes')->flatten()->filter()->unique()->values()->all()
            : [];

        return $productSizes ?: ['One Size'];
    }

    public function slug(Product $product): string
    {
        return $product->slug ?: Str::slug($product->name);
    }

    /**
     * @return array<int, string>
     */
    /**
     * @return array<int, array{url: string, thumb: ?string}>
     */
    private function images(Product $product): array
    {
        $images = $product->images
            ->sortByDesc('is_primary')
            ->map(fn ($image): ?array => $image->image ? [
                'url' => Imageurl($image->image, 'products'),
                'thumb' => ImageManager::thumbnailUrl($image->image, 'products'),
            ] : null)
            ->filter()
            ->values();

        if ($product->thumbnail_url) {
            $images->prepend([
                'url' => $product->thumbnail_url,
                'thumb' => $product->thumbnail_preview_url,
            ]);
        }

        $seen = [];

        return $images->filter(function (array $entry) use (&$seen): bool {
            if (isset($seen[$entry['url']])) {
                return false;
            }

            return $seen[$entry['url']] = true;
        })->values()->all();
    }

    private function colorKey(Color $color): string
    {
        return strtolower($color->code ?: Str::slug($color->name));
    }

    /**
     * @return array<string, array{name: string, hex: string}>
     */
    private function defaultColors(): array
    {
        return [
            'black' => ['name' => 'Black', 'hex' => '#111111'],
            'white' => ['name' => 'White', 'hex' => '#f8f4ec'],
            'navy' => ['name' => 'Navy', 'hex' => '#1f3f8f'],
            'red' => ['name' => 'Red', 'hex' => '#f04444'],
        ];
    }

    private function gradientFor(int $seed): string
    {
        $gradients = [
            'linear-gradient(150deg,#e7e9ee,#cfd4dd)',
            'linear-gradient(150deg,#1f2024,#33353c)',
            'linear-gradient(150deg,#ede6dc,#d8c9b4)',
            'linear-gradient(150deg,#dfe7ee,#bcccdb)',
            'linear-gradient(150deg,#e8e4dd,#cbbfa9)',
            'linear-gradient(150deg,#26282d,#3b3e46)',
        ];

        return $gradients[$seed % count($gradients)];
    }
}
