<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AbandonedCart;
use App\Models\AdminNotification;
use App\Models\Coupon;
use App\Models\DealCampaign;
use App\Models\MediaAsset;
use App\Models\Order;
use App\Models\Page;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ReturnRequest;
use App\Models\Review;
use App\Models\Setting;
use App\Models\ShippingMethod;
use App\Models\TaxRule;

final class SetupHealthService
{
    /**
     * @return array<string, mixed>
     */
    public function overview(): array
    {
        $groups = collect([
            $this->catalogChecks(),
            $this->salesChecks(),
            $this->marketingChecks(),
            $this->operationsChecks(),
            $this->contentChecks(),
        ]);

        $checks = $groups->flatMap(fn (array $group): array => $group['checks'])->values();
        $ready = $checks->where('status', 'ready')->count();
        $attention = $checks->where('status', 'attention')->count();
        $critical = $checks->where('status', 'critical')->count();
        $score = $checks->isEmpty() ? 100 : (int) round(($ready / $checks->count()) * 100);

        return [
            'score' => $score,
            'ready' => $ready,
            'attention' => $attention,
            'critical' => $critical,
            'total' => $checks->count(),
            'groups' => $groups->all(),
            'priorityChecks' => $checks
                ->whereIn('status', ['critical', 'attention'])
                ->take(6)
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function catalogChecks(): array
    {
        $productsWithoutImages = Product::query()
            ->where(fn ($query) => $query->whereNull('thumbnail')->orWhere('thumbnail', ''))
            ->count();

        $singleLowStock = Product::query()
            ->where('product_type', 'single')
            ->whereColumn('stock', '<=', 'low_stock_alert')
            ->count();

        $variantLowStock = ProductVariant::query()
            ->whereColumn('stock', '<=', 'low_stock_alert')
            ->count();

        return $this->group('Catalog', 'fa-boxes-stacked', [
            $this->check(
                'Product images',
                $productsWithoutImages,
                'Products without thumbnails',
                'admin.products.index',
                'Add images',
                $productsWithoutImages === 0 ? 'ready' : 'attention',
            ),
            $this->check(
                'Stock readiness',
                $singleLowStock + $variantLowStock,
                'Products or variants at low-stock threshold',
                'admin.inventory.index',
                'Review stock',
                ($singleLowStock + $variantLowStock) === 0 ? 'ready' : 'critical',
            ),
            $this->check(
                'Product catalog',
                Product::query()->where('status', 'active')->count(),
                'Active products available for merchandising',
                'admin.products.index',
                'View products',
                Product::query()->where('status', 'active')->exists() ? 'ready' : 'critical',
                true,
            ),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function salesChecks(): array
    {
        $openOrders = Order::query()
            ->whereIn('status', ['pending', 'paid', 'processing', 'shipped'])
            ->count();

        $returnsWaiting = ReturnRequest::query()
            ->whereIn('status', ['requested', 'approved', 'received'])
            ->whereIn('refund_status', ['not_refunded', 'pending', 'partial'])
            ->count();

        return $this->group('Sales', 'fa-receipt', [
            $this->check(
                'Open orders',
                $openOrders,
                'Orders still moving through fulfillment',
                'admin.orders.index',
                'Review orders',
                $openOrders === 0 ? 'ready' : 'attention',
            ),
            $this->check(
                'Returns queue',
                $returnsWaiting,
                'Return requests waiting for admin action',
                'admin.returns.index',
                'Review returns',
                $returnsWaiting === 0 ? 'ready' : 'attention',
            ),
            $this->check(
                'Cart recovery',
                AbandonedCart::query()->whereIn('status', ['new', 'contacted'])->count(),
                'Recoverable carts with customer contact data',
                'admin.abandoned-carts.index',
                'Open carts',
                'attention',
            ),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function marketingChecks(): array
    {
        $endingDeals = DealCampaign::query()
            ->where('status', true)
            ->whereBetween('ends_at', [now(), now()->addDays(7)])
            ->count();

        return $this->group('Marketing', 'fa-bullhorn', [
            $this->check(
                'Active coupons',
                Coupon::query()->active()->count(),
                'Coupons currently valid for customers',
                'admin.coupons.index',
                'View coupons',
                Coupon::query()->active()->exists() ? 'ready' : 'attention',
                true,
            ),
            $this->check(
                'Deals ending soon',
                $endingDeals,
                'Active offers ending in the next 7 days',
                'admin.deals.index',
                'Review deals',
                $endingDeals === 0 ? 'ready' : 'attention',
            ),
            $this->check(
                'Pending reviews',
                Review::query()->where('status', 'pending')->count(),
                'Customer reviews waiting for moderation',
                'admin.reviews.index',
                'Moderate',
                Review::query()->where('status', 'pending')->exists() ? 'attention' : 'ready',
            ),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function operationsChecks(): array
    {
        $missingSettings = collect([
            'site_name',
            'contact_email',
            'contact_phone',
            'order_prefix',
            'product_sku_prefix',
        ])->filter(fn (string $key): bool => blank(Setting::get($key)))->count();

        return $this->group('Operations', 'fa-sliders', [
            $this->check(
                'Store settings',
                $missingSettings,
                'Required operational settings still blank',
                'admin.settings.index',
                'Open settings',
                $missingSettings === 0 ? 'ready' : 'critical',
            ),
            $this->check(
                'Shipping methods',
                ShippingMethod::query()->where('status', true)->count(),
                'Enabled delivery choices',
                'admin.shipping.index',
                'Review shipping',
                ShippingMethod::query()->where('status', true)->exists() ? 'ready' : 'critical',
                true,
            ),
            $this->check(
                'Tax rules',
                TaxRule::query()->where('status', true)->count(),
                'Enabled tax rules',
                'admin.taxes.index',
                'Review tax',
                TaxRule::query()->where('status', true)->exists() ? 'ready' : 'attention',
                true,
            ),
            $this->check(
                'Unread notifications',
                AdminNotification::query()->unread()->count(),
                'Admin alerts still unread',
                'admin.notifications.index',
                'Open alerts',
                AdminNotification::query()->unread()->exists() ? 'attention' : 'ready',
            ),
            $this->check(
                'Media optimization',
                MediaAsset::query()->whereNotIn('optimization_status', ['optimized', 'kept_original'])->count(),
                'Media files still pending optimization',
                'admin.media.index',
                'Optimize media',
                MediaAsset::query()->whereNotIn('optimization_status', ['optimized', 'kept_original'])->exists() ? 'attention' : 'ready',
            ),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function contentChecks(): array
    {
        $productsMissingSeo = Product::query()
            ->where(fn ($query) => $query
                ->whereNull('seo_title')
                ->orWhere('seo_title', '')
                ->orWhereNull('seo_description')
                ->orWhere('seo_description', ''))
            ->count();

        $pagesMissingSeo = Page::query()
            ->where(fn ($query) => $query
                ->whereNull('seo_title')
                ->orWhere('seo_title', '')
                ->orWhereNull('seo_description')
                ->orWhere('seo_description', ''))
            ->count();

        return $this->group('Content', 'fa-file-lines', [
            $this->check(
                'SEO completion',
                $productsMissingSeo + $pagesMissingSeo,
                'Products or pages missing SEO title/description',
                'admin.seo.index',
                'Fix SEO',
                ($productsMissingSeo + $pagesMissingSeo) === 0 ? 'ready' : 'attention',
            ),
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $checks
     * @return array<string, mixed>
     */
    private function group(string $label, string $icon, array $checks): array
    {
        return [
            'label' => $label,
            'icon' => $icon,
            'checks' => $checks,
            'ready' => collect($checks)->where('status', 'ready')->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function check(
        string $title,
        int $count,
        string $description,
        string $route,
        string $action,
        string $status,
        bool $positiveCount = false,
    ): array {
        return [
            'title' => $title,
            'count' => $count,
            'description' => $description,
            'url' => route($route),
            'action' => $action,
            'status' => $status,
            'positiveCount' => $positiveCount,
        ];
    }
}
