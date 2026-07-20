<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Color;
use App\Models\Product;
use App\Models\Size;
use App\Support\Catalog;
use Illuminate\Support\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ShopController extends Controller
{
    public function index(Request $request): View
    {
        $products = Product::query()
            ->with($this->productRelations())
            ->withSum('variants', 'stock')
            ->where('status', 'active')
            ->orderBy('sort_order')
            ->latest()
            ->get()
            ->map(fn (Product $product): array => $this->mapProduct($product))
            ->values();

        if ($products->isEmpty()) {
            $products = collect(Catalog::products());
        }

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
            'sizes' => $this->sizes($products),
            'colors' => $this->colors($products),
            'minPrice' => $minPrice,
            'maxPrice' => $maxPrice,
        ]);
    }

    public function show(string $product): View|RedirectResponse
    {
        $requestedProduct = $product;

        $dynamicProduct = Product::query()
            ->with($this->productRelations())
            ->withSum('variants', 'stock')
            ->where('status', 'active')
            ->where('slug', $product)
            ->first();

        if (! $dynamicProduct && ctype_digit($product)) {
            $dynamicProduct = Product::query()
                ->with($this->productRelations())
                ->withSum('variants', 'stock')
                ->where('status', 'active')
                ->find((int) $product);

            if ($dynamicProduct) {
                return redirect()->route('frontend.shop.show', $this->productSlug($dynamicProduct));
            }
        }

        if (! $dynamicProduct) {
            $dynamicProduct = Product::query()
                ->with($this->productRelations())
                ->withSum('variants', 'stock')
                ->where('status', 'active')
                ->get()
                ->first(fn (Product $item): bool => $this->productSlug($item) === $product);
        }

        if ($dynamicProduct) {
            $product = $this->mapProduct($dynamicProduct);
            $related = Product::query()
                ->with($this->productRelations())
                ->withSum('variants', 'stock')
                ->where('status', 'active')
                ->where('id', '!=', $dynamicProduct->id)
                ->where('category_id', $dynamicProduct->category_id)
                ->orderBy('sort_order')
                ->latest()
                ->limit(4)
                ->get()
                ->map(fn (Product $product): array => $this->mapProduct($product))
                ->values()
                ->all();
        } else {
            $product = collect(Catalog::products())
                ->first(fn (array $item): bool => (string) $item['id'] === $product || Str::slug($item['name']) === $product) ?? abort(404);
            $product['slug'] = Str::slug($product['name']);
            $product['url'] = route('frontend.shop.show', $product['slug']);
            $product['images'] = [];

            if (ctype_digit($requestedProduct)) {
                return redirect()->route('frontend.shop.show', $product['slug']);
            }

            $related = array_slice(array_values(array_filter(
                Catalog::products(),
                fn ($p) => $p['cat'] === $product['cat'] && $p['id'] !== $product['id']
            )), 0, 4);
        }

        return view('frontend.shop.show', [
            'product' => $product,
            'related' => $related,
            'colors' => $product['color_map'] ?? Catalog::colors(),
            'reviews' => Catalog::reviews(),
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function productRelations(): array
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
     * @return array<string, mixed>
     */
    private function mapProduct(Product $product): array
    {
        $variantColors = $product->variants
            ->filter(fn ($variant) => $variant->color)
            ->pluck('color')
            ->unique('id')
            ->values();

        $colorKeys = $variantColors
            ->map(fn (Color $color): string => strtolower($color->code ?: $color->name))
            ->filter()
            ->values()
            ->all();

        $colorMap = $variantColors
            ->mapWithKeys(fn (Color $color): array => [
                strtolower($color->code ?: $color->name) => [
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

        $price = (float) $product->final_price;
        $slug = $this->productSlug($product);
        $images = $this->productImages($product);

        return [
            'id' => $product->id,
            'slug' => $slug,
            'url' => route('frontend.shop.show', $slug),
            'name' => $product->name,
            'price' => $price,
            'was' => $product->has_discount ? (float) $product->price : null,
            'tint' => $this->gradientFor($product->id),
            'dark' => $product->id % 5 === 0,
            'cat' => $product->category?->name ?: 'Products',
            'subcat' => $product->subCategory?->name ?: $product->category?->name ?: 'General',
            'brand' => $product->brand?->name ?: config('app.name'),
            'tag' => $product->is_on_sale || $product->has_discount ? 'sale' : ($product->is_new ? 'new' : null),
            'colors' => $colorKeys ?: ['black'],
            'color_map' => $colorMap ?: Catalog::colors(),
            'sizes' => $sizes ?: ['One Size'],
            'rating' => (float) ($product->rating_avg ?: 0),
            'reviews' => (int) $product->rating_count,
            'badge' => $product->is_best_seller ? 'Best Seller' : ($product->is_featured ? 'Featured' : null),
            'featured' => $product->is_featured,
            'desc' => $product->short_description ?: $product->description,
            'gallery' => max(1, count($images)),
            'images' => $images,
            'image_url' => $product->thumbnail_url,
        ];
    }

    /**
     * @return array<int, string>
     */
    private function productImages(Product $product): array
    {
        $images = $product->images
            ->sortByDesc('is_primary')
            ->map(fn ($image): ?string => $image->image ? Imageurl($image->image, 'products') : null)
            ->filter()
            ->values();

        if ($product->thumbnail_url) {
            $images->prepend($product->thumbnail_url);
        }

        return array_values(array_unique($images->all()));
    }

    private function productSlug(Product $product): string
    {
        return $product->slug ?: Str::slug($product->name);
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $products
     * @return array<int, string>
     */
    private function sizes(Collection $products): array
    {
        $sizes = Size::query()
            ->where('status', true)
            ->orderBy('sort_order')
            ->pluck('code')
            ->filter()
            ->values()
            ->all();

        return $sizes ?: $products->pluck('sizes')->flatten()->filter()->unique()->values()->all() ?: Catalog::sizes();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $products
     * @return array<string, array{name: string, hex: string}>
     */
    private function colors(Collection $products): array
    {
        $colors = Color::query()
            ->where('status', true)
            ->orderBy('sort_order')
            ->get(['name', 'code', 'hex_code'])
            ->mapWithKeys(fn (Color $color): array => [
                strtolower($color->code ?: $color->name) => [
                    'name' => $color->name,
                    'hex' => $color->hex_code ?: '#1a1a1d',
                ],
            ])
            ->all();

        return $colors ?: $products->pluck('color_map')->collapse()->all() ?: Catalog::colors();
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
