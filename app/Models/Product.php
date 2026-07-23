<?php

namespace App\Models;

use App\Enums\ProductType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class Product extends Model
{
    use HasFactory;
    use HasTranslations;

    /**
     * Fields stored per language (JSON). Reading them returns the value for
     * the current app locale (with fallback handled by spatie).
     *
     * @var array<int, string>
     */
    public array $translatable = [
        'name',
        'short_description',
        'description',
        'seo_title',
        'seo_description',
    ];

    protected $fillable = [
        'category_id',
        'sub_category_id',
        'brand_id',
        'product_type',
        'name',
        'slug',
        'short_description',
        'description',
        'thumbnail',
        'sku',
        'stock',
        'low_stock_alert',
        'price',
        'cost_price',
        'discount_type',
        'discount_amount',
        'weight',
        'status',
        'is_featured',
        'is_new',
        'is_best_seller',
        'is_on_sale',
        'sort_order',
        'seo_title',
        'seo_description',
        'rating_avg',
        'rating_count',
    ];

    protected $casts = [
        'product_type' => ProductType::class,
        'stock' => 'integer',
        'low_stock_alert' => 'integer',
        'price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'weight' => 'decimal:2',
        'is_featured' => 'boolean',
        'is_new' => 'boolean',
        'is_best_seller' => 'boolean',
        'is_on_sale' => 'boolean',
        'sort_order' => 'integer',
        'rating_avg' => 'decimal:2',
        'rating_count' => 'integer',
    ];

    /* ---------------- Relationships ---------------- */

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function subCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'sub_category_id');
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function specifications(): HasMany
    {
        return $this->hasMany(ProductSpecification::class)->orderBy('sort_order');
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class)->latest();
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class)->latest();
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(ProductTag::class, 'product_product_tag')->withTimestamps();
    }

    public function dealCampaigns(): BelongsToMany
    {
        return $this->belongsToMany(DealCampaign::class, 'deal_campaign_product')->withTimestamps();
    }

    /* ---------------- Scopes ---------------- */

    public function scopeStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /* ---------------- Accessors ---------------- */

    /**
     * Final price after applying the discount (never below zero).
     */
    public function getFinalPriceAttribute(): float
    {
        $price = (float) $this->price;
        $amount = (float) $this->discount_amount;

        $final = match ($this->discount_type) {
            'fixed' => $price - $amount,
            'percentage' => $price - ($price * $amount / 100),
            default => $price,
        };

        return max(0, round($final, 2));
    }

    public function getHasDiscountAttribute(): bool
    {
        return in_array($this->discount_type, ['fixed', 'percentage'], true)
            && (float) $this->discount_amount > 0;
    }

    public function isSingle(): bool
    {
        return $this->product_type === ProductType::Single;
    }

    public function isVariable(): bool
    {
        return $this->product_type === ProductType::Variable;
    }

    /**
     * Total stock across the product: its own stock (single) or the sum of
     * variant stock (variable). Uses the eager-loaded sum when available.
     */
    public function getTotalStockAttribute(): int
    {
        if ($this->isSingle()) {
            return (int) $this->stock;
        }

        return (int) ($this->variants_sum_stock ?? $this->variants()->sum('stock'));
    }

    /**
     * Public URL of the product thumbnail (or the primary gallery image).
     */
    public function getThumbnailUrlAttribute(): ?string
    {
        if ($this->thumbnail) {
            return Imageurl($this->thumbnail, 'products');
        }

        $primary = $this->relationLoaded('images')
            ? $this->images->firstWhere('is_primary', true) ?? $this->images->first()
            : $this->images()->orderByDesc('is_primary')->first();

        return $primary ? Imageurl($primary->image, 'products') : null;
    }
}
