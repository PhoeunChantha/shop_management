<?php

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\Admin\ProductReportService;
use Database\Seeders\RolePermissionSeeder;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');

    $this->apparel = Category::create(['name' => 'Apparel', 'slug' => 'apparel']);
    $this->accessories = Category::create(['name' => 'Accessories', 'slug' => 'accessories']);

    $this->shirt = Product::factory()->create([
        'name' => 'Alpha Shirt',
        'sku' => 'ALPHA-1',
        'category_id' => $this->apparel->id,
        'product_type' => 'single',
        'stock' => 3,
        'low_stock_alert' => 10,
        'cost_price' => 10,
    ]);

    $this->cap = Product::factory()->create([
        'name' => 'Beta Cap',
        'sku' => 'BETA-1',
        'category_id' => $this->accessories->id,
        'product_type' => 'single',
        'stock' => 50,
        'low_stock_alert' => 5,
        'cost_price' => 5,
    ]);

    // 10 shirts @ $20 = $200 revenue, $100 COGS, $100 profit, 50% margin.
    $order1 = Order::factory()->create(['payment_status' => 'paid', 'placed_at' => '2026-08-15']);
    $order1->details()->create([
        'product_id' => $this->shirt->id,
        'name' => $this->shirt->name,
        'sku' => $this->shirt->sku,
        'image' => $this->shirt->thumbnail,
        'price' => 20,
        'unit_cost' => 10,
        'quantity' => 10,
        'line_total' => 200,
    ]);

    // 5 caps @ $8 = $40 revenue, $25 COGS, $15 profit, 37.5% margin.
    $order2 = Order::factory()->create(['payment_status' => 'paid', 'placed_at' => '2026-08-16']);
    $order2->details()->create([
        'product_id' => $this->cap->id,
        'name' => $this->cap->name,
        'sku' => $this->cap->sku,
        'image' => $this->cap->thumbnail,
        'price' => 8,
        'unit_cost' => 5,
        'quantity' => 5,
        'line_total' => 40,
    ]);

    $this->range = ['start_date' => '2026-08-01', 'end_date' => '2026-08-30'];
});

it('computes correct summary totals, thumbnail links, and stock warnings', function () {
    $response = $this->actingAs($this->admin)
        ->get(route('admin.reports.products', $this->range));

    $response->assertOk();
    $response->assertSee('Alpha Shirt');
    $response->assertSee('Beta Cap');
    $response->assertSee(route('admin.products.edit', $this->shirt->id), false);

    $report = app(ProductReportService::class)->report($this->range + ['start_date' => '2026-08-01', 'end_date' => '2026-08-30']);

    expect($report['summary']['products'])->toBe(2)
        ->and($report['summary']['units'])->toBe(15)
        ->and($report['summary']['revenue'])->toBe(240.0)
        ->and($report['summary']['cogs'])->toBe(125.0)
        ->and($report['summary']['profit'])->toBe(115.0)
        ->and($report['summary']['margin'])->toBe(47.9);

    $shirtRow = collect($report['products']->items())->firstWhere('sku', 'ALPHA-1');
    expect($shirtRow['stock'])->toBe(3)
        ->and($shirtRow['margin'])->toBe(50.0)
        ->and($shirtRow['product_id'])->toBe($this->shirt->id);
});

it('sorts the table by the requested column and direction', function () {
    $response = $this->actingAs($this->admin)
        ->get(route('admin.reports.products', $this->range + ['sort' => 'quantity', 'direction' => 'asc']));

    $response->assertOk();
    $body = $response->getContent();

    // BETA-1 (5 units) must render before ALPHA-1 (10 units) when sorted ascending by
    // quantity. Compared by SKU, not name — the top-products chart JSON (always
    // revenue-sorted) embeds product names earlier in the page regardless of table sort.
    expect(strpos($body, 'BETA-1'))->toBeLessThan(strpos($body, 'ALPHA-1'));
});

it('filters the report to a single category', function () {
    $response = $this->actingAs($this->admin)
        ->get(route('admin.reports.products', $this->range + ['category_id' => $this->accessories->id]));

    $response->assertOk();
    $response->assertSee('Beta Cap');
    $response->assertDontSee('Alpha Shirt');
});

it('reports period-over-period comparison against the equal-length prior window', function () {
    // Prior period: 5 shirts @ $20 = $100 revenue.
    $prevOrder = Order::factory()->create(['payment_status' => 'paid', 'placed_at' => '2026-07-15']);
    $prevOrder->details()->create([
        'product_id' => $this->shirt->id,
        'name' => $this->shirt->name,
        'sku' => $this->shirt->sku,
        'price' => 20,
        'unit_cost' => 10,
        'quantity' => 5,
        'line_total' => 100,
    ]);

    $report = app(ProductReportService::class)->report($this->range);

    // Current period revenue is $240 vs a $100 same-length prior window => +140%.
    expect($report['comparison']['revenue']['previous'])->toBe(100.0)
        ->and($report['comparison']['revenue']['direction'])->toBe('up')
        ->and($report['comparison']['revenue']['change'])->toBe(140.0);
});

it('flags the CSV export headers with the new stock column', function () {
    $response = $this->actingAs($this->admin)
        ->get(route('admin.reports.products.export', $this->range));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('text/csv');
    expect($response->streamedContent())->toContain('Stock');
});
