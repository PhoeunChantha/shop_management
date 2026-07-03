<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\ImageManager;
use App\Http\Requests\Product\BaseProductRequest;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductTag;
use App\Services\SettingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Fields stored per language via spatie/laravel-translatable.
 */

class ProductService
{
    private const FOLDER = 'products';

    private const TRANSLATABLE = ['name', 'short_description', 'description', 'seo_title', 'seo_description'];

    public function __construct(private readonly SettingService $settings) {}

    public function create(BaseProductRequest $request): Product
    {
        return $this->save($request, new Product);
    }

    public function update(BaseProductRequest $request, Product $product): Product
    {
        return $this->save($request, $product);
    }

    /**
     * Persist a product with all of its related data in a single transaction.
     * Any image file written during a failed attempt is removed on rollback.
     */
    private function save(BaseProductRequest $request, Product $product): Product
    {
        $uploaded = [];

        try {
            return DB::transaction(function () use ($request, $product, &$uploaded) {
                $this->fill($product, $request);
                $product->slug = $this->uniqueSlug($this->primaryName($request), $product->id);

                if ($request->hasFile('thumbnail')) {
                    $name = ImageManager::update($request->file('thumbnail'), $product->thumbnail, self::FOLDER);
                    $product->thumbnail = $name;
                    $uploaded[] = $name;
                }

                $product->save();

                $this->removeImages($product, $request);
                $this->storeImages($product, $request, $uploaded);
                $this->setPrimaryImage($product, $request);
                $this->syncVariants($product, $request);
                $this->syncSpecifications($product, $request);
                $this->syncTags($product, $request);

                return $product;
            });
        } catch (\Throwable $e) {
            foreach ($uploaded as $file) {
                ImageManager::delete($file, self::FOLDER);
            }

            throw $e;
        }
    }

    public function updateStatus(Product $product, string $status): void
    {
        $product->update(['status' => $status]);
    }

    public function deleteImage(ProductImage $image): void
    {
        $productId = $image->product_id;
        $wasPrimary = $image->is_primary;

        ImageManager::delete($image->image, self::FOLDER);
        $image->delete();

        // Promote another image to primary if we removed the primary one.
        if ($wasPrimary) {
            $next = ProductImage::where('product_id', $productId)->orderBy('sort_order')->first();
            $next?->update(['is_primary' => true]);
        }
    }

    public function delete(Product $product): void
    {
        DB::transaction(function () use ($product) {
            ImageManager::delete($product->thumbnail, self::FOLDER);

            foreach ($product->images as $image) {
                ImageManager::delete($image->image, self::FOLDER);
            }

            $product->delete(); // cascades images/variants/specs/pivot
        });
    }

    /* ------------------------------------------------------------------ */

    private function fill(Product $product, BaseProductRequest $request): void
    {
        $product->category_id = $request->input('category_id');
        $product->sub_category_id = $request->input('sub_category_id') ?: null;
        $product->brand_id = $request->input('brand_id') ?: null;
        $this->fillTranslations($product, $request);
        $product->price = $request->input('price');
        $product->cost_price = $request->filled('cost_price') ? $request->input('cost_price') : null;
        $product->discount_type = $request->input('discount_type') ?: null;
        $product->discount_amount = $request->filled('discount_type') ? ($request->input('discount_amount') ?? 0) : 0;
        $product->weight = $request->filled('weight') ? $request->input('weight') : null;
        $product->status = $request->input('status');
        $product->is_featured = $request->boolean('is_featured');
        $product->is_new = $request->boolean('is_new');
        $product->is_best_seller = $request->boolean('is_best_seller');
        $product->is_on_sale = $request->boolean('is_on_sale');
        $product->sort_order = (int) ($request->input('sort_order') ?? 0);
    }

    /**
     * Store the per-language values for every translatable field, keeping only
     * the configured store languages and dropping blank locales.
     */
    private function fillTranslations(Product $product, BaseProductRequest $request): void
    {
        $languages = $this->settings->languages();

        foreach (self::TRANSLATABLE as $field) {
            $values = (array) $request->input($field, []);
            $translations = [];

            foreach ($languages as $lang) {
                $value = $values[$lang] ?? null;
                if ($value !== null && $value !== '') {
                    $translations[$lang] = (string) $value;
                }
            }

            $product->setTranslations($field, $translations);
        }
    }

    /**
     * The product name in the primary language (used for the slug).
     */
    private function primaryName(BaseProductRequest $request): string
    {
        $primary = $this->settings->primaryLanguage();
        $names = (array) $request->input('name', []);

        return (string) ($names[$primary] ?? collect($names)->first(fn ($v) => filled($v)) ?? 'product');
    }

