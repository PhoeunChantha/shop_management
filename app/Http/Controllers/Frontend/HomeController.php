<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Banner;
use App\Models\Collection as ProductCollection;
use App\Models\Product;
use App\Support\Catalog;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $products = Product::query()
            ->with([
                'brand:id,name',
                'category:id,name',
                'subCategory:id,name',
                'images:id,product_id,image,is_primary,sort_order',
                'variants:id,product_id,color_id,size_id,price,stock,status',
                'variants.color:id,name,code,hex_code',
                'variants.size:id,name,code',
            ])
            ->withSum('variants', 'stock')
            ->where('status', 'active')
            ->orderBy('sort_order')
            ->latest()
            ->get();

        $mappedProducts = $products->map(fn (Product $product) => $this->mapProduct($product))->values();
        $catalogProducts = collect(Catalog::products());
        $hasDynamicProducts = $mappedProducts->isNotEmpty();
        $homeProducts = $hasDynamicProducts ? $mappedProducts : $catalogProducts;

        return view('frontend.home', [
            'heroSlides' => $this->heroSlides(),
            'best' => $this->section($homeProducts, fn (array $product) => $product['badge'] === 'Best Seller' || $product['reviews'] > 200),
            'fresh' => $this->section($homeProducts, fn (array $product) => $product['tag'] === 'new'),
            'trend' => $this->section($homeProducts, fn (array $product) => (bool) ($product['featured'] ?? false), 4, 4),
            'flash' => $this->section($homeProducts, fn (array $product) => filled($product['was'])),
            'collections' => $this->collections(),
            'reviews' => Catalog::reviews(),
            'marquee' => $this->marquee(),
            'instagramTiles' => $this->instagramTiles($homeProducts, $hasDynamicProducts),
            'trustItems' => [
                ['truck', 'Free shipping', 'On orders over $75'],
                ['refresh', '30-day returns', 'No-questions-asked'],
                ['shield', 'Secure checkout', '256-bit encryption'],
                ['spark', 'Carbon neutral', 'Every delivery'],
            ],
            'newsletter' => [
                'eyebrow' => 'Members get more',
                'title' => 'Get 10% off your first order',
                'copy' => 'Early access to drops, members-only pricing, and free shipping. No spam — just good tees.',
            ],
        ]);
    }

    private function heroSlides(): array
    {
        $slides = Banner::query()
            ->where('status', true)
            ->orderBy('sort_order')
            ->latest()
            ->get()
            ->map(fn (Banner $banner, int $index) => [
                'kicker' => $banner->kicker ?: sprintf('%02d / Featured', $index + 1),
                'title' => nl2br(e($banner->title)),
                'copy' => $banner->subtitle ?: '',
                'primary' => $banner->cta_text ?: 'Shop now',
                'secondary' => 'Explore the edit',
                'trust' => 'Premium essentials',
                'image' => Imageurl($banner->image, 'banners') ?: $banner->image,
                'url' => $banner->cta_link ?: route('frontend.shop.index'),
            ])
            ->values()
            ->all();

        return $slides ?: [
            ['kicker' => '01 / New arrivals', 'title' => 'Premium oversized<br>t-shirts.', 'copy' => 'Built for comfort. Designed for style. Garment-dyed heavyweight cotton with the relaxed structure you reach for every day.', 'primary' => 'Shop new arrivals', 'secondary' => 'Explore the edit', 'trust' => '240gsm organic cotton', 'image' => 'https://images.unsplash.com/photo-1618354691373-d851c5c3a990?auto=format&fit=crop&w=1800&q=88', 'url' => route('frontend.shop.index')],
            ['kicker' => '02 / Best sellers', 'title' => 'The tees<br>everyone loves.', 'copy' => 'The most-reordered fits in our collection. Proven weight, precise proportions, and color that only gets better with time.', 'primary' => 'Shop best sellers', 'secondary' => 'See the reviews', 'trust' => '12k+ five-star reviews', 'image' => 'https://images.unsplash.com/photo-1503342217505-b0a15ec3261c?auto=format&fit=crop&w=1800&q=88', 'url' => route('frontend.shop.index')],
            ['kicker' => '03 / Graphic collection', 'title' => 'Bold designs.<br>Everyday wear.', 'copy' => 'Limited-run artwork meets our signature heavyweight base. Made to be noticed, built to stay in rotation.', 'primary' => 'Shop graphic tees', 'secondary' => 'View lookbook', 'trust' => 'Limited edition print runs', 'image' => 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?auto=format&fit=crop&w=1800&q=88', 'url' => route('frontend.shop.index')],
            ['kicker' => '04 / Flash sale', 'title' => 'Up to 40% off.<br>Last call.', 'copy' => 'Final sizes from past drops. Once a color or size is gone, it is gone. Move fast on the pieces you missed.', 'primary' => 'Shop the sale', 'secondary' => 'View all offers', 'trust' => 'Ends Sunday at midnight', 'image' => 'https://images.unsplash.com/photo-1529139574466-a303027c1d8b?auto=format&fit=crop&w=1800&q=88', 'url' => route('frontend.shop.index')],
        ];
    }

    private function collections(): array
    {
        $collections = ProductCollection::query()
            ->withCount(['products' => fn ($query) => $query->where('status', 'active')])
            ->where('status', true)
            ->orderBy('sort_order')
            ->latest()
            ->limit(4)
            ->get()
            ->map(fn (ProductCollection $collection) => [
                'name' => $collection->name,
                'count' => $collection->products_count,
                'image_url' => $collection->image ? Imageurl($collection->image, 'collections') : null,
                'tint' => $this->gradientFor($collection->id),
                'dark' => $collection->id % 3 === 0,
                'sub' => $collection->description ?: 'Curated products',
            ])
            ->values()
            ->all();

        return $collections ?: Catalog::collections();
    }

    private function marquee(): array
    {
        $announcements = Announcement::query()
            ->where('status', true)
            ->orderBy('sort_order')
            ->limit(5)
            ->pluck('message')
            ->all();

        return $announcements ?: Catalog::marquee();
    }

    private function section(Collection $products, callable $filter, int $limit = 4, int $fallbackOffset = 0): array
    {
        $items = $products->filter($filter)->take($limit);

        if ($items->count() < $limit) {
            $ids = $items->pluck('id')->all();
            $items = $items
                ->merge($products->reject(fn (array $product) => in_array($product['id'], $ids, true))->slice($fallbackOffset))
                ->take($limit);
        }

        return $items->values()->all();
    }

    private function mapProduct(Product $product): array
    {
        $colorRows = $product->variants
            ->filter(fn ($variant) => $variant->color)
            ->pluck('color')
            ->unique('id')
            ->values();

        $colors = $colorRows
            ->map(fn ($color) => $color->code ?: str($color->name)->slug()->toString())
            ->filter()
            ->values()
            ->all();

        $colorMap = $colorRows
            ->mapWithKeys(fn ($color) => [
                $color->code ?: str($color->name)->slug()->toString() => [
                    'name' => $color->name,
                    'hex' => $color->hex_code ?: '#1a1a1d',
                ],
            ])
            ->all();

        $price = (float) $product->final_price;
        $was = $product->has_discount ? (float) $product->price : null;

        return [
            'id' => $product->id,
            'url' => route('frontend.shop.index', ['product' => $product->id]),
            'name' => $product->name,
            'price' => $price,
            'was' => $was,
            'tint' => $this->gradientFor($product->id),
            'dark' => $product->id % 5 === 0,
            'cat' => $product->category?->name ?: 'Products',
            'tag' => $product->is_on_sale ? 'sale' : ($product->is_new ? 'new' : null),
            'colors' => $colors ?: ['black'],
            'color_map' => $colorMap ?: Catalog::colors(),
            'rating' => 5,
            'reviews' => 0,
            'badge' => $product->is_best_seller ? 'Best Seller' : ($product->is_featured ? 'Featured' : null),
            'featured' => $product->is_featured,
            'brand' => $product->brand?->name ?: config('app.name'),
            'subcat' => $product->subCategory?->name ?: $product->category?->name ?: 'General',
            'sizes' => $product->variants->filter(fn ($variant) => $variant->size)->pluck('size.code')->filter()->unique()->values()->all(),
            'desc' => $product->short_description ?: $product->description,
            'gallery' => max(1, $product->images->count()),
            'image_url' => $product->thumbnail_url,
        ];
    }

    private function instagramTiles(Collection $products, bool $hasDynamicProducts): array
    {
        if (! $hasDynamicProducts) {
            return collect(['#ece6da,#d8cdb8', '#23201a,#3a352b', '#e7ddcf,#cdb79a', '#e3ddd0,#c4b69c', '#e6e0d6,#c9bba2', '#ded7c8,#bcae93'])
                ->map(fn (string $gradient, int $index) => [
                    'image_url' => null,
                    'tint' => "linear-gradient(150deg,{$gradient})",
                    'dark' => $index === 1,
                ])
                ->all();
        }

        return $products
            ->take(6)
            ->map(fn (array $product) => [
                'image_url' => $product['image_url'] ?? null,
                'tint' => $product['tint'],
                'dark' => $product['dark'],
            ])
            ->values()
            ->all();
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
