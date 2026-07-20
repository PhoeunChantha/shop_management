<?php

namespace App\Http\Controllers\Frontend;

use App\Enums\ReviewStatus;
use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Banner;
use App\Models\Collection as ProductCollection;
use App\Models\DealCampaign;
use App\Models\Product;
use App\Models\Review;
use App\Models\Setting;
use App\Services\FrontendProductService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __construct(
        private readonly FrontendProductService $products,
    ) {}

    public function index(): View
    {
        $mappedProducts = $this->products->mappedActiveProducts();
        $hasDynamicProducts = $mappedProducts->isNotEmpty();
        $homeProducts = $mappedProducts;
        $flashDeal = $this->activeFlashDeal();
        $flashProducts = $flashDeal['products'] ?: $this->section($homeProducts, fn (array $product) => filled($product['was']));

        return view('frontend.home', [
            'heroSlides' => $this->heroSlides(),
            'best' => $this->section($homeProducts, fn (array $product) => $product['badge'] === 'Best Seller' || $product['reviews'] > 200),
            'fresh' => $this->section($homeProducts, fn (array $product) => $product['tag'] === 'new'),
            'trend' => $this->section($homeProducts, fn (array $product) => (bool) ($product['featured'] ?? false), 4, 4),
            'flash' => $flashProducts,
            'flashDeal' => $flashDeal,
            'collections' => $this->collections(),
            'reviews' => $this->reviews(),
            'reviewMeta' => $this->reviewMeta(),
            'marquee' => $this->marquee(),
            'instagramTiles' => $this->instagramTiles($homeProducts, $hasDynamicProducts),
            'instagramHandle' => $this->instagramHandle(),
            'trustItems' => $this->trustItems(),
            'newsletter' => [
                'eyebrow' => 'Members get more',
                'title' => 'Get 10% off your first order',
                'copy' => 'Early access to drops, members-only pricing, and free shipping. No spam — just good tees.',
            ],
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function productRelations(): array
    {
        return $this->products->relations();
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

        return $collections ?: [
            ['name' => 'Premium Basics', 'count' => 0, 'image_url' => null, 'tint' => 'linear-gradient(150deg,#e8e4dd,#cbbfa9)', 'dark' => false, 'sub' => 'Core products for clean merchandising.'],
            ['name' => 'Editorial Picks', 'count' => 0, 'image_url' => null, 'tint' => 'linear-gradient(150deg,#dfe7ee,#bcccdb)', 'dark' => false, 'sub' => 'Featured products for homepage and campaign use.'],
        ];
    }

    private function marquee(): array
    {
        $announcements = Announcement::query()
            ->where('status', true)
            ->orderBy('sort_order')
            ->limit(5)
            ->pluck('message')
            ->all();

        return collect($announcements)
            ->merge($this->defaultMarquee())
            ->filter()
            ->unique(fn (string $message): string => mb_strtolower($message))
            ->values()
            ->take(6)
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function defaultMarquee(): array
    {
        return [
            'Free standard shipping over $75',
            'Easy 30-day returns',
            '240gsm organic cotton',
            'Carbon-neutral delivery',
            'Member early access',
        ];
    }

    /**
     * @return array{title: string, seconds: int, products: array<int, array<string, mixed>>}
     */
    private function activeFlashDeal(): array
    {
        $deal = DealCampaign::query()
            ->with(['products' => fn ($query) => $query
                ->with($this->productRelations())
                ->withSum('variants', 'stock')
                ->where('status', 'active')
                ->orderBy('sort_order')
                ->limit(4)])
            ->where('status', true)
            ->where('type', 'flash')
            ->where(function (Builder $query): void {
                $query->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function (Builder $query): void {
                $query->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            })
            ->orderBy('priority')
            ->orderBy('ends_at')
            ->first();

        if (! $deal) {
            return [
                'title' => 'Up to 40% off - ends soon',
                'seconds' => 7 * 3600 + 42 * 60 + 18,
                'products' => [],
            ];
        }

        return [
            'title' => $deal->title,
            'seconds' => $deal->ends_at ? (int) max(1, floor(now()->diffInSeconds($deal->ends_at, false))) : 24 * 3600,
            'products' => $deal->products
                ->map(fn (Product $product): array => $this->applyDealDiscount($this->products->map($product), $deal))
                ->values()
                ->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $product
     * @return array<string, mixed>
     */
    private function applyDealDiscount(array $product, DealCampaign $deal): array
    {
        $value = (float) $deal->discount_value;

        if ($value <= 0 || ! in_array($deal->discount_type, ['fixed', 'percentage'], true)) {
            return $product;
        }

        $basePrice = (float) $product['price'];
        $discounted = match ($deal->discount_type) {
            'percentage' => $basePrice - ($basePrice * min($value, 100) / 100),
            'fixed' => $basePrice - $value,
            default => $basePrice,
        };

        $product['was'] = $basePrice;
        $product['price'] = max(0, round($discounted, 2));
        $product['tag'] = 'sale';

        return $product;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function reviews(): array
    {
        $reviews = Review::query()
            ->with('user:id,name')
            ->where('status', ReviewStatus::Approved->value)
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn (Review $review): array => [
                'name' => $review->author_name ?: $review->user?->name ?: 'Customer',
                'city' => 'Verified buyer',
                'rating' => $review->rating,
                'text' => $review->body ?: $review->title ?: 'Great product and smooth shopping experience.',
                'verified' => $review->is_verified,
            ])
            ->values()
            ->all();

        return $reviews ?: [
            ['name' => 'Verified customer', 'city' => 'Recent buyer', 'rating' => 5, 'text' => 'Clean fit, fast checkout, and the fabric feels premium.', 'verified' => true],
            ['name' => 'Store customer', 'city' => 'Repeat buyer', 'rating' => 5, 'text' => 'The product photos and sizing made it easy to choose.', 'verified' => true],
        ];
    }

    /**
     * @return array{eyebrow: string}
     */
    private function reviewMeta(): array
    {
        $count = Review::query()->where('status', ReviewStatus::Approved->value)->count();

        return [
            'eyebrow' => $count > 0 ? 'Loved by '.number_format($count).' customers' : 'Loved by 50,000+',
        ];
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

    private function instagramHandle(): string
    {
        $links = json_decode((string) Setting::get('social_links', '[]'), true) ?: [];
        $instagram = collect($links)->first(fn (array $link): bool => str_contains((string) ($link['icon'] ?? ''), 'instagram'));
        $url = (string) ($instagram['url'] ?? '');
        $handle = trim((string) ($instagram['title'] ?? ''));

        if ($url !== '' && preg_match('~instagram\.com/([^/?#]+)~i', $url, $matches)) {
            $handle = $matches[1];
        }

        return '@'.ltrim($handle ?: 'tshirtshop', '@');
    }

    /**
     * @return array<int, array{0: string, 1: string, 2: string}>
     */
    private function trustItems(): array
    {
        return [
            ['truck', 'Free shipping', 'On orders over $75'],
            ['refresh', '30-day returns', 'No-questions-asked'],
            ['shield', 'Secure checkout', '256-bit encryption'],
            ['spark', filled(Setting::get('site_tagline')) ? Setting::get('site_tagline') : 'Carbon neutral', 'Every delivery'],
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
