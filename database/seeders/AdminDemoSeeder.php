<?php

namespace Database\Seeders;

use App\Enums\CouponType;
use App\Enums\ShippingRateType;
use App\Models\AbandonedCart;
use App\Models\AdminNotification;
use App\Models\AdminSavedView;
use App\Models\Announcement;
use App\Models\Banner;
use App\Models\Collection as ProductCollection;
use App\Models\Coupon;
use App\Models\CustomerProfile;
use App\Models\CustomerTag;
use App\Models\DealCampaign;
use App\Models\MediaAsset;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\PurchaseOrder;
use App\Models\ReturnRequest;
use App\Models\Review;
use App\Models\ShippingMethod;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\TaxRule;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AdminDemoSeeder extends Seeder
{
    private const IMAGES = [
        'hero' => 'https://images.unsplash.com/photo-1441986300917-64674bd600d8?auto=format&fit=crop&w=1600&q=85',
        'sale' => 'https://images.unsplash.com/photo-1483985988355-763728e1935b?auto=format&fit=crop&w=1600&q=85',
        'collection' => 'https://images.unsplash.com/photo-1496747611176-843222e1e57c?auto=format&fit=crop&w=1200&q=85',
        'basics' => 'https://images.unsplash.com/photo-1523381210434-271e8be1f52b?auto=format&fit=crop&w=1200&q=85',
        'media' => 'https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?auto=format&fit=crop&w=1200&q=85',
    ];

    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            UserSeeder::class,
            CategorySeeder::class,
            SizeSeeder::class,
            ColorSeeder::class,
            BrandSeeder::class,
            ProductTagSeeder::class,
            ProductSeeder::class,
            SettingSeeder::class,
            ContentSeeder::class,
        ]);

        $this->seedTeamAccounts();
        $this->ensureTransactionalSamples();
        $this->seedShippingAndTaxes();
        $this->seedStorefrontManagement();
        $this->seedMarketingSamples();
        $this->seedMediaLibrary();
        $this->seedCustomerCrm();
        $this->seedPurchasing();
        $this->seedReturns();
        $this->seedAbandonedCarts();
        $this->seedOrderEvents();
        $this->seedNotifications();
        $this->seedSavedViews();
    }

    private function seedTeamAccounts(): void
    {
        $manager = Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);
        $staff = Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web']);

        $manager->syncPermissions(Permission::query()->where('name', 'not like', 'delete %')->get());
        $staff->syncPermissions(Permission::query()
            ->whereIn('name', [
                'view products', 'view orders', 'edit orders', 'view customers',
                'view reviews', 'edit reviews', 'view returns', 'edit returns',
                'view inventory', 'view media', 'create media', 'view notifications',
            ])
            ->get());

        $accounts = [
            ['name' => 'Store Manager', 'email' => 'manager@example.com', 'role' => 'manager'],
            ['name' => 'Fulfillment Staff', 'email' => 'staff@example.com', 'role' => 'staff'],
        ];

        foreach ($accounts as $account) {
            $user = User::updateOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ],
            );

            $user->syncRoles([$account['role']]);
        }
    }

    private function ensureTransactionalSamples(): void
    {
        if (Order::count() < 25) {
            $this->call(OrderSeeder::class);
        }

        if (Review::count() < 20) {
            $this->call(ReviewSeeder::class);
        }

        if (StockMovement::count() === 0) {
            $this->call(StockMovementSeeder::class);
        }
    }

    private function seedStorefrontManagement(): void
    {
        $banners = [
            [
                'title' => 'Weekend Essentials Drop',
                'kicker' => 'New season',
                'subtitle' => 'Fresh arrivals styled for busy everyday commerce.',
                'cta_text' => 'Shop arrivals',
                'cta_link' => '/shop',
                'image' => self::IMAGES['hero'],
                'sort_order' => 1,
                'status' => true,
            ],
            [
                'title' => 'Clearance Event',
                'kicker' => 'Limited time',
                'subtitle' => 'Move slow stock with a clean promotional banner.',
                'cta_text' => 'View sale',
                'cta_link' => '/shop?flag=sale',
                'image' => self::IMAGES['sale'],
                'sort_order' => 2,
                'status' => true,
            ],
        ];

        foreach ($banners as $banner) {
            Banner::updateOrCreate(['title' => $banner['title']], $banner);
        }

        Announcement::updateOrCreate(
            ['message' => 'Free standard shipping over $75'],
            ['link' => '/shop', 'sort_order' => 1, 'status' => true],
        );

        $products = Product::query()->where('status', 'active')->limit(8)->pluck('id');
        $collections = [
            ['name' => 'Premium Basics', 'slug' => 'premium-basics', 'image' => self::IMAGES['basics'], 'description' => 'Core products for clean merchandising.', 'sort_order' => 1],
            ['name' => 'Editorial Picks', 'slug' => 'editorial-picks', 'image' => self::IMAGES['collection'], 'description' => 'Featured products for homepage and campaign use.', 'sort_order' => 2],
        ];

        foreach ($collections as $data) {
            $collection = ProductCollection::updateOrCreate(['slug' => $data['slug']], $data + ['status' => true]);
            $collection->products()->syncWithoutDetaching(
                $products->mapWithKeys(fn (int $productId, int $index): array => [$productId => ['sort_order' => $index + 1]])->all(),
            );
        }
    }

    private function seedShippingAndTaxes(): void
    {
        $shippingMethods = [
            [
                'name' => 'Standard Delivery',
                'description' => 'Reliable ground delivery for most orders.',
                'type' => ShippingRateType::FreeOver,
                'rate' => 6.95,
                'free_over_amount' => 75,
                'delivery_time' => '2-4 business days',
                'sort_order' => 1,
                'status' => true,
            ],
            [
                'name' => 'Express Delivery',
                'description' => 'Priority dispatch for urgent orders.',
                'type' => ShippingRateType::Flat,
                'rate' => 14.95,
                'free_over_amount' => null,
                'delivery_time' => '1-2 business days',
                'sort_order' => 2,
                'status' => true,
            ],
            [
                'name' => 'Store Pickup',
                'description' => 'No-cost pickup for local customers.',
                'type' => ShippingRateType::Free,
                'rate' => 0,
                'free_over_amount' => null,
                'delivery_time' => 'Same day',
                'sort_order' => 3,
                'status' => true,
            ],
        ];

        foreach ($shippingMethods as $method) {
            ShippingMethod::updateOrCreate(['name' => $method['name']], $method);
        }

        $taxRules = [
            ['name' => 'US Sales Tax', 'rate' => 7.5, 'is_inclusive' => false, 'country' => 'US', 'sort_order' => 1, 'status' => true],
            ['name' => 'Cambodia VAT', 'rate' => 10, 'is_inclusive' => false, 'country' => 'KH', 'sort_order' => 2, 'status' => true],
            ['name' => 'EU VAT Sample', 'rate' => 20, 'is_inclusive' => true, 'country' => 'EU', 'sort_order' => 3, 'status' => false],
        ];

        foreach ($taxRules as $rule) {
            TaxRule::updateOrCreate(['name' => $rule['name']], $rule);
        }
    }

    private function seedMarketingSamples(): void
    {
        $coupons = [
            ['code' => 'WELCOME15', 'type' => CouponType::Percentage, 'value' => 15, 'min_spend' => 50, 'max_discount' => 35, 'usage_limit' => 500, 'used_count' => 38],
            ['code' => 'FREESHIP75', 'type' => CouponType::Fixed, 'value' => 6.95, 'min_spend' => 75, 'max_discount' => null, 'usage_limit' => null, 'used_count' => 124],
            ['code' => 'VIP25', 'type' => CouponType::Percentage, 'value' => 25, 'min_spend' => 120, 'max_discount' => 60, 'usage_limit' => 100, 'used_count' => 12],
        ];

        foreach ($coupons as $coupon) {
            Coupon::updateOrCreate(
                ['code' => $coupon['code']],
                $coupon + [
                    'starts_at' => now()->subDays(7),
                    'expires_at' => now()->addDays(45),
                    'status' => true,
                ],
            );
        }

        $productIds = Product::query()->where('status', 'active')->limit(6)->pluck('id');
        $deals = [
            ['title' => 'Flash Deal Weekend', 'slug' => 'flash-deal-weekend', 'type' => 'flash', 'badge' => 'Up to 35% off', 'discount_type' => 'percentage', 'discount_value' => 35, 'ends_at' => now()->addDays(5), 'priority' => 1],
            ['title' => 'Deal of the Day', 'slug' => 'deal-of-the-day', 'type' => 'daily', 'badge' => 'Today only', 'discount_type' => 'fixed', 'discount_value' => 10, 'ends_at' => now()->addDay(), 'priority' => 2],
            ['title' => 'Clearance Sale', 'slug' => 'clearance-sale', 'type' => 'clearance', 'badge' => 'Final stock', 'discount_type' => 'percentage', 'discount_value' => 45, 'ends_at' => now()->addDays(14), 'priority' => 3],
        ];

        foreach ($deals as $deal) {
            $campaign = DealCampaign::updateOrCreate(
                ['slug' => $deal['slug']],
                $deal + [
                    'image' => self::IMAGES['sale'],
                    'summary' => 'Admin sample promotion ready for product merchandising review.',
                    'starts_at' => now()->subDay(),
                    'cta_text' => 'Shop deal',
                    'cta_url' => '/shop',
                    'meta_title' => $deal['title'],
                    'meta_description' => 'Sample SEO description for '.$deal['title'].'.',
                    'status' => true,
                ],
            );

            $campaign->products()->syncWithoutDetaching($productIds->all());
        }
    }

    private function seedMediaLibrary(): void
    {
        $assets = [
            ['folder' => 'products', 'filename' => self::IMAGES['media'], 'original_name' => 'editorial-product-shot.jpg', 'width' => 1200, 'height' => 800, 'alt_text' => 'Editorial product styling image'],
            ['folder' => 'banners', 'filename' => self::IMAGES['hero'], 'original_name' => 'homepage-banner.jpg', 'width' => 1600, 'height' => 900, 'alt_text' => 'Homepage campaign banner'],
            ['folder' => 'deals', 'filename' => self::IMAGES['sale'], 'original_name' => 'flash-sale-campaign.jpg', 'width' => 1600, 'height' => 900, 'alt_text' => 'Flash sale campaign artwork'],
        ];

        $adminId = User::role('admin')->value('id');

        foreach ($assets as $asset) {
            MediaAsset::updateOrCreate(
                ['filename' => $asset['filename'], 'folder' => $asset['folder']],
                $asset + [
                    'user_id' => $adminId,
                    'mime_type' => 'image/jpeg',
                    'size' => 428000,
                    'original_size' => 512000,
                    'optimized_size' => 428000,
                    'optimization_status' => 'optimized',
                    'optimization_notes' => 'Seed sample asset.',
                ],
            );
        }
    }

    private function seedCustomerCrm(): void
    {
        $tags = CustomerTag::query()->pluck('id', 'name');

        Order::query()
            ->select('customer_name', 'customer_email', 'customer_phone')
            ->whereNotNull('customer_email')
            ->limit(12)
            ->get()
            ->each(function (Order $order, int $index) use ($tags): void {
                $profile = CustomerProfile::updateOrCreate(
                    ['email' => $order->customer_email],
                    [
                        'name' => $order->customer_name,
                        'phone' => $order->customer_phone,
                        'status' => true,
                        'notes' => $index < 3 ? 'High-value review sample customer.' : null,
                    ],
                );

                $tagNames = $index < 3 ? ['VIP'] : ($index % 5 === 0 ? ['Wholesale'] : []);
                $tagIds = collect($tagNames)->map(fn (string $name) => $tags[$name] ?? null)->filter()->all();
                $profile->tags()->sync($tagIds);
            });
    }

    private function seedPurchasing(): void
    {
        $suppliers = collect([
            ['name' => 'Northstar Apparel Supply', 'contact_name' => 'Irene Cole', 'email' => 'orders@northstar.example', 'phone' => '+1 555 0101', 'address' => '210 Warehouse Ave, Seattle, WA'],
            ['name' => 'Pacific Threadworks', 'contact_name' => 'Marcus Lin', 'email' => 'sales@threadworks.example', 'phone' => '+1 555 0102', 'address' => '88 Market Street, Los Angeles, CA'],
            ['name' => 'Urban Packaging Co.', 'contact_name' => 'Nora Wells', 'email' => 'hello@urbanpack.example', 'phone' => '+1 555 0103', 'address' => '44 Fulfillment Road, Austin, TX'],
        ])->map(fn (array $supplier) => Supplier::updateOrCreate(['name' => $supplier['name']], $supplier + ['status' => true]));

        $variants = ProductVariant::with('product')->limit(9)->get();
        $adminId = User::role('admin')->value('id');

        foreach (['PO-DEMO-1001' => 'ordered', 'PO-DEMO-1002' => 'partial', 'PO-DEMO-1003' => 'received'] as $number => $status) {
            $po = PurchaseOrder::updateOrCreate(
                ['po_number' => $number],
                [
                    'supplier_id' => $suppliers->random()->id,
                    'user_id' => $adminId,
                    'status' => $status,
                    'ordered_at' => now()->subDays(10)->toDateString(),
                    'expected_at' => now()->addDays($status === 'received' ? -1 : 8)->toDateString(),
                    'received_at' => $status === 'received' ? now()->subDay()->toDateString() : null,
                    'notes' => 'Sample purchasing workflow for admin review.',
                ],
            );

            $subtotal = 0;
            foreach ($variants->random(min(3, $variants->count())) as $variant) {
                $quantity = random_int(12, 36);
                $received = match ($status) {
                    'received' => $quantity,
                    'partial' => random_int(1, $quantity - 1),
                    default => 0,
                };
                $unitCost = (float) ($variant->cost_price ?? $variant->product?->cost_price ?? 12);
                $lineTotal = round($unitCost * $quantity, 2);
                $subtotal += $lineTotal;

                $po->items()->updateOrCreate(
                    ['variant_id' => $variant->id],
                    [
                        'product_id' => $variant->product_id,
                        'name' => $variant->product?->name ?? 'Product',
                        'sku' => $variant->sku,
                        'quantity_ordered' => $quantity,
                        'quantity_received' => $received,
                        'unit_cost' => $unitCost,
                        'line_total' => $lineTotal,
                    ],
                );
            }

            $po->update(['subtotal' => $subtotal]);
        }
    }

    private function seedReturns(): void
    {
        $orders = Order::with('details')->whereHas('details')->latest()->limit(4)->get();

        foreach ($orders as $index => $order) {
            $detail = $order->details->first();
            if (! $detail) {
                continue;
            }

            $status = ['requested', 'approved', 'received', 'refunded'][$index] ?? 'requested';
            $refundStatus = ['not_refunded', 'pending', 'partial', 'refunded'][$index] ?? 'not_refunded';
            $amount = (float) $detail->line_total;

            $return = ReturnRequest::updateOrCreate(
                ['order_id' => $order->id, 'reason' => 'wrong_size'],
                [
                    'user_id' => $order->user_id,
                    'status' => $status,
                    'refund_status' => $refundStatus,
                    'customer_note' => 'Sample return request for admin workflow review.',
                    'admin_note' => $index > 0 ? 'Checked by support team.' : null,
                    'requested_amount' => $amount,
                    'refund_amount' => $refundStatus === 'refunded' ? $amount : ($refundStatus === 'partial' ? round($amount / 2, 2) : 0),
                    'requested_at' => now()->subDays(6 - $index),
                    'approved_at' => in_array($status, ['approved', 'received', 'refunded'], true) ? now()->subDays(4 - $index) : null,
                    'received_at' => in_array($status, ['received', 'refunded'], true) ? now()->subDays(2) : null,
                    'refunded_at' => $status === 'refunded' ? now()->subDay() : null,
                ],
            );

            $return->items()->updateOrCreate(
                ['order_detail_id' => $detail->id],
                [
                    'name' => $detail->name,
                    'sku' => $detail->sku,
                    'quantity' => 1,
                    'unit_price' => $detail->price,
                    'line_total' => $detail->price,
                    'condition' => $status === 'received' ? 'Inspected - sellable' : 'Awaiting inspection',
                ],
            );
        }
    }

    private function seedAbandonedCarts(): void
    {
        $products = Product::query()->where('status', 'active')->limit(5)->get();
        $samples = [
            ['cart_token' => 'cart-demo-new', 'customer_name' => 'Jenny Park', 'customer_email' => 'jenny.park@example.com', 'status' => 'new', 'days' => 1],
            ['cart_token' => 'cart-demo-contacted', 'customer_name' => 'Oliver Grant', 'customer_email' => 'oliver.grant@example.com', 'status' => 'contacted', 'days' => 3],
            ['cart_token' => 'cart-demo-recovered', 'customer_name' => 'Nina Santos', 'customer_email' => 'nina.santos@example.com', 'status' => 'recovered', 'days' => 8],
        ];

        foreach ($samples as $sample) {
            $cart = AbandonedCart::updateOrCreate(
                ['cart_token' => $sample['cart_token']],
                [
                    'customer_name' => $sample['customer_name'],
                    'customer_email' => $sample['customer_email'],
                    'customer_phone' => '+1 555 0199',
                    'status' => $sample['status'],
                    'last_activity_at' => now()->subDays($sample['days']),
                    'contacted_at' => in_array($sample['status'], ['contacted', 'recovered'], true) ? now()->subDays(2) : null,
                    'recovered_at' => $sample['status'] === 'recovered' ? now()->subDay() : null,
                    'admin_note' => 'Sample cart recovery lead.',
                    'metadata' => ['source' => 'demo-seeder', 'utm' => 'summer-admin-review'],
                ],
            );

            $subtotal = 0;
            foreach ($products->take(2) as $product) {
                $quantity = random_int(1, 2);
                $price = (float) $product->final_price;
                $lineTotal = round($price * $quantity, 2);
                $subtotal += $lineTotal;

                $cart->items()->updateOrCreate(
                    ['product_id' => $product->id],
                    [
                        'name' => $product->name,
                        'sku' => $product->sku,
                        'image' => $product->thumbnail,
                        'quantity' => $quantity,
                        'unit_price' => $price,
                        'line_total' => $lineTotal,
                    ],
                );
            }

            $cart->update(['item_count' => $cart->items()->sum('quantity'), 'subtotal' => $subtotal]);
        }
    }

    private function seedOrderEvents(): void
    {
        $adminId = User::role('admin')->value('id');

        Order::query()->latest()->limit(10)->get()->each(function (Order $order) use ($adminId): void {
            $events = [
                ['type' => 'created', 'title' => 'Order received', 'body' => 'Sample order imported for admin review.'],
                ['type' => 'payment', 'title' => 'Payment checked', 'body' => 'Payment status reviewed by the team.'],
                ['type' => 'fulfilment', 'title' => 'Fulfillment note', 'body' => 'Warehouse workflow sample event.'],
            ];

            foreach ($events as $event) {
                $order->events()->firstOrCreate(
                    ['type' => $event['type'], 'title' => $event['title']],
                    $event + ['user_id' => $adminId],
                );
            }
        });
    }

    private function seedNotifications(): void
    {
        $lowStockProduct = Product::query()->orderBy('stock')->first();
        $return = ReturnRequest::query()->latest()->first();
        $deal = DealCampaign::query()->where('ends_at', '>=', now())->orderBy('ends_at')->first();
        $cart = AbandonedCart::query()->where('status', 'new')->first();

        $notifications = [
            ['fingerprint' => 'demo-low-stock', 'type' => 'low_stock', 'priority' => 'warning', 'title' => 'Low stock needs attention', 'body' => ($lowStockProduct?->name ?? 'A product').' is near its alert threshold.', 'url' => '/admin/inventory', 'source' => $lowStockProduct],
            ['fingerprint' => 'demo-return-review', 'type' => 'return_request', 'priority' => 'info', 'title' => 'Return request waiting for review', 'body' => 'Sample return workflow is ready to inspect.', 'url' => $return ? '/admin/returns/'.$return->id : '/admin/returns', 'source' => $return],
            ['fingerprint' => 'demo-deal-expiring', 'type' => 'deal_expiring', 'priority' => 'warning', 'title' => 'Deal campaign expiring soon', 'body' => ($deal?->title ?? 'A campaign').' is close to its end date.', 'url' => '/admin/deals', 'source' => $deal],
            ['fingerprint' => 'demo-abandoned-cart', 'type' => 'abandoned_cart', 'priority' => 'critical', 'title' => 'New recoverable cart', 'body' => 'A sample cart recovery opportunity is available.', 'url' => $cart ? '/admin/abandoned-carts/'.$cart->id : '/admin/abandoned-carts', 'source' => $cart],
        ];

        foreach ($notifications as $notification) {
            $source = $notification['source'] ?? null;

            AdminNotification::updateOrCreate(
                ['fingerprint' => $notification['fingerprint']],
                [
                    'type' => $notification['type'],
                    'priority' => $notification['priority'],
                    'title' => $notification['title'],
                    'body' => $notification['body'],
                    'url' => $notification['url'],
                    'source_type' => $source ? $source::class : null,
                    'source_id' => $source?->id,
                    'read_at' => null,
                    'expires_at' => Carbon::now()->addDays(30),
                ],
            );
        }
    }

    private function seedSavedViews(): void
    {
        $views = [
            ['scope' => 'products', 'name' => 'Low stock', 'route_name' => 'admin.products.index', 'query' => ['stock' => 'low_stock'], 'icon' => 'fa-box-open', 'color' => '#c9a227', 'sort_order' => 1],
            ['scope' => 'products', 'name' => 'No image review', 'route_name' => 'admin.products.index', 'query' => ['status' => 'draft'], 'icon' => 'fa-image', 'color' => '#64748b', 'sort_order' => 2],
            ['scope' => 'products', 'name' => 'On sale', 'route_name' => 'admin.products.index', 'query' => ['flag' => 'on_sale'], 'icon' => 'fa-tags', 'color' => '#dc2626', 'sort_order' => 3],
            ['scope' => 'orders', 'name' => 'Unpaid', 'route_name' => 'admin.orders.index', 'query' => ['payment_status' => 'unpaid'], 'icon' => 'fa-credit-card', 'color' => '#c9a227', 'sort_order' => 1],
            ['scope' => 'orders', 'name' => 'Processing', 'route_name' => 'admin.orders.index', 'query' => ['status' => 'processing'], 'icon' => 'fa-truck-fast', 'color' => '#2563eb', 'sort_order' => 2],
            ['scope' => 'customers', 'name' => 'VIP buyers', 'route_name' => 'admin.customers.index', 'query' => ['spend' => 'vip'], 'icon' => 'fa-crown', 'color' => '#7c3aed', 'sort_order' => 1],
            ['scope' => 'customers', 'name' => 'Repeat buyers', 'route_name' => 'admin.customers.index', 'query' => ['spend' => 'repeat'], 'icon' => 'fa-repeat', 'color' => '#0f766e', 'sort_order' => 2],
            ['scope' => 'returns', 'name' => 'Pending refund', 'route_name' => 'admin.returns.index', 'query' => ['refund_status' => 'pending'], 'icon' => 'fa-money-bill-transfer', 'color' => '#dc2626', 'sort_order' => 1],
            ['scope' => 'returns', 'name' => 'Requested', 'route_name' => 'admin.returns.index', 'query' => ['status' => 'requested'], 'icon' => 'fa-rotate-left', 'color' => '#c9a227', 'sort_order' => 2],
            ['scope' => 'media', 'name' => 'Product assets', 'route_name' => 'admin.media.index', 'query' => ['folder' => 'products'], 'icon' => 'fa-photo-film', 'color' => '#0f766e', 'sort_order' => 1],
            ['scope' => 'media', 'name' => 'Banner assets', 'route_name' => 'admin.media.index', 'query' => ['folder' => 'banners'], 'icon' => 'fa-images', 'color' => '#2563eb', 'sort_order' => 2],
        ];

        foreach ($views as $view) {
            AdminSavedView::updateOrCreate(
                ['user_id' => null, 'scope' => $view['scope'], 'name' => $view['name']],
                $view + ['is_global' => true],
            );
        }
    }
}
