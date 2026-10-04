<?php

use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\User;
use App\Services\Admin\OrderReportService;
use App\Services\Admin\Reports\ReportFilters;
use Carbon\CarbonImmutable;
use Database\Seeders\RolePermissionSeeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-09-30 12:00:00'));
    $this->seed(RolePermissionSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $make = fn (OrderStatus $s, PaymentStatus $p, string $placed, array $extra = []) => Order::factory()->create(array_merge([
        'status' => $s->value, 'payment_status' => $p->value, 'fulfillment_status' => FulfillmentStatus::Unfulfilled->value,
        'placed_at' => $placed, 'shipped_at' => null, 'fulfilled_at' => null, 'grand_total' => 50,
    ], $extra));

    $make(OrderStatus::Pending, PaymentStatus::Unpaid, '2026-09-29 18:00');
    $make(OrderStatus::Processing, PaymentStatus::Paid, '2026-09-27 10:00');
    // Shipped 24h after placement.
    $make(OrderStatus::Shipped, PaymentStatus::Paid, '2026-09-20 10:00', ['shipped_at' => '2026-09-21 10:00']);
    // Delivered: shipped after 72h, fulfilled after 96h.
    $make(OrderStatus::Delivered, PaymentStatus::Paid, '2026-09-10 10:00', [
        'shipped_at' => '2026-09-13 10:00', 'fulfilled_at' => '2026-09-14 10:00', 'fulfillment_status' => FulfillmentStatus::Fulfilled->value,
    ]);
    $make(OrderStatus::Cancelled, PaymentStatus::Unpaid, '2026-09-12 10:00');
    $make(OrderStatus::Cancelled, PaymentStatus::Paid, '2026-09-13 10:00', ['grand_total' => 80]);
    // Back-dated shipment must not distort the average.
    $make(OrderStatus::Shipped, PaymentStatus::Paid, '2026-09-15 10:00', ['shipped_at' => '2026-09-14 10:00']);
    // Outside the window.
    $make(OrderStatus::Delivered, PaymentStatus::Paid, '2026-07-01 10:00');

    $this->filters = ReportFilters::fromArray(['start_date' => '2026-09-01', 'end_date' => '2026-09-30']);
});

it('counts orders per real status, including unpaid ones', function () {
    $report = app(OrderReportService::class)->report($this->filters, 'summary');
    $count = fn (string $s) => collect($report['statuses'])->firstWhere('status', $s)['count'];

    expect($report['total'])->toBe(7)
        ->and($report['kpis']['pending']['value'])->toBe(1)
        ->and($report['kpis']['processing']['value'])->toBe(1)
        ->and($report['kpis']['shipped']['value'])->toBe(2)
        ->and($report['kpis']['delivered']['value'])->toBe(1)
        ->and($report['kpis']['cancelled']['value'])->toBe(2)
        ->and($count('refunded'))->toBe(0)
        ->and(collect($report['statuses'])->pluck('status')->all())->toBe(array_map(fn ($s) => $s->value, OrderStatus::cases()));
});

it('splits cancellations by whether payment was captured', function () {
    $c = app(OrderReportService::class)->report($this->filters, 'summary')['cancellations'];

    expect($c['total'])->toBe(2)
        ->and($c['before_payment'])->toBe(1)
        ->and($c['after_payment'])->toBe(1)
        ->and($c['after_payment_value'])->toBe(80.0);
});

it('measures time to ship and fulfil from recorded timestamps only', function () {
    $t = app(OrderReportService::class)->report($this->filters, 'summary')['timing'];

    // (24h + 72h) / 2; the back-dated shipment is excluded.
    expect($t['ship']['orders'])->toBe(2)
        ->and($t['ship']['avg_hours'])->toBe(48.0)
        ->and($t['ship']['within_48h'])->toBe(50.0)
        ->and($t['fulfil']['orders'])->toBe(1)
        ->and($t['fulfil']['avg_hours'])->toBe(96.0);
});

it('ages the open backlog across all dates', function () {
    $b = app(OrderReportService::class)->report($this->filters, 'summary')['backlog'];

    // Open + not fulfilled: pending (18h), processing (3d+), 2× shipped (>7d), and the July order is delivered (closed).
    expect($b['total'])->toBe(4)
        ->and($b['buckets'][0]['count'])->toBe(1)
        ->and($b['buckets'][2]['count'])->toBe(1)
        ->and($b['buckets'][3]['count'])->toBe(2);
});

it('gates the orders report behind its own permission', function () {
    $role = Role::create(['name' => 'sales-only', 'guard_name' => 'web']);
    $role->givePermissionTo(['view sales reports']);
    $user = User::factory()->create()->assignRole($role);
    $admin = User::factory()->create()->assignRole('admin');

    $this->actingAs($user)->get(route('admin.reports.orders'))->assertForbidden();

    foreach (['summary', 'orders'] as $view) {
        $this->actingAs($admin)->get(route('admin.reports.orders', ['view' => $view, 'start_date' => '2026-09-01', 'end_date' => '2026-09-30']))
            ->assertOk()->assertSee('Orders &amp; Fulfillment', false);
        $this->actingAs($admin)->get(route('admin.reports.orders.export', ['view' => $view]))->assertOk();
    }
});
