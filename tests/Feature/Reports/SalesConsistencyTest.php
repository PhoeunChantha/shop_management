<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ReturnRequest;
use App\Models\User;
use App\Services\Admin\DashboardService;
use App\Services\Admin\OverviewReportService;
use App\Services\Admin\Reports\ReportFilters;
use App\Services\Admin\Reports\SalesLedger;
use App\Services\Admin\SalesReportService;
use Carbon\CarbonImmutable;
use Database\Seeders\RolePermissionSeeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * One mixed dataset; Dashboard, Overview and Sales must agree on every sales
 * and refund figure, and every breakdown must sum back to the ledger.
 */
beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-09-30 12:00:00'));
    $this->seed(RolePermissionSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $apparel = Category::create(['name' => 'Apparel', 'slug' => 'apparel']);
    $caps = Category::create(['name' => 'Caps', 'slug' => 'caps']);
    $shirt = Product::factory()->create(['name' => 'Shirt', 'sku' => 'SH-1', 'category_id' => $apparel->id, 'product_type' => 'single']);
    $cap = Product::factory()->create(['name' => 'Cap', 'sku' => 'CP-1', 'category_id' => $caps->id, 'product_type' => 'single']);

    $order = function (OrderStatus $status, PaymentStatus $payment, string $placed, array $lines, array $extra = []) {
        $subtotal = array_sum(array_map(fn ($l) => $l[1] * $l[2], $lines));
        $o = Order::factory()->create(array_merge([
            'status' => $status->value,
            'payment_status' => $payment->value,
            'payment_method' => 'aba',
            'placed_at' => $placed,
            'subtotal' => $subtotal,
            'discount_total' => 0,
            'tax_total' => 0,
            'shipping_total' => 0,
            'grand_total' => $subtotal,
        ], $extra));
        foreach ($lines as [$product, $price, $qty, $cost]) {
            $o->details()->create([
                'product_id' => $product?->id, 'name' => $product?->name ?? 'Gone', 'sku' => $product?->sku,
                'price' => $price, 'quantity' => $qty, 'line_total' => $price * $qty, 'unit_cost' => $cost,
            ]);
        }

        return $o;
    };

    // Plain paid order with a coupon, tax and shipping (100 − 10 + 9 + 5 = 104).
    $order(OrderStatus::Delivered, PaymentStatus::Paid, '2026-09-20 10:00', [[$shirt, 25, 4, 10]],
        ['discount_total' => 10, 'tax_total' => 9, 'shipping_total' => 5, 'grand_total' => 104, 'customer_email' => 'a@x.test']);
    // Two-product order, partially refunded through a return (30 recorded).
    $partial = $order(OrderStatus::Delivered, PaymentStatus::PartiallyRefunded, '2026-09-21 10:00', [[$shirt, 20, 2, 10], [$cap, 10, 4, null]],
        ['payment_method' => 'wallet', 'customer_email' => 'b@x.test']);
    // Fully refunded by admin, no recorded amount → implied 50.
    $order(OrderStatus::Refunded, PaymentStatus::Refunded, '2026-09-22 10:00', [[$cap, 10, 5, 4]], ['customer_email' => 'a@x.test']);
    // Cancelled but still paid: a sale, no invented refund.
    $order(OrderStatus::Cancelled, PaymentStatus::Paid, '2026-09-23 10:00', [[$cap, 12, 1, 4]], ['customer_email' => 'c@x.test']);
    // Unpaid orders: never sales.
    $order(OrderStatus::Pending, PaymentStatus::Unpaid, '2026-09-24 10:00', [[$shirt, 25, 1, 10]]);
    $order(OrderStatus::Cancelled, PaymentStatus::Unpaid, '2026-09-24 11:00', [[$shirt, 25, 1, 10]]);
    // Deleted product line.
    $order(OrderStatus::Paid, PaymentStatus::Paid, '2026-09-25 10:00', [[null, 15, 2, null]], ['payment_method' => 'manual_qr', 'customer_email' => 'd@x.test']);
    // Previous window + older history (returning customer a@x.test).
    $order(OrderStatus::Delivered, PaymentStatus::Paid, '2026-08-20 10:00', [[$shirt, 25, 2, 10]], ['customer_email' => 'a@x.test']);

    ReturnRequest::create([
        'return_number' => 'RMA-1', 'order_id' => $partial->id, 'status' => 'refunded', 'refund_status' => 'refunded',
        'reason' => 'Size', 'requested_amount' => 30, 'refund_amount' => 30, 'requested_at' => '2026-09-25', 'refunded_at' => '2026-09-26',
    ]);

    $this->filters = ['start_date' => '2026-09-01', 'end_date' => '2026-09-30'];
    $this->reportFilters = ReportFilters::fromArray($this->filters);
    $this->ledger = app(SalesLedger::class)->summary($this->reportFilters);
});

