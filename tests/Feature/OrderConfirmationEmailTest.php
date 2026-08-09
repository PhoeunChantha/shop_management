<?php

use App\Mail\OrderConfirmationMail;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Services\Frontend\CheckoutService;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Category::create(['name' => 'Tees', 'slug' => 'tees']);
    Brand::create(['name' => 'Acme', 'slug' => 'acme']);
});

function placeOne(): Order
{
    $product = Product::factory()->create([
        'status' => 'active', 'product_type' => 'single', 'stock' => 5,
        'price' => 40, 'discount_type' => null, 'discount_amount' => 0,
    ]);

    return app(CheckoutService::class)->placeOrder([
        'customer' => ['first_name' => 'Sam', 'last_name' => 'Lee', 'email' => 'sam@example.com', 'address' => 'x', 'city' => 'y'],
        'items' => [['id' => $product->id, 'name' => 'x', 'price' => 40, 'size' => null, 'color' => null, 'qty' => 1]],
        'shipping_id' => null,
        'payment' => 'card',
    ]);
}

it('queues an order confirmation email to the customer after placing an order', function () {
    Mail::fake();

    $order = placeOne();

    Mail::assertQueued(OrderConfirmationMail::class, function (OrderConfirmationMail $mail) use ($order) {
        return $mail->order->is($order) && $mail->hasTo('sam@example.com');
    });
});

it('does not send the customer email when the admin disables it', function () {
    Mail::fake();
    Setting::set('order_email_enabled', '0', 'notifications');

    placeOne();

    Mail::assertNotQueued(OrderConfirmationMail::class);
});

it('copies a new order to the admin alert email when configured', function () {
    Mail::fake();
    Setting::set('admin_order_alert_email', 'ops@shop.com', 'notifications');

    placeOne();

    Mail::assertQueued(OrderConfirmationMail::class, fn (OrderConfirmationMail $mail) => $mail->hasTo('ops@shop.com'));
    Mail::assertQueued(OrderConfirmationMail::class, fn (OrderConfirmationMail $mail) => $mail->hasTo('sam@example.com'));
});

it('applies the configured sender identity to the email', function () {
    Setting::set('mail_from_name', 'Shop Team', 'notifications');
    Setting::set('mail_from_address', 'orders@shop.com', 'notifications');

    $order = placeOne();
    $from = (new OrderConfirmationMail($order))->envelope()->from;

    expect($from?->address)->toBe('orders@shop.com')
        ->and($from?->name)->toBe('Shop Team');
});

it('builds the confirmation email with subject and PDF invoice attachment', function () {
    $order = placeOne();

    $mail = new OrderConfirmationMail($order);
    $built = $mail->render(); // renders markdown view without error

    expect($built)->toContain($order->order_number);
    expect($mail->envelope()->subject)->toContain($order->order_number);
    expect($mail->attachments())->toHaveCount(1);
});
