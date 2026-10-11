<?php

use App\Models\Order;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
});

function alertOrder(array $extra = []): Order
{
    $order = Order::factory()->create(array_merge(['customer_name' => 'Dara Sok', 'grand_total' => 47.19], $extra));
    $order->details()->create(['name' => 'Heavy Tee', 'sku' => 'HT-1', 'price' => 20, 'quantity' => 2, 'line_total' => 40]);

    return $order;
}

it('returns only the orders placed after the last one seen', function () {
    $old = alertOrder();
    $new = alertOrder(['customer_name' => 'Sophea Chan']);

    $this->actingAs($this->admin)->getJson(route('admin.orders.alerts', ['after' => $old->id]))
        ->assertOk()
        ->assertJsonPath('latest_id', $new->id)
        ->assertJsonCount(1, 'orders')
        ->assertJsonPath('orders.0.number', $new->order_number)
        ->assertJsonPath('orders.0.customer', 'Sophea Chan')
        ->assertJsonPath('orders.0.items', 2)
        ->assertJsonPath('orders.0.url', route('admin.orders.show', $new->id));
});

it('returns nothing when there is no newer order', function () {
    $order = alertOrder();

    $this->actingAs($this->admin)->getJson(route('admin.orders.alerts', ['after' => $order->id]))
        ->assertOk()
        ->assertJsonPath('latest_id', $order->id)
        ->assertJsonCount(0, 'orders');
});

it('refuses staff without the view orders permission, and guests', function () {
    $this->getJson(route('admin.orders.alerts', ['after' => 0]))->assertUnauthorized();

    $this->actingAs(User::factory()->create())
        ->getJson(route('admin.orders.alerts', ['after' => 0]))
        ->assertForbidden();
});

it('renders the alert on admin pages with popup and sound on by default', function () {
    $html = $this->actingAs($this->admin)->get(route('admin.orders.index'))->assertOk()->getContent();

    expect($html)->toContain('x-data="orderAlertModal"')
        ->toContain('popup: true')
        ->toContain('sound: true');
});

it('follows the popup and sound switches in settings', function () {
    Setting::set('order_alert_sound', '0');
    $html = $this->actingAs($this->admin)->get(route('admin.orders.index'))->getContent();
    expect($html)->toContain('popup: true')->toContain('sound: false');

    Setting::set('order_alert_popup', '0');
    $html = $this->actingAs($this->admin)->get(route('admin.orders.index'))->getContent();
    expect($html)->not->toContain('orderAlertModal');
});

it('offers both switches on the Notifications settings tab', function () {
    $this->actingAs($this->admin)->get(route('admin.settings.index', ['tab' => 'notifications']))
        ->assertOk()
        ->assertSee('name="order_alert_popup"', false)
        ->assertSee('name="order_alert_sound"', false);
});