it('computes the expected ledger totals for the dataset', function () {
    // Captured: subtotals 100 + 80 + 50 + 12 + 30 = 272; order value 276.
    // Refunds: 30 recorded (partial return) + 50 implied (admin-refunded).
    expect($this->ledger['orders'])->toBe(5)
        ->and($this->ledger['gross_sales'])->toBe(272.0)
        ->and($this->ledger['discounts'])->toBe(10.0)
        ->and($this->ledger['recorded_refunds'])->toBe(30.0)
        ->and($this->ledger['implied_refunds'])->toBe(50.0)
        ->and($this->ledger['refunds'])->toBe(80.0)
        ->and($this->ledger['net_sales'])->toBe(182.0)
        ->and($this->ledger['total_sales'])->toBe(196.0);
});

it('reports identical sales and refund figures on Dashboard, Overview and Sales', function () {
    $overview = app(OverviewReportService::class)->report($this->reportFilters, true)['summary'];
    $sales = app(SalesReportService::class)->report($this->reportFilters, 'summary', true)['summary'];
    $dashboard = app(DashboardService::class)->overview('2026-09-01', '2026-09-30');

    foreach (['orders', 'gross_sales', 'discounts', 'recorded_refunds', 'implied_refunds', 'refunds',
        'merchandise_refunds', 'tax_shipping_refunds', 'net_sales', 'tax', 'shipping', 'total_sales', 'average_order_value'] as $key) {
        expect($overview[$key])->toBe($this->ledger[$key], "overview {$key}")
            ->and($sales[$key])->toBe($this->ledger[$key], "sales {$key}")
            ->and($dashboard['sales'][$key])->toBe($this->ledger[$key], "dashboard {$key}");
    }

    $revenue = collect($dashboard['kpis'])->firstWhere('label', 'Revenue');
    $orders = collect($dashboard['kpis'])->firstWhere('label', 'Orders');
    expect($revenue['raw'])->toBe($this->ledger['total_sales'])
        ->and($orders['raw'])->toBe($this->ledger['orders'])
        ->and(round(array_sum($dashboard['chart']['values']), 2))->toBe($this->ledger['total_sales']);
});

it('reconciles every time series with the summary', function () {
    $overview = app(OverviewReportService::class)->report($this->reportFilters, false);
    $series = app(SalesReportService::class)->report($this->reportFilters, 'summary', false)['series'];

    expect(round(array_sum($overview['chart']['total_sales']), 2))->toBe($this->ledger['total_sales'])
        ->and(round(array_sum(array_column($series, 'net_sales')), 2))->toBe($this->ledger['net_sales'])
        ->and(round(array_sum(array_column($series, 'refunds')), 2))->toBe($this->ledger['refunds'])
        ->and(array_sum(array_column($series, 'units')))->toBe(18);

    // Monthly bucketing for long windows sums to the same totals.
    $year = ReportFilters::fromArray(['start_date' => '2026-01-01', 'end_date' => '2026-12-31']);
    $monthly = app(SalesLedger::class)->series($year);
    expect(count($monthly))->toBe(12)
        ->and(round(array_sum(array_column($monthly, 'total_sales')), 2))->toBe(app(SalesLedger::class)->summary($year)['total_sales']);
});

