<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Services\Admin\OrderService;
use App\Services\Admin\SettingService;
use App\Services\Frontend\CheckoutGuardService;
use Illuminate\Http\Request;

beforeEach(function () {
    $category = Category::firstOrCreate(['slug' => 'tees'], ['name' => 'Tees']);
    $this->product = Product::factory()->create([
        'category_id' => $category->id, 'status' => 'active', 'product_type' => 'single', 'stock' => 10,
        'price' => 20, 'discount_type' => null, 'discount_amount' => 0,
    ]);
});

function orderForm(Product $product, array $overrides = []): array
{
    return array_merge([
        'email' => 'buyer@example.com', 'first_name' => 'A', 'last_name' => 'B', 'address' => 'x', 'city' => 'y',
        'payment' => 'aba_qr',
        'items' => json_encode([['id' => $product->id, 'qty' => 1]]),
    ], $overrides);
}

function unpaidOrder(array $attributes = []): Order
{
    return Order::create(array_merge([
        'customer_name' => 'A B', 'customer_email' => 'buyer@example.com', 'shipping_address' => 'x', 'shipping_city' => 'y',
        'subtotal' => 20, 'discount_total' => 0, 'shipping_total' => 0, 'tax_total' => 0, 'grand_total' => 20,
        'status' => OrderStatus::Pending, 'payment_method' => 'aba_qr', 'payment_status' => PaymentStatus::Unpaid,
        'placed_at' => now(), 'ip_address' => '10.0.0.1',
    ], $attributes));
}

it('sends guests to sign in when guest checkout is turned off', function () {
    Setting::set('guest_checkout', '0', 'checkout');

    $this->get(route('frontend.checkout.index'))->assertRedirect(route('frontend.login'));
    $this->post(route('frontend.checkout.store'), orderForm($this->product))->assertRedirect(route('frontend.login'));

    expect(Order::count())->toBe(0);

    // Signed-in customers can still check out.
    $this->actingAs(User::factory()->create())->get(route('frontend.checkout.index'))->assertOk();
});

it('allows guest checkout by default and stores the visitor IP', function () {
    $this->get(route('frontend.checkout.index'))->assertOk();

    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.7'])
        ->post(route('frontend.checkout.store'), orderForm($this->product))
        ->assertRedirect();

    expect(Order::sole()->ip_address)->toBe('203.0.113.7');
});

it('rejects a submission that filled the hidden bot-trap field', function () {
    $this->from(route('frontend.checkout.index'))
        ->post(route('frontend.checkout.store'), orderForm($this->product, [CheckoutGuardService::HONEYPOT => 'https://spam.example']))
        ->assertRedirect(route('frontend.checkout.index'))
        ->assertSessionHas('error');

    expect(Order::count())->toBe(0)->and($this->product->fresh()->stock)->toBe(10);
});

it('caps open unpaid orders per email and per IP', function () {
    unpaidOrder();
    unpaidOrder();
    unpaidOrder(); // default limit: 3

    $this->from(route('frontend.checkout.index'))
        ->post(route('frontend.checkout.store'), orderForm($this->product))
        ->assertSessionHas('error');

    // Different email, same network.
    $this->from(route('frontend.checkout.index'))
        ->withServerVariables(['REMOTE_ADDR' => '10.0.0.1'])
        ->post(route('frontend.checkout.store'), orderForm($this->product, ['email' => 'other@example.com']))
        ->assertSessionHas('error');

    expect(Order::count())->toBe(3);

    // 0 turns the cap off.
    Setting::set('checkout_max_unpaid_orders', '0', 'checkout');
    $this->post(route('frontend.checkout.store'), orderForm($this->product));
    expect(Order::count())->toBe(4);
});

it('treats a blank limit as the default, not as "off"', function () {
    Setting::set('checkout_max_unpaid_orders', '', 'checkout');
    Setting::set('checkout_unpaid_expiry_hours', '', 'checkout');

    expect(app(SettingService::class)->checkoutSecurity())
        ->toMatchArray(['max_unpaid_orders' => 3, 'unpaid_expiry_hours' => 24]);
});

it('cancels expired unpaid orders and returns their stock', function () {
    $this->post(route('frontend.checkout.store'), orderForm($this->product, ['items' => json_encode([['id' => $this->product->id, 'qty' => 2]])]));
    $old = Order::sole();
    $old->forceFill(['placed_at' => now()->subHours(30)])->save();
    expect($this->product->fresh()->stock)->toBe(8);

    $recent = unpaidOrder(['placed_at' => now()->subHours(2)]);
    $paid = unpaidOrder(['placed_at' => now()->subHours(48), 'payment_status' => PaymentStatus::Paid, 'paid_at' => now()]);

    $this->artisan('shop:cancel-unpaid-orders')->assertSuccessful();

    expect($old->fresh()->status)->toBe(OrderStatus::Cancelled)
        ->and($this->product->fresh()->stock)->toBe(10)
        ->and($recent->fresh()->status)->toBe(OrderStatus::Pending)
        ->and($paid->fresh()->status)->toBe(OrderStatus::Pending);

    // Running again changes nothing (the stock is not returned twice).
    expect(app(OrderService::class)->cancelExpiredUnpaid(24))->toBe(0)
        ->and($this->product->fresh()->stock)->toBe(10);
});

it('does nothing when unpaid-order expiry is turned off', function () {
    Setting::set('checkout_unpaid_expiry_hours', '0', 'checkout');
    $order = unpaidOrder(['placed_at' => now()->subDays(10)]);

    $this->artisan('shop:cancel-unpaid-orders')->assertSuccessful();

    expect($order->fresh()->status)->toBe(OrderStatus::Pending);
});

it('reads the visitor IP from the tunnel but ignores spoofed forwarding headers', function () {
    $ipFor = function (string $remote, string $forwarded): string {
        $request = Request::create('/', 'GET', [], [], [], ['REMOTE_ADDR' => $remote, 'HTTP_X_FORWARDED_FOR' => $forwarded]);
        Request::setTrustedProxies(['127.0.0.1', '::1'], Request::HEADER_X_FORWARDED_FOR);

        return (string) $request->ip();
    };

    // Via cloudflared (loopback): the IP Cloudflare appended, not the client's fake one.
    expect($ipFor('127.0.0.1', '6.6.6.6, 198.51.100.4'))->toBe('198.51.100.4')
        // Direct connection: forwarding header ignored.
        ->and($ipFor('198.51.100.9', '6.6.6.6'))->toBe('198.51.100.9');
});
