<?php

namespace App\Http\Controllers\Frontend;

use App\Enums\ReviewStatus;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductSpecification;
use App\Models\Review;
use App\Services\Admin\SettingService;
use App\Services\Frontend\ProductService;
use App\Services\Frontend\RecentlyViewedService;
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

    public function index(Request $request): View
    {
        $products = $this->products->mappedActiveProducts();

        $categories = collect($products)
            ->groupBy('cat')
            ->map(fn ($categoryProducts) => [
                'count' => $categoryProducts->count(),
                'subcategories' => $categoryProducts->groupBy('subcat')->map->count()->all(),
            ])
            ->all();
        $brands = collect($products)->groupBy('brand')->map->count()->all();
        $prices = collect($products)->pluck('price')->filter();
        $minPrice = max(0, (int) floor((float) ($prices->min() ?: 0)));
        $maxPrice = max($minPrice, (int) ceil((float) ($prices->max() ?: 120)));

        return view('frontend.shop.index', [
            'products' => $products->all(),
            'categories' => $categories,
            'brands' => $brands,
            'sizes' => $this->products->sizes($products),
            'colors' => $this->products->colors(),
            'minPrice' => $minPrice,
            'maxPrice' => $maxPrice,
            'seo' => [
                'title' => 'Shop all — '.$this->settings->siteName(),
                'description' => 'Browse the full collection — premium heavyweight tees, hoodies and streetwear essentials.',
                'canonical' => route('frontend.shop.index'),
            ],
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
        $related = Product::query()
            ->with($this->products->relations())
            ->withSum('variants', 'stock')
            ->where('status', 'active')
            ->where('id', '!=', $dynamicProduct->id)
            ->where('category_id', $dynamicProduct->category_id)
            ->orderBy('sort_order')
            ->latest()
            ->limit(4)
            ->get()
            ->map(fn (Product $product): array => $this->products->map($product))
            ->values()
            ->all();

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