it('makes every sales breakdown sum back to the ledger', function () {
    $service = app(SalesReportService::class);
    $sum = fn (string $view, string $key) => round(collect($service->exportRows($this->reportFilters, $view, true))
        ->skip(1)->sum(fn ($row) => (float) $row[$key]), 2);

    // Products / categories: column 7 = net sales (allocated discounts + refunds).
    expect($sum('products', 7))->toBe($this->ledger['net_sales'])
        ->and($sum('products', 6))->toBe($this->ledger['merchandise_refunds'])
        ->and($sum('categories', 6))->toBe($this->ledger['net_sales'])
        // Customers / methods: net (6) and total (7).
        ->and($sum('customers', 6))->toBe($this->ledger['net_sales'])
        ->and($sum('customers', 7))->toBe($this->ledger['total_sales'])
        ->and($sum('methods', 7))->toBe($this->ledger['total_sales'])
        // Transactions: total (13).
        ->and($sum('orders', 13))->toBe($this->ledger['total_sales']);
});

it('splits customers into new and returning by prior captured orders', function () {
    $mix = app(OverviewReportService::class)->report($this->reportFilters, false)['customerMix'];

    expect($mix['returning']['customers'])->toBe(1) // a@x.test bought in August
        ->and($mix['new']['customers'])->toBe(3);
});

it('reports cost and margin only when finance access is granted', function () {
    $withFinance = app(OverviewReportService::class)->report($this->reportFilters, true);
    $without = app(OverviewReportService::class)->report($this->reportFilters, false);

    // COGS = 4×10 + 2×10 + 5×4 + 1×4; cap/deleted lines without cost are flagged.
    expect($withFinance['finance']['cogs'])->toBe(84.0)
        ->and($withFinance['finance']['uncosted_units'])->toBe(6)
        ->and($withFinance['finance']['gross_profit'])->toBe(round($this->ledger['net_sales'] - 84, 2))
        ->and($without['finance'])->toBeNull()
        ->and($without['comparison'])->not->toHaveKey('gross_profit');
});

it('hides profit and cost from users without the finance permission', function () {
    $role = Role::create(['name' => 'analyst', 'guard_name' => 'web']);
    $role->givePermissionTo(['view reports', 'view sales reports']);
    $analyst = User::factory()->create()->assignRole($role);
    $admin = User::factory()->create()->assignRole('admin');

    $this->actingAs($analyst)->get(route('admin.reports.index', $this->filters))
        ->assertOk()->assertDontSee('Gross profit')->assertDontSee('Cost of goods');
    $this->actingAs($analyst)->get(route('admin.reports.sales', ['view' => 'products'] + $this->filters))
        ->assertOk()->assertDontSee('Margin');
    $csv = $this->actingAs($analyst)->get(route('admin.reports.sales.export', ['view' => 'products'] + $this->filters))->streamedContent();
    expect($csv)->not->toContain('COGS');

    $this->actingAs($admin)->get(route('admin.reports.index', $this->filters))
        ->assertOk()->assertSee('Gross profit');
    $this->actingAs($admin)->get(route('admin.reports.sales', ['view' => 'products'] + $this->filters))
        ->assertOk()->assertSee('Margin');
});

it('renders every sales tab and its export', function (string $view) {
    $admin = User::factory()->create()->assignRole('admin');

    $this->actingAs($admin)->get(route('admin.reports.sales', ['view' => $view, 'search' => 'a'] + $this->filters))->assertOk();
    $this->actingAs($admin)->get(route('admin.reports.sales.export', ['view' => $view] + $this->filters))->assertOk();
})->with(['summary', 'products', 'categories', 'customers', 'methods', 'orders']);
