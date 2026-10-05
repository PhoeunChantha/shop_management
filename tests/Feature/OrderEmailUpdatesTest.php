<?php

use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Mail\OrderConfirmationMail;
use App\Mail\OrderStatusMail;
use App\Models\Category;
use App\Models\NewsletterSubscriber;
use App\Models\Order;
use App\Models\Product;
use App\Services\Admin\OrderService;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();
    $category = Category::firstOrCreate(['slug' => 'tees'], ['name' => 'Tees']);
    $this->product = Product::factory()->create([
        'category_id' => $category->id, 'status' => 'active', 'product_type' => 'single', 'stock' => 10,
        'price' => 20, 'discount_type' => null, 'discount_amount' => 0,
    ]);
});

function checkoutForm(Product $product, array $overrides = []): array
{
    return array_merge([
        'email' => 'Buyer@Example.com', 'first_name' => 'A', 'last_name' => 'B', 'address' => 'x', 'city' => 'y',
        'payment' => 'aba_qr',
        'items' => json_encode([['id' => $product->id, 'qty' => 1]]),
    ], $overrides);
}

function markShipped(Order $order): Order
{
    return app(OrderService::class)->updateFulfilment($order, [
        'status' => OrderStatus::Shipped->value,
        'fulfillment_status' => FulfillmentStatus::Unfulfilled->value,
        'payment_status' => PaymentStatus::Paid->value,
        'carrier' => 'J&T',
        'tracking_number' => 'TRK123',
    ]);
}

it('opts the customer in when the box is ticked: saved, subscribed, and emailed on status changes', function () {
    $this->post(route('frontend.checkout.store'), checkoutForm($this->product, ['email_updates' => '1']));

    $order = Order::sole();
    expect($order->email_updates)->toBeTrue()
        ->and(NewsletterSubscriber::where('email', 'buyer@example.com')->exists())->toBeTrue();
    Mail::assertQueued(OrderConfirmationMail::class);

    markShipped($order);

    Mail::assertQueued(OrderStatusMail::class, fn (OrderStatusMail $mail): bool => $mail->hasTo('Buyer@Example.com') && $mail->order->is($order));
});

it('respects an unticked box: no newsletter, no status emails, receipt still sent', function () {
    // Browsers omit unticked checkboxes entirely.
    $this->post(route('frontend.checkout.store'), checkoutForm($this->product));

    $order = Order::sole();
    expect($order->email_updates)->toBeFalse()
        ->and(NewsletterSubscriber::count())->toBe(0);
    Mail::assertQueued(OrderConfirmationMail::class);

    markShipped($order);

    Mail::assertNotQueued(OrderStatusMail::class);
});

it('emails the customer when an unpaid order is cancelled automatically', function () {
    $this->post(route('frontend.checkout.store'), checkoutForm($this->product, ['email_updates' => '1']));
    Order::sole()->forceFill(['placed_at' => now()->subHours(30)])->save();

    $this->artisan('shop:cancel-unpaid-orders')->assertSuccessful();

    Mail::assertQueued(OrderStatusMail::class, fn (OrderStatusMail $mail): bool => $mail->order->status === OrderStatus::Cancelled);
});

it('renders the status email with tracking and the right order link', function () {
    $this->post(route('frontend.checkout.store'), checkoutForm($this->product, ['email_updates' => '1']));
    $order = markShipped(Order::sole());

    $html = (new OrderStatusMail($order))->render();

    expect($html)->toContain($order->order_number)
        ->toContain('Your order is on its way.')
        ->toContain('TRK123')
        ->toContain('/track-order/'.$order->order_number); // guest order → private tracking link
});
