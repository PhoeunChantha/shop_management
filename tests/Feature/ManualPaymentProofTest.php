<?php

use App\Enums\OrderStatus;
use App\Helpers\ImageManager;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Services\Admin\OrderService;
use App\Services\Frontend\OrderTrackingService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;

beforeEach(function () {
    $category = Category::firstOrCreate(['slug' => 'tees'], ['name' => 'Tees']);
    $this->product = Product::factory()->create([
        'category_id' => $category->id, 'status' => 'active', 'product_type' => 'single', 'stock' => 10,
        'price' => 20, 'discount_type' => null, 'discount_amount' => 0,
    ]);

    // One manual (QR) method and one online method, as an admin would configure.
    Setting::set('payment_methods', json_encode([
        ['id' => 'aba_qr', 'name' => 'ABA QR', 'code' => 'aba_qr', 'type' => 'manual', 'status' => true, 'sort_order' => 1],
        ['id' => 'card', 'name' => 'Card', 'code' => 'card', 'type' => 'online', 'status' => true, 'sort_order' => 2],
    ]), 'payment');

    $this->uploaded = [];
});

afterEach(function () {
    // Remove the real files ImageManager wrote under public/uploads.
    foreach (Order::whereNotNull('payment_proof')->pluck('payment_proof') as $file) {
        ImageManager::delete($file, 'payment-proofs');
    }
});

function proofForm(Product $product, array $overrides = []): array
{
    return array_merge([
        'email' => 'buyer@example.com', 'first_name' => 'A', 'last_name' => 'B', 'address' => 'x', 'city' => 'y',
        'payment' => 'aba_qr',
        'items' => json_encode([['id' => $product->id, 'qty' => 1]]),
    ], $overrides);
}

it('shows the payment proof fields on checkout', function () {
    $this->get(route('frontend.checkout.index'))
        ->assertOk()
        ->assertSee('name="payment_proof"', false)
        ->assertSee('name="payment_reference"', false)
        ->assertSee('enctype="multipart/form-data"', false);
});

it('refuses a manual-payment order without a payment screenshot', function () {
    $this->from(route('frontend.checkout.index'))
        ->post(route('frontend.checkout.store'), proofForm($this->product))
        ->assertRedirect(route('frontend.checkout.index'))
        ->assertSessionHasErrors('payment_proof');

    expect(Order::count())->toBe(0)->and($this->product->fresh()->stock)->toBe(10);
});

it('rejects a proof that is not an image', function () {
    $this->from(route('frontend.checkout.index'))
        ->post(route('frontend.checkout.store'), proofForm($this->product, [
            'payment_proof' => UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'),
        ]))
        ->assertSessionHasErrors('payment_proof');

    expect(Order::count())->toBe(0);
});

it('stores the screenshot and reference with the order', function () {
    $this->post(route('frontend.checkout.store'), proofForm($this->product, [
        'payment_proof' => UploadedFile::fake()->image('receipt.jpg', 400, 800),
        'payment_reference' => '  TRX-99887766  ',
    ]))->assertRedirect();

    $order = Order::sole();

    expect($order->payment_reference)->toBe('TRX-99887766')
        ->and($order->payment_proof)->not->toBeNull()
        ->and(file_exists(public_path(ImageManager::path($order->payment_proof, 'payment-proofs'))))->toBeTrue()
        ->and($order->events()->where('title', 'Payment proof uploaded by customer')->exists())->toBeTrue()
        ->and(app(OrderTrackingService::class)->statusLabel($order))->toBe('Verifying payment');
});

it('does not ask for proof on online payment methods', function () {
    $this->post(route('frontend.checkout.store'), proofForm($this->product, ['payment' => 'card']))
        ->assertSessionDoesntHaveErrors('payment_proof');

    expect(Order::sole()->payment_proof)->toBeNull();
});

it('shows the proof to the admin on the order page', function () {
    $this->post(route('frontend.checkout.store'), proofForm($this->product, [
        'payment_proof' => UploadedFile::fake()->image('receipt.png'),
        'payment_reference' => 'TRX-1',
    ]));
    $order = Order::sole();

    $this->seed(RolePermissionSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->get(route('admin.orders.show', $order->id))
        ->assertOk()
        ->assertSee('Payment proof')
        ->assertSee('TRX-1')
        ->assertSee($order->payment_proof, false);
});

it('never auto-cancels an unpaid order that has payment proof', function () {
    $this->post(route('frontend.checkout.store'), proofForm($this->product, [
        'payment_proof' => UploadedFile::fake()->image('receipt.jpg'),
    ]));
    $order = Order::sole();
    $order->forceFill(['placed_at' => now()->subDays(5)])->save();

    expect(app(OrderService::class)->cancelExpiredUnpaid(24))->toBe(0)
        ->and($order->fresh()->status)->toBe(OrderStatus::Pending);
});
