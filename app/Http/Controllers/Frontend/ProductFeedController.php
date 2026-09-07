<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\Admin\SettingService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class ProductFeedController extends Controller
{
    private const CACHE_TTL_SECONDS = 1800;

    /**
     * Public product catalog feed (Google/Meta "g:" RSS format), consumed by
     * Meta Commerce Manager and Google Merchant Center as a scheduled feed
     * URL — no API credentials needed on either side. Cached briefly since
     * it's polled periodically, not on every storefront request.
     */
    public function index(SettingService $settings): Response
    {
        $currency = $settings->currency()['code'];

        $items = Cache::remember('product_feed.items.'.$currency, self::CACHE_TTL_SECONDS, function () use ($currency) {
            return Product::query()
                ->with(['brand:id,name', 'images:id,product_id,image,is_primary,sort_order'])
                ->withSum('variants', 'stock')
                ->where('status', 'active')
                ->orderBy('id')
                ->get()
                ->map(fn (Product $product): ?array => $this->mapItem($product, $currency))
                ->filter()
                ->values()
                ->all();
        });

        return response()
            ->view('frontend.product-feed', ['items' => $items])
            ->header('Content-Type', 'application/xml');
    }

    /**
     * @return array<string, mixed>|null null when the product has no image
     *                                   (Meta/Google reject items without one)
     */
    private function mapItem(Product $product, string $currency): ?array
    {
        $image = $product->thumbnail_url;

        if (blank($image) || blank($product->slug)) {
            return null;
        }

        return [
            'id' => 'product-'.$product->id,
            'title' => $product->name,
            'description' => trim(strip_tags((string) ($product->short_description ?: $product->description))) ?: $product->name,
            'link' => route('frontend.shop.show', $product->slug),
            'image_link' => $image,
            'availability' => $product->total_stock > 0 ? 'in stock' : 'out of stock',
            'price' => number_format((float) $product->final_price, 2, '.', '').' '.$currency,
            'brand' => $product->brand?->name ?: config('app.name'),
            'condition' => 'new',
        ];
    }
}
