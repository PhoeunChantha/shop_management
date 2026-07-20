<?php

namespace App\Services;

use App\Models\Announcement;
use App\Models\Category;
use App\Models\Collection as ProductCollection;
use App\Models\Product;
use App\Models\ProductTag;
use App\Support\Catalog;
use Illuminate\Support\Str;

class FrontendNavigationService
{
    /**
     * @return array<string, mixed>
     */
    public function data(): array
    {
        $categoryMenus = $this->categoryMenus();

        return [
            'announcements' => $this->announcements(),
            'categoryMenus' => $categoryMenus,
            'menus' => [
                'new' => $this->newProducts(),
                'best' => $this->bestSellers(),
                'tees' => $this->categories(),
                'graphics' => $this->graphics(),
                'collections' => $this->collections(),
            ],
            'mobile' => $this->mobileLinks($categoryMenus),
            'search' => [
                'recent' => $this->recentSearches(),
                'trending' => $this->trendingSearches(),
                'categories' => $this->categories(3),
                'products' => $this->popularProducts(),
            ],
        ];
    }

    /**
     * @return array<int, array{label: string, url: string, children: array<int, array{label: string, url: string}>, feature_class: string, feature_title: string, feature_cta: string}>
     */
    private function categoryMenus(int $limit = 5): array
    {
        $featureClasses = ['ut-mega-collection', 'ut-mega-sale', 'ut-mega-oversized', 'ut-mega-graphic', 'ut-mega-collection'];

        $menus = Category::query()
            ->where('status', true)
            ->whereHas('products', fn ($query) => $query->where('status', 'active'))
            ->withCount(['products' => fn ($query) => $query->where('status', 'active')])
            ->orderBy('sort_order')
            ->latest()
            ->limit($limit)
            ->get(['id', 'name', 'slug'])
            ->values()
            ->map(function (Category $category, int $index) use ($featureClasses): array {
                $children = $this->subCategoriesFor($category);

                return [
                    'label' => $category->name,
                    'url' => route('frontend.shop.index', ['category' => $category->slug ?: $category->id]),
                    'children' => $children ?: $this->categoryFallbackChildren($category),
                    'feature_class' => $featureClasses[$index % count($featureClasses)],
                    'feature_title' => strtoupper(str_replace(' ', "\n", $category->name)),
                    'feature_cta' => 'Shop '.$category->name,
                ];
            })
            ->all();

        return $menus ?: $this->fallbackCategoryMenus();
    }

