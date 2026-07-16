<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\ImageManager;
use App\Http\Requests\Product\BaseProductRequest;
use App\Models\Attribute;
use App\Models\Brand;
use App\Models\Category;
use App\Models\MediaAsset;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductTag;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Fields stored per language via spatie/laravel-translatable.
 */

class ProductService
{
    private const FOLDER = 'products';

    private const VARIANT_FOLDER = 'variants';

    private const TRANSLATABLE = ['name', 'short_description', 'description', 'seo_title', 'seo_description'];

    public function __construct(private readonly SettingService $settings) {}

    /**
     * Paginated, filtered product list for the admin index.
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->filteredQuery($filters)
            ->with(['category', 'brand', 'images'])
            ->withCount('variants')
            ->withSum('variants', 'stock')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * The filtered/sorted product query (no eager loads or pagination) — shared
     * by the index list and the export so both honour the same filters.
     *
     * @param  array<string, mixed>  $filters
     */
    public function filteredQuery(array $filters): \Illuminate\Database\Eloquent\Builder
    {
        $search = trim($filters['search'] ?? '');

        return Product::query()
            ->when(! empty($filters['ids'] ?? []), fn ($q) => $q->whereKey($filters['ids']))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")
                        ->orWhereHas('variants', function ($q) use ($search) {
                            $q->where('sku', 'like', "%{$search}%")
                                ->orWhere('barcode', 'like', "%{$search}%");
                        });
                });
            })
            ->when($filters['category_id'] ?? null, fn ($q, $v) => $q->where('category_id', $v))
            ->when($filters['brand_id'] ?? null, fn ($q, $v) => $q->where('brand_id', $v))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when(($filters['min_price'] ?? null) !== null, fn ($q) => $q->where('price', '>=', $filters['min_price']))
            ->when(($filters['max_price'] ?? null) !== null, fn ($q) => $q->where('price', '<=', $filters['max_price']))
            ->when(($filters['stock'] ?? null) === 'in_stock', fn ($q) => $q->whereHas('variants', fn ($v) => $v->where('stock', '>', 0)))
            ->when(($filters['stock'] ?? null) === 'out_of_stock', fn ($q) => $q->whereDoesntHave('variants', fn ($v) => $v->where('stock', '>', 0)))
            ->when(($filters['stock'] ?? null) === 'low_stock', fn ($q) => $q->whereHas('variants', fn ($v) => $v->whereColumn('stock', '<=', 'low_stock_alert')->where('low_stock_alert', '>', 0)))
            ->when(($filters['flag'] ?? null) === 'featured', fn ($q) => $q->where('is_featured', true))
            ->when(($filters['flag'] ?? null) === 'new', fn ($q) => $q->where('is_new', true))
            ->when(($filters['flag'] ?? null) === 'best_seller', fn ($q) => $q->where('is_best_seller', true))
            ->when(($filters['flag'] ?? null) === 'on_sale', fn ($q) => $q->where('is_on_sale', true))
            ->orderByDesc('id');
    }

    public function create(BaseProductRequest $request): Product
    {
        return $this->save($request, new Product);
    }

    public function update(BaseProductRequest $request, Product $product): Product
    {
        return $this->save($request, $product);
    }

    /**
     * Shared dropdown data for product create/edit forms.
     *
     * @return array<string, mixed>
     */
    public function formData(): array
    {
        return [
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            'brands' => Brand::orderBy('name')->get(['id', 'name']),
            'attributes' => Attribute::where('status', true)
                ->with(['values' => fn ($q) => $q->where('status', true)->orderBy('sort_order')])
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
            'tags' => ProductTag::orderBy('name')->get(['id', 'name']),
            'locales' => $this->settings->activeLanguages(),
            'primaryLang' => $this->settings->primaryLanguage(),
        ];
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
                } elseif ($selected = $this->selectedMediaFilename($request->input('thumbnail_media'), self::FOLDER)) {
                    $product->thumbnail = $selected;
                }

                $product->save();

                $this->removeImages($product, $request);
                $this->storeImages($product, $request, $uploaded);
                $this->setPrimaryImage($product, $request);
                $this->syncVariants($product, $request, $uploaded);
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
        $product->product_type = $request->input('product_type', 'variable');
        $this->fillTranslations($product, $request);

        // Single products carry their own sku/stock; variable products use variants.
        if ($product->product_type === 'single') {
            // Auto-generate a unique SKU when the admin leaves it blank (keep the
            // existing one on edit so it stays stable).
            $product->sku = $request->filled('sku')
                ? trim((string) $request->input('sku'))
                : ($product->sku ?: $this->generateProductSku());
            $product->stock = (int) ($request->input('stock') ?? 0);
            $product->low_stock_alert = (int) ($request->input('low_stock_alert') ?? 0);
        } else {
            $product->sku = null;
            $product->stock = 0;
            $product->low_stock_alert = 0;
        }

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

        $mediaNames = array_values(array_unique(array_filter((array) $request->input('images_media', []))));

        foreach ($mediaNames as $offset => $filename) {
            $name = $this->selectedMediaFilename($filename, self::FOLDER);

            if (! $name || $product->images()->where('image', $name)->exists()) {
                continue;
            }

            $product->images()->create([
                'image' => $name,
                'sort_order' => $start + count((array) $request->file('images', [])) + $offset + 1,
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

    private function syncVariants(Product $product, BaseProductRequest $request, array &$uploaded = []): void
    {
        $product->variants()->delete();

        // Single products have no variant rows.
        if ($request->input('product_type') === 'single') {
            return;
        }

        $usedSkus = [];

        foreach ((array) $request->input('variants', []) as $index => $variant) {
            $valueIds = array_values(array_filter(array_map('intval', (array) ($variant['value_ids'] ?? []))));
            if (empty($valueIds)) {
                continue;
            }

            // Auto-generate a unique SKU when the admin leaves it blank.
            $sku = trim($variant['sku'] ?? '');
            if ($sku === '') {
                $sku = $this->generateSku($product, $valueIds, $usedSkus);
            }
            $usedSkus[] = $sku;

            // Per-variant image: a freshly uploaded file wins, else keep the existing one.
            $imageFile = $request->file("variants.{$index}.image");
            if ($imageFile) {
                $image = ImageManager::upload($imageFile, self::VARIANT_FOLDER);
                $uploaded[] = $image;
            } elseif ($selected = $this->selectedMediaFilename($variant['image_media'] ?? null, self::VARIANT_FOLDER)) {
                $image = $selected;
            } else {
                $image = ($variant['image_existing'] ?? '') ?: null;
            }

            $created = $product->variants()->create([
                'sku' => $sku,
                'barcode' => $variant['barcode'] ?? null,
                'image' => $image,
                'stock' => $variant['stock'] ?? 0,
                'low_stock_alert' => $variant['low_stock_alert'] ?? 0,
                'price' => $this->nullableNumber($variant['price'] ?? null),
                'cost_price' => $this->nullableNumber($variant['cost_price'] ?? null),
                'weight' => $this->nullableNumber($variant['weight'] ?? null),
                'status' => (bool) ($variant['status'] ?? true),
            ]);

            $created->values()->sync($valueIds);
        }
    }

    /**
     * A unique product-level SKU for single products left blank by the admin.
     * Checks both the products and product_variants tables so codes never clash.
     */
    private function generateProductSku(): string
    {
        $prefix = $this->settings->productSkuPrefix();

        do {
            $sku = $prefix.strtoupper(Str::random(8));
        } while (
            Product::query()->where('sku', $sku)->exists()
            || DB::table('product_variants')->where('sku', $sku)->exists()
        );

        return $sku;
    }

    /**
     * Build a unique SKU (e.g. SKU-12-3-4) that collides with neither the
     * batch being saved nor any existing variant.
     *
     * @param  array<int, int>  $valueIds
     * @param  array<int, string>  $used
     */
    private function generateSku(Product $product, array $valueIds, array $used): string
    {
        $base = 'SKU-'.$product->id.'-'.implode('-', $valueIds);
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

    private function selectedMediaFilename(?string $filename, string $folder): ?string
    {
        $filename = trim((string) $filename);

        if ($filename === '') {
            return null;
        }

        return MediaAsset::query()
            ->where('folder', $folder)
            ->where('filename', $filename)
            ->value('filename');
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
