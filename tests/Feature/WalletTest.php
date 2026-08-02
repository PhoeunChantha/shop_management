<?php

use App\Enums\PaymentStatus;
use App\Exceptions\CheckoutException;
use App\Exceptions\WalletException;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Models\WalletTopup;
use App\Services\Admin\WalletService;
use App\Services\Frontend\CheckoutService;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
    Category::create(['name' => 'Tees', 'slug' => 'tees']);
    Brand::create(['name' => 'Acme', 'slug' => 'acme']);

    $this->customer = User::factory()->create();
    $this->customer->assignRole('customer');

    Setting::set('payment_methods', json_encode([
        ['id' => 'wallet', 'name' => 'Wallet', 'code' => 'wallet', 'type' => 'wallet', 'status' => true, 'sort_order' => 1],
    ]), 'payment');
});

function walletOrderData(int $productId, string $payment = 'wallet'): array
{
    return [
        'customer' => ['first_name' => 'A', 'last_name' => 'B', 'email' => 'a@b.com', 'address' => 'x', 'city' => 'y'],
        'items' => [['id' => $productId, 'name' => 'x', 'price' => 20, 'size' => 'M', 'color' => 'black', 'qty' => 1]],
        'shipping_id' => null,
        'payment' => $payment,
    ];
}

it('credits and debits the wallet with a ledger', function () {
    $wallet = app(WalletService::class);

    $wallet->credit($this->customer, 50, 'topup', 'Top-up');
    expect((float) $this->customer->refresh()->wallet_balance)->toBe(50.0);

    $wallet->debit($this->customer, 20, 'payment', 'Order');
    expect((float) $this->customer->refresh()->wallet_balance)->toBe(30.0)
        ->and($this->customer->walletTransactions()->count())->toBe(2);
});

it('rejects a debit beyond the balance', function () {
    app(WalletService::class)->credit($this->customer, 10, 'topup');

    expect(fn () => app(WalletService::class)->debit($this->customer, 20, 'payment'))
        ->toThrow(WalletException::class);

    expect((float) $this->customer->refresh()->wallet_balance)->toBe(10.0);
});

it('pays an order from the wallet and marks it paid', function () {
    $product = Product::factory()->create(['status' => 'active', 'product_type' => 'single', 'stock' => 5, 'price' => 20]);
    app(WalletService::class)->credit($this->customer, 100, 'topup');

    $this->actingAs($this->customer);
    $order = app(CheckoutService::class)->placeOrder(walletOrderData($product->id));

    expect($order->payment_status)->toBe(PaymentStatus::Paid)
        ->and((float) $this->customer->refresh()->wallet_balance)->toBe(80.0);
});

it('blocks a wallet order when the balance is too low', function () {
    $product = Product::factory()->create(['status' => 'active', 'product_type' => 'single', 'stock' => 5, 'price' => 20]);
    app(WalletService::class)->credit($this->customer, 5, 'topup');

    $this->actingAs($this->customer);

    expect(fn () => app(CheckoutService::class)->placeOrder(walletOrderData($product->id)))
        ->toThrow(CheckoutException::class);

    expect(Order::count())->toBe(0)
        ->and((float) $this->customer->refresh()->wallet_balance)->toBe(5.0);
});

it('credits the wallet when a top-up is approved (via callback)', function () {
    config(['services.payway.merchant_id' => 'm', 'services.payway.api_key' => 'k']);
    Http::fake(['*' => Http::response(['data' => ['payment_status' => 'APPROVED']], 200)]);

    WalletTopup::create(['user_id' => $this->customer->id, 'tran_id' => 'WT123', 'amount' => 25, 'status' => 'pending']);

    $this->post(route('frontend.payment.callback'), ['tran_id' => 'WT123'])->assertOk();

    expect(WalletTopup::first()->status)->toBe('completed')
        ->and((float) $this->customer->refresh()->wallet_balance)->toBe(25.0);
});