    private function storeImages(Product $product, BaseProductRequest $request, array &$uploaded): void
    {
        $start = (int) $product->images()->max('sort_order');

        foreach ((array) $request->file('images', []) as $i => $file) {
            $name = ImageManager::upload($file, self::FOLDER);
            $uploaded[] = $name;

            $product->images()->create([
                'image' => $name,
                'sort_order' => $start + $i + 1,
                'is_primary' => false,
            ]);
        }
    }

    private function removeImages(Product $product, BaseProductRequest $request): void
    {
        $ids = array_filter((array) $request->input('removed_images', []));

        if (empty($ids)) {
            return;
        }

        foreach (ProductImage::where('product_id', $product->id)->whereIn('id', $ids)->get() as $image) {
            ImageManager::delete($image->image, self::FOLDER);
            $image->delete();
        }
    }

    private function setPrimaryImage(Product $product, BaseProductRequest $request): void
    {
        $images = $product->images()->get();

        if ($images->isEmpty()) {
            return;
        }

        $primaryId = (int) $request->input('primary_image_id');
        $product->images()->update(['is_primary' => false]);

        $target = $images->firstWhere('id', $primaryId)
            ?? $images->sortBy('sort_order')->first();

        $product->images()->whereKey($target->id)->update(['is_primary' => true]);
    }

    private function syncVariants(Product $product, BaseProductRequest $request): void
    {
        $product->variants()->delete();
        $usedSkus = [];

        foreach ((array) $request->input('variants', []) as $variant) {
            if (empty($variant['size_id']) || empty($variant['color_id'])) {
                continue;
            }

            // Auto-generate a unique SKU when the admin leaves it blank.
            $sku = trim($variant['sku'] ?? '');
            if ($sku === '') {
                $sku = $this->generateSku($product, $variant, $usedSkus);
            }
            $usedSkus[] = $sku;

            $product->variants()->create([
                'size_id' => $variant['size_id'],
                'color_id' => $variant['color_id'],
                'sku' => $sku,
                'barcode' => $variant['barcode'] ?? null,
                'stock' => $variant['stock'] ?? 0,
                'low_stock_alert' => $variant['low_stock_alert'] ?? 0,
                'price' => $this->nullableNumber($variant['price'] ?? null),
                'cost_price' => $this->nullableNumber($variant['cost_price'] ?? null),
                'weight' => $this->nullableNumber($variant['weight'] ?? null),
                'status' => (bool) ($variant['status'] ?? true),
            ]);
        }
    }

    /**
     * Build a unique SKU (e.g. SKU-12-3-4) that collides with neither the
     * batch being saved nor any existing variant.
     *
     * @param  array<int, string>  $used
     */
    private function generateSku(Product $product, array $variant, array $used): string
    {
        $base = 'SKU-'.$product->id.'-'.($variant['size_id'] ?? 'X').'-'.($variant['color_id'] ?? 'X');
        $sku = $base;
        $n = 1;

        while (in_array($sku, $used, true) || DB::table('product_variants')->where('sku', $sku)->exists()) {
            $sku = $base.'-'.$n++;
        }

        return $sku;
    }

    private function syncSpecifications(Product $product, BaseProductRequest $request): void
    {
        $product->specifications()->delete();

        foreach ((array) $request->input('specifications', []) as $i => $spec) {
            $name = trim($spec['name'] ?? '');
            $value = trim($spec['value'] ?? '');

            if ($name === '' || $value === '') {
                continue;
            }

            $product->specifications()->create([
                'name' => $name,
                'value' => $value,
                'sort_order' => (int) ($spec['sort_order'] ?? $i),
            ]);
        }
    }

    private function syncTags(Product $product, BaseProductRequest $request): void
    {
        $ids = array_map('intval', (array) $request->input('tags', []));

        $newNames = collect(explode(',', (string) $request->input('new_tags', '')))
            ->map(fn ($t) => trim($t))
            ->filter();

        foreach ($newNames as $name) {
            $tag = ProductTag::firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'status' => true],
            );
            $ids[] = $tag->id;
        }

        $product->tags()->sync(array_values(array_unique($ids)));
    }

    private function nullableNumber($value): ?string
    {
        return ($value === null || $value === '') ? null : (string) $value;
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'product';
        $slug = $base;
        $suffix = 2;

        while (
            Product::query()
                ->where('slug', $slug)
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
