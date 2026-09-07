<?php

namespace App\Http\Controllers\Frontend;

use App\Enums\ReviewStatus;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductSpecification;
use App\Models\Review;
use App\Services\Admin\SettingService;
use App\Services\Frontend\ProductService;
use App\Services\Frontend\RecentlyViewedService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShopController extends Controller
{
    public function __construct(
        private readonly ProductService $products,
        private readonly SettingService $settings,
        private readonly RecentlyViewedService $recentlyViewed,
    ) {}

    public function index(Request $request): View|JsonResponse
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:100'],
            'subcategory' => ['nullable', 'string', 'max:100'],
            'brand' => ['nullable', 'string', 'max:100'],
            'sizes' => ['nullable', 'string', 'max:200'],
            'colors' => ['nullable', 'string', 'max:200'],
            'max_price' => ['nullable', 'numeric', 'min:0'],
            'sort' => ['nullable', 'in:featured,newest,low,high,rated'],
        ]);

        [$minPrice, $maxPrice] = $this->products->priceRange();

        $filters = [
            'q' => $validated['q'] ?? null,
            'category' => $validated['category'] ?? null,
            'subcategory' => $validated['subcategory'] ?? null,
            'brand' => $validated['brand'] ?? null,
            'sizes' => array_values(array_filter(explode(',', (string) ($validated['sizes'] ?? '')))),
            'colors' => array_values(array_filter(explode(',', (string) ($validated['colors'] ?? '')))),
            'sale' => $request->boolean('sale'),
            'new' => $request->boolean('new'),
            'best' => $request->boolean('best'),
            'max_price' => $validated['max_price'] ?? null,
            'sort' => $validated['sort'] ?? 'featured',
        ];

        // A category landing (?category=slug-or-name) uses that category's SEO fields.
        $activeCategory = filled($filters['category']) && $filters['category'] !== 'All'
            ? Category::query()->where('status', true)->whereSlugOrName((string) $filters['category'])->first()
            : null;

        $seo = [
            'title' => 'Shop all — '.$this->settings->siteName(),
            'description' => 'Browse the full collection — premium heavyweight tees, hoodies and streetwear essentials.',
            'canonical' => route('frontend.shop.index'),
        ];

        if ($activeCategory) {
            $seo = [
                'title' => ($activeCategory->seo_title ?: $activeCategory->name).' — '.$this->settings->siteName(),
                'description' => $activeCategory->seo_description ?: strip_tags((string) $activeCategory->description) ?: $seo['description'],
                'image' => $activeCategory->seo_image ? Imageurl($activeCategory->seo_image, 'categories') : ($activeCategory->image ? Imageurl($activeCategory->image, 'categories') : null),
                'canonical' => route('frontend.shop.index', ['category' => $activeCategory->slug ?: $activeCategory->id]),
            ];
        }

        $data = [
            'products' => $this->products->filteredProducts($filters),
            'catalogTotal' => $this->products->activeCount(),
            'categories' => $this->products->categoryFacets(),
            'brands' => $this->products->brandFacets(),
            'sizes' => $this->products->sizes(),
            'colors' => $this->products->colors(),
            'minPrice' => $minPrice,
            'maxPrice' => $maxPrice,
            'filters' => $filters,
            'activeCat' => $filters['category'] ?: 'All',
            'activeSub' => $filters['subcategory'] ?: 'All',
            'activeBrand' => $filters['brand'] ?: 'All',
            'activeSizes' => $filters['sizes'],
            'activeColors' => $filters['colors'],
            'priceValue' => $filters['max_price'] !== null ? (int) $filters['max_price'] : $maxPrice,
            'activeCategory' => $activeCategory,
            'seo' => $seo,
        ];

        // The sidebar + results partials are also rendered standalone here so
        // filter/pagination clicks can swap them in without a full reload.
        if ($request->ajax()) {
            return response()->json([
                'sidebar' => view('frontend.shop._sidebar', $data)->render(),
                'results' => view('frontend.shop._results', $data)->render(),
            ]);
        }

        return view('frontend.shop.index', $data);
    }

    /**
     * Live product search for the header dropdown (JSON). Debounced client-side.
     */
    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate(['q' => ['nullable', 'string', 'max:100']]);

        return response()->json([
            'results' => $this->products->searchSuggestions((string) ($validated['q'] ?? '')),
        ]);
    }

    public function show(string $product): View|RedirectResponse
    {
        $dynamicProduct = Product::query()
            ->with($this->products->relations())
            ->withSum('variants', 'stock')
            ->where('status', 'active')
            ->where('slug', $product)
            ->first();

        if (! $dynamicProduct && ctype_digit($product)) {
            $dynamicProduct = Product::query()
                ->with($this->products->relations())
                ->withSum('variants', 'stock')
                ->where('status', 'active')
                ->find((int) $product);

            if ($dynamicProduct) {
                return redirect()->route('frontend.shop.show', $this->products->slug($dynamicProduct));
            }
        }

        if (! $dynamicProduct) {
            $dynamicProduct = Product::query()
                ->with($this->products->relations())
                ->withSum('variants', 'stock')
                ->where('status', 'active')
                ->get()
                ->first(fn (Product $item): bool => $this->products->slug($item) === $product);
        }

        if (! $dynamicProduct) {
            abort(404);
        }

        $product = $this->products->map($dynamicProduct);
        $related = $this->products->relatedProducts($dynamicProduct, 4)->all();

        $reviews = Review::query()
            ->with('user:id,name')
            ->where('product_id', $dynamicProduct->id)
            ->where('status', ReviewStatus::Approved->value)
            ->latest()
            ->limit(6)
            ->get()
            ->map(fn (Review $review): array => [
                'name' => $review->author_name ?: $review->user?->name ?: 'Customer',
                'city' => $review->is_verified ? 'Verified buyer' : 'Customer',
                'rating' => $review->rating,
                'text' => $review->body ?: $review->title ?: '',
                'verified' => (bool) $review->is_verified,
            ])
            ->values()
            ->all();

        // Recently-viewed rail from BEFORE we record this one, then record it.
        $recentlyViewed = $this->recentlyViewed->products(8, $dynamicProduct->id);
        $this->recentlyViewed->record($dynamicProduct->id);

        return view('frontend.shop.show', [
            'product' => $product,
            'related' => $related,
            'colors' => $product['color_map'] ?? $this->products->colors(),
            'reviews' => $reviews,
            'specifications' => $dynamicProduct->specifications
                ->map(fn (ProductSpecification $spec): array => ['name' => $spec->name, 'value' => $spec->value])
                ->all(),
            'shippingInfo' => $this->settings->shippingInfo(),
            'recentlyViewed' => $recentlyViewed,
            'seo' => [
                'title' => (($dynamicProduct->seo_title ?: $product['name'])).' — '.$this->settings->siteName(),
                'description' => $dynamicProduct->seo_description ?: strip_tags((string) ($product['desc'] ?? '')),
                'image' => $product['image_url'] ?? null,
                'type' => 'product',
                'canonical' => route('frontend.shop.show', $product['slug']),
            ],
        ]);
    }
}
