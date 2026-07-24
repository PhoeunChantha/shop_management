<?php

namespace App\Services;

use App\Models\Color;
use App\Models\Product;
use App\Models\Size;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class FrontendProductService
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
    private function images(Product $product): array
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