    /**
     * @return array<int, array{label: string, url: string}>
     */
    private function subCategoriesFor(Category $category, int $limit = 5): array
    {
        return Product::query()
            ->where('status', 'active')
            ->where('category_id', $category->id)
            ->whereNotNull('sub_category_id')
            ->with(['subCategory' => fn ($query) => $query->where('status', true)->select(['id', 'name', 'slug'])])
            ->latest()
            ->limit(40)
            ->get(['id', 'category_id', 'sub_category_id'])
            ->pluck('subCategory')
            ->filter()
            ->unique('id')
            ->take($limit)
            ->map(fn (Category $subCategory): array => [
                'label' => $subCategory->name,
                'url' => route('frontend.shop.index', [
                    'category' => $category->slug ?: $category->id,
                    'subcategory' => $subCategory->slug ?: $subCategory->id,
                ]),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{label: string, url: string}>
     */
    private function categoryFallbackChildren(Category $category): array
    {
        return [
            ['label' => 'All '.$category->name, 'url' => route('frontend.shop.index', ['category' => $category->slug ?: $category->id])],
            ['label' => 'New in '.$category->name, 'url' => route('frontend.shop.index', ['category' => $category->slug ?: $category->id, 'filter' => 'new'])],
            ['label' => 'Best sellers', 'url' => route('frontend.shop.index', ['category' => $category->slug ?: $category->id, 'filter' => 'best'])],
        ];
    }

    /**
     * @return array<int, array{label: string, url: string, children: array<int, array{label: string, url: string}>, feature_class: string, feature_title: string, feature_cta: string}>
     */
    private function fallbackCategoryMenus(): array
    {
        return collect([
            ['label' => 'New in', 'children' => ['New arrivals', 'Latest drops', 'Trending now', 'Staff picks'], 'class' => 'ut-mega-collection'],
            ['label' => 'Best sellers', 'children' => ['Most loved tees', 'Heavyweight favorites', 'Community picks', 'Sale best sellers'], 'class' => 'ut-mega-sale'],
            ['label' => 'Tees', 'children' => ['Heavyweight', 'Vintage wash', 'Streetwear', 'Minimal'], 'class' => 'ut-mega-oversized'],
            ['label' => 'Graphics', 'children' => ['Anime', 'Typography', 'Street art', 'Limited edition'], 'class' => 'ut-mega-graphic'],
            ['label' => 'Collections', 'children' => ['Summer collection', 'Urban collection', 'Minimal collection', 'Sale up to 40% off'], 'class' => 'ut-mega-collection'],
        ])->map(fn (array $item): array => [
            'label' => $item['label'],
            'url' => route('frontend.shop.index'),
            'children' => $this->fallbackLinks($item['children']),
            'feature_class' => $item['class'],
            'feature_title' => strtoupper(str_replace(' ', "\n", $item['label'])),
            'feature_cta' => 'Shop '.$item['label'],
        ])->all();
    }

    /**
     * @return array<int, string>
     */
    private function announcements(): array
    {
        $messages = Announcement::query()
            ->where('status', true)
            ->orderBy('sort_order')
            ->limit(6)
            ->pluck('message')
            ->filter()
            ->values()
            ->all();

        return $this->fillAnnouncementLoop($messages);
    }

    /**
     * @param  array<int, string>  $messages
     * @return array<int, string>
     */
    private function fillAnnouncementLoop(array $messages): array
    {
        return collect($messages)
            ->merge(Catalog::marquee())
            ->filter()
            ->unique(fn (string $message): string => mb_strtolower($message))
            ->values()
            ->take(6)
            ->all();
    }

    /**
     * @return array<int, array{label: string, url: string}>
     */
    private function newProducts(int $limit = 4): array
    {
        return Product::query()
            ->where('status', 'active')
            ->where('is_new', true)
            ->latest()
            ->limit($limit)
            ->get(['id', 'name', 'slug'])
            ->map(fn (Product $product): array => [
                'label' => $product->name,
                'url' => route('frontend.shop.show', $this->productSlug($product)),
            ])
            ->values()
            ->all() ?: $this->fallbackLinks(['New arrivals', 'Latest drops', 'Trending now', 'Staff picks']);
    }

    /**
     * @return array<int, array{label: string, url: string}>
     */
    private function bestSellers(int $limit = 4): array
    {
        return Product::query()
            ->where('status', 'active')
            ->where('is_best_seller', true)
            ->latest()
            ->limit($limit)
            ->get(['id', 'name', 'slug'])
            ->map(fn (Product $product): array => [
                'label' => $product->name,
                'url' => route('frontend.shop.show', $this->productSlug($product)),
            ])
            ->values()
            ->all() ?: $this->fallbackLinks(['Most loved tees', 'Heavyweight favorites', 'Community picks', 'Sale best sellers']);
    }

    /**
     * @return array<int, array{label: string, url: string}>
     */
    private function categories(int $limit = 4): array
    {
        return Category::query()
            ->withCount(['products' => fn ($query) => $query->where('status', 'active')])
            ->where('status', true)
            ->orderBy('sort_order')
            ->latest()
            ->limit($limit)
            ->get(['id', 'name', 'slug'])
            ->map(fn (Category $category): array => [
                'label' => $category->name,
                'url' => route('frontend.shop.index', ['category' => $category->slug ?: $category->id]),
            ])
            ->values()
            ->all() ?: $this->fallbackLinks(['Heavyweight', 'Vintage wash', 'Streetwear', 'Minimal']);
    }

    /**
     * @return array<int, array{label: string, url: string}>
     */
    private function graphics(int $limit = 4): array
    {
        return ProductTag::query()
            ->where('status', true)
            ->latest()
            ->limit($limit)
            ->get(['name', 'slug'])
            ->map(fn (ProductTag $tag): array => [
                'label' => $tag->name,
                'url' => route('frontend.shop.index', ['tag' => $tag->slug ?: $tag->name]),
            ])
            ->values()
            ->all() ?: $this->fallbackLinks(['Anime', 'Typography', 'Street art', 'Limited edition']);
    }

    /**
     * @return array<int, array{label: string, url: string}>
     */
    private function collections(int $limit = 4): array
    {
        return ProductCollection::query()
            ->where('status', true)
            ->orderBy('sort_order')
            ->latest()
            ->limit($limit)
            ->get(['id', 'name', 'slug'])
            ->map(fn (ProductCollection $collection): array => [
                'label' => $collection->name,
                'url' => route('frontend.shop.index', ['collection' => $collection->slug ?: $collection->id]),
            ])
            ->values()
            ->all() ?: $this->fallbackLinks(['Summer collection', 'Urban collection', 'Minimal collection', 'Sale up to 40% off']);
    }

    /**
     * @return array<int, array{label: string, url: string}>
     */
    private function popularProducts(int $limit = 2): array
    {
        return Product::query()
            ->where('status', 'active')
            ->orderByDesc('is_best_seller')
            ->orderByDesc('is_featured')
            ->latest()
            ->limit($limit)
            ->get(['id', 'name', 'slug', 'price', 'discount_type', 'discount_amount'])
            ->map(fn (Product $product): array => [
                'label' => $product->name,
                'url' => route('frontend.shop.show', $this->productSlug($product)),
                'price' => '$'.number_format((float) $product->final_price, 2),
            ])
            ->values()
            ->all() ?: [
                ['label' => 'Essential Heavyweight Tee', 'url' => route('frontend.shop.index'), 'price' => '$42'],
                ['label' => 'Vintage Box Tee', 'url' => route('frontend.shop.index'), 'price' => '$48'],
            ];
    }

    /**
     * @return array<int, array{label: string, url: string}>
     */
    private function mobileLinks(array $categoryMenus): array
    {
        return collect($categoryMenus)
            ->map(fn (array $menu): array => [
                'label' => $menu['label'],
                'url' => $menu['url'],
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{label: string, url: string}>
     */
    private function recentSearches(): array
    {
        return $this->fallbackLinks(['Oversized black tee', 'Vintage wash']);
    }

    /**
     * @return array<int, array{label: string, url: string}>
     */
    private function trendingSearches(): array
    {
        return $this->fallbackLinks(['Heavyweight', 'Summer drop', 'Graphic tees']);
    }

    /**
     * @param  array<int, string>  $labels
     * @return array<int, array{label: string, url: string}>
     */
    private function fallbackLinks(array $labels): array
    {
        return collect($labels)
            ->map(fn (string $label): array => ['label' => $label, 'url' => route('frontend.shop.index')])
            ->all();
    }

    private function productSlug(Product $product): string
    {
        return $product->slug ?: Str::slug($product->name);
    }
}
