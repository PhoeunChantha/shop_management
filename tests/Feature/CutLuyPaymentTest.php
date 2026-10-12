<?php

use App\Enums\PaymentStatus;
use App\Exceptions\CutLuyException;
use App\Models\CutluyPayment;
use App\Models\CutluyWebhookEvent;
use App\Models\Order;
use App\Models\Setting;
use App\Models\User;
use App\Models\WalletTopup;
use App\Services\Frontend\CheckoutService;
use App\Services\Frontend\CutLuyService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;

const CUTLUY_SECRET = 'whsec_test_secret';

beforeEach(function () {
    // Point EnvService at a scratch .env so the real one is never touched.
    $this->envPath = storage_path('framework/testing-env-'.uniqid().'.env');
    file_put_contents($this->envPath, "APP_NAME=Shop\nCUTLUY_API_KEY=ck_test_key\nCUTLUY_WEBHOOK_SECRET=".CUTLUY_SECRET."\n");
    app()->loadEnvironmentFrom(basename($this->envPath));
    app()->useEnvironmentPath(dirname($this->envPath));

    $this->customer = User::factory()->create(['wallet_balance' => 0]);

    Setting::set('payment_methods', json_encode([
        ['id' => 'khqr', 'name' => 'ABA KHQR', 'code' => 'aba_khqr', 'type' => 'online', 'gateway' => 'cutluy', 'status' => true, 'sort_order' => 1],
        ['id' => 'manual', 'name' => 'ABA QR', 'code' => 'aba_qr', 'type' => 'manual', 'status' => true, 'sort_order' => 2],
    ]), 'payment');
    $this->order = Order::factory()->create([
        'user_id' => $this->customer->id,
        'grand_total' => 47.19,
        'payment_status' => PaymentStatus::Unpaid,
        'paid_at' => null,
        'payment_method' => 'aba_khqr',
    ]);
});

afterEach(function () {
    @unlink($this->envPath);
});

function cutluyPaymentBody(string $id, string $status = 'pending', string $amount = '47.19'): array
{
    return [
        'id' => $id,
        'status' => $status,
        'amount' => $amount,
        'currency' => 'USD',
        'checkout_url' => 'https://cutluy.com/pay/'.$id,
        'qr_string' => '00020101021229TESTQR6304AB12',
    ];
}

/** Sign a body the way CutLuy does: hex HMAC-SHA256 over "{t}.{rawBody}". */
function cutluySignature(string $raw, ?int $time = null, string $secret = CUTLUY_SECRET): string
{
    $time ??= time();

    return 't='.$time.',v1='.hash_hmac('sha256', $time.'.'.$raw, $secret);
}

function deliverCutluy($test, string $event, string $paymentId, ?string $signature = null)
{
    $raw = json_encode(['type' => $event, 'data' => ['payment' => ['id' => $paymentId]]]);

    return $test->call('POST', route('frontend.webhooks.cutluy'), [], [], [], [
        'HTTP_X-CutLuy-Event' => $event,
        'HTTP_X-CutLuy-Signature' => $signature ?? cutluySignature($raw),
        'CONTENT_TYPE' => 'application/json',
    ], $raw);
}

// ── Creating a payment ───────────────────────────────────────────────────

it('creates a CutLuy payment for the order total and shows the QR', function () {
    Http::fake(['cutluy.com/v1/payments' => Http::response(cutluyPaymentBody('PAY_1'), 201)]);

    $this->actingAs($this->customer)->followingRedirects()->get(route('frontend.payment.qr', $this->order))
        ->assertOk()
        ->assertSee('00020101021229TESTQR6304AB12')
        ->assertSee($this->order->order_number);

    Http::assertSent(fn (ClientRequest $r) => $r->method() === 'POST'
        && $r->hasHeader('Authorization', 'Bearer ck_test_key')
        && (float) $r['amount'] === 47.19
        && $r['reference_id'] === $this->order->order_number
        && $r['idempotency_key'] === $this->order->order_number);

    expect(CutluyPayment::where('payment_id', 'PAY_1')->value('status'))->toBe('pending')
        ->and($this->order->fresh()->payment_status)->toBe(PaymentStatus::Unpaid);
});

it('reuses the open payment when the pay page is reloaded', function () {
    Http::fake(['cutluy.com/v1/payments' => Http::response(cutluyPaymentBody('PAY_1'), 201)]);

    $this->actingAs($this->customer)->followingRedirects()->get(route('frontend.payment.qr', $this->order))->assertOk();
    $this->actingAs($this->customer)->followingRedirects()->get(route('frontend.payment.qr', $this->order))->assertOk();

    Http::assertSentCount(1);
    expect(CutluyPayment::count())->toBe(1);
});

it('creates a fresh payment with a new idempotency key after the QR expired', function () {
    Http::fakeSequence('cutluy.com/v1/payments')
        ->push(cutluyPaymentBody('PAY_1'), 201)
        ->push(cutluyPaymentBody('PAY_2'), 201);

    $this->actingAs($this->customer)->followingRedirects()->get(route('frontend.payment.qr', $this->order));
    CutluyPayment::where('payment_id', 'PAY_1')->update(['status' => 'expired']);

    $this->actingAs($this->customer)->post(route('frontend.payment.qr.renew', $this->order))
        ->assertRedirect(route('frontend.payment.qr', $this->order));
    $this->actingAs($this->customer)->followingRedirects()->get(route('frontend.payment.qr', $this->order))->assertOk();

    expect(CutluyPayment::where('payment_id', 'PAY_2')->value('idempotency_key'))->toBe($this->order->order_number.'-2');
});

it('handles quota, auth, suspension and rate-limit errors without retrying', function (int $status, string $code) {
    Http::fake(['cutluy.com/*' => Http::response(['error' => $code, 'message' => 'nope'], $status, ['Retry-After' => '17'])]);

    $this->actingAs($this->customer)->followingRedirects()->get(route('frontend.payment.qr', $this->order))
        ->assertOk()
        ->assertDontSee('data-qrpay-code', false);

    Http::assertSentCount(1);
    expect(CutluyPayment::count())->toBe(0);

    try {
        app(CutLuyService::class)->usage();
    } catch (CutLuyException $e) {
        expect($e->errorCode)->toBe($code)
            ->and($e->retryAfter)->toBe($status === 429 ? 17 : null);
    }
})->with([[402, 'quota_exceeded'], [401, 'unauthorized'], [403, 'account_suspended'], [429, 'rate_limited']]);

it('does not let a stranger see or poll another customer\'s payment', function () {
    $stranger = User::factory()->create();

    $this->actingAs($stranger)->get(route('frontend.payment.qr', $this->order))->assertForbidden();
    $this->actingAs($stranger)->getJson(route('frontend.payment.qr.status', $this->order))->assertForbidden();
});

// ── Webhook ──────────────────────────────────────────────────────────────

it('marks the order paid on a signed payment.completed, re-checking with CutLuy', function () {
    $payment = CutluyPayment::create([
        'payment_id' => 'PAY_1', 'payable_type' => $this->order->getMorphClass(), 'payable_id' => $this->order->id,
        'reference_id' => $this->order->order_number, 'idempotency_key' => $this->order->order_number, 'amount' => 47.19, 'status' => 'pending',
    ]);
    Http::fake(['cutluy.com/v1/payments/PAY_1' => Http::response(cutluyPaymentBody('PAY_1', 'paid'))]);

    deliverCutluy($this, 'payment.completed', 'PAY_1')->assertOk();

    expect($this->order->fresh()->payment_status)->toBe(PaymentStatus::Paid)
        ->and($this->order->fresh()->paid_at)->not->toBeNull()
        ->and($payment->fresh()->status)->toBe('paid')
        ->and(CutluyWebhookEvent::first()->processed_at)->not->toBeNull();
});

it('rejects a wrong signature, a tampered body and a stale timestamp', function () {
    CutluyPayment::create([
        'payment_id' => 'PAY_1', 'payable_type' => $this->order->getMorphClass(), 'payable_id' => $this->order->id,
        'reference_id' => 'r', 'idempotency_key' => 'k', 'amount' => 47.19, 'status' => 'pending',
    ]);
    Http::fake(['cutluy.com/*' => Http::response(cutluyPaymentBody('PAY_1', 'paid'))]);
    $raw = json_encode(['type' => 'payment.completed', 'data' => ['payment' => ['id' => 'PAY_1']]]);

    deliverCutluy($this, 'payment.completed', 'PAY_1', cutluySignature($raw, null, 'wrong-secret'))->assertUnauthorized();
    deliverCutluy($this, 'payment.completed', 'PAY_1', cutluySignature($raw.' '))->assertUnauthorized();
    deliverCutluy($this, 'payment.completed', 'PAY_1', cutluySignature($raw, time() - 600))->assertUnauthorized();
    deliverCutluy($this, 'payment.completed', 'PAY_1', 'garbage')->assertUnauthorized();

    Http::assertNothingSent();
    expect($this->order->fresh()->payment_status)->toBe(PaymentStatus::Unpaid)
        ->and(CutluyWebhookEvent::count())->toBe(0);
});

it('never fulfils on payment.scanned', function () {
    $payment = CutluyPayment::create([
        'payment_id' => 'PAY_1', 'payable_type' => $this->order->getMorphClass(), 'payable_id' => $this->order->id,
        'reference_id' => 'r', 'idempotency_key' => 'k', 'amount' => 47.19, 'status' => 'pending',
    ]);
    Http::fake(['cutluy.com/*' => Http::response(cutluyPaymentBody('PAY_1', 'scanned'))]);

    deliverCutluy($this, 'payment.scanned', 'PAY_1')->assertOk();

    expect($payment->fresh()->status)->toBe('scanned')
        ->and($this->order->fresh()->payment_status)->toBe(PaymentStatus::Unpaid);
});

it('does not fulfil when CutLuy has not actually marked the payment paid', function () {
    CutluyPayment::create([
        'payment_id' => 'PAY_1', 'payable_type' => $this->order->getMorphClass(), 'payable_id' => $this->order->id,
        'reference_id' => 'r', 'idempotency_key' => 'k', 'amount' => 47.19, 'status' => 'pending',
    ]);
    // A forged-looking "completed" event while the API still says pending.
    Http::fake(['cutluy.com/*' => Http::response(cutluyPaymentBody('PAY_1', 'pending'))]);

    deliverCutluy($this, 'payment.completed', 'PAY_1')->assertOk();

    expect($this->order->fresh()->payment_status)->toBe(PaymentStatus::Unpaid);
});

it('refuses a paid payment whose amount does not match the order', function () {
    $payment = CutluyPayment::create([
        'payment_id' => 'PAY_1', 'payable_type' => $this->order->getMorphClass(), 'payable_id' => $this->order->id,
        'reference_id' => 'r', 'idempotency_key' => 'k', 'amount' => 47.19, 'status' => 'pending',
    ]);
    Http::fake(['cutluy.com/*' => Http::response(cutluyPaymentBody('PAY_1', 'paid', '0.01'))]);

    deliverCutluy($this, 'payment.completed', 'PAY_1')->assertOk();

    expect($this->order->fresh()->payment_status)->toBe(PaymentStatus::Unpaid)
        ->and($payment->fresh()->status)->toBe('failed');
});

it('credits a wallet top-up exactly once when the event is delivered twice', function () {
    $topup = WalletTopup::create([
        'user_id' => $this->customer->id, 'tran_id' => 'WT-CUTLUY-1', 'payment_method' => 'aba_khqr',
        'method_type' => 'online', 'amount' => 25, 'status' => 'pending',
    ]);
    CutluyPayment::create([
        'payment_id' => 'PAY_T', 'payable_type' => $topup->getMorphClass(), 'payable_id' => $topup->id,
        'reference_id' => $topup->tran_id, 'idempotency_key' => $topup->tran_id, 'amount' => 25, 'status' => 'pending',
    ]);
    Http::fake(['cutluy.com/*' => Http::response(cutluyPaymentBody('PAY_T', 'paid', '25.00'))]);

    deliverCutluy($this, 'payment.completed', 'PAY_T')->assertOk();
    deliverCutluy($this, 'payment.completed', 'PAY_T')->assertOk();

    expect((float) $this->customer->fresh()->wallet_balance)->toBe(25.0)
        ->and($topup->fresh()->status)->toBe('completed')
        ->and($this->customer->walletTransactions()->count())->toBe(1)
        ->and(CutluyWebhookEvent::count())->toBe(1);
});

it('acknowledges a signed event for a payment this store did not create', function () {
    Http::fake();

    deliverCutluy($this, 'payment.completed', 'SOMEONE_ELSE')->assertOk();

    Http::assertNothingSent();
});

// ── Pay-page polling fallback ────────────────────────────────────────────

it('reports the status to the pay page and settles if the webhook never arrived', function () {
    CutluyPayment::create([
        'payment_id' => 'PAY_1', 'payable_type' => $this->order->getMorphClass(), 'payable_id' => $this->order->id,
        'reference_id' => 'r', 'idempotency_key' => 'k', 'amount' => 47.19, 'status' => 'pending',
    ]);
    Http::fake(['cutluy.com/*' => Http::response(cutluyPaymentBody('PAY_1', 'paid'))]);

    $this->actingAs($this->customer)->getJson(route('frontend.payment.qr.status', $this->order))
        ->assertOk()->assertJson(['status' => 'paid']);

    expect($this->order->fresh()->payment_status)->toBe(PaymentStatus::Paid);
});

// ── Settings ─────────────────────────────────────────────────────────────

it('keeps the gateway chosen for an online payment method', function () {
    Setting::set('payment_methods', json_encode([
        ['id' => 'khqr', 'name' => 'ABA KHQR', 'code' => 'aba_khqr', 'type' => 'online', 'gateway' => 'cutluy', 'status' => true, 'sort_order' => 1],
        ['id' => 'card', 'name' => 'Card', 'code' => 'card', 'type' => 'online', 'status' => true, 'sort_order' => 2],
        ['id' => 'manual', 'name' => 'Bank', 'code' => 'bank', 'type' => 'manual', 'gateway' => 'cutluy', 'status' => true, 'sort_order' => 3],
    ]), 'payment');

    $checkout = app(CheckoutService::class);

    expect($checkout->methodGateway('aba_khqr'))->toBe('cutluy')
        ->and($checkout->methodGateway('card'))->toBe('payway')
        ->and($checkout->methodGateway('bank'))->toBeNull();   // manual stays the payslip flow
});

it('shows the CutLuy settings tab and reports quota from the connection check', function () {
    $this->seed(RolePermissionSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    Http::fake(['cutluy.com/v1/usage' => Http::response(['plan' => 'starter', 'limit' => 500, 'used' => 20, 'remaining' => 480])]);

    // Opened on a local address, the page must still show the public site URL.
    config(['app.url' => 'https://shop.example.com']);

    $this->actingAs($admin)->get('http://127.0.0.1:2000'.route('admin.settings.index', ['tab' => 'cutluy'], false))
        ->assertOk()
        ->assertSee('https://shop.example.com/webhooks/cutluy')
        ->assertDontSee('127.0.0.1:2000/webhooks/cutluy')
        ->assertSee('name="cutluy_api_key"', false)
        ->assertDontSee('ck_test_key');   // the saved key never reaches page source

    $this->actingAs($admin)->postJson(route('admin.settings.cutluy-test'))
        ->assertOk()
        ->assertJsonPath('ok', true)
        ->assertJsonFragment(['ok' => true]);
});

it('stores the expiry, shows a countdown and offers a new QR once it has run out', function () {
    $body = cutluyPaymentBody('PAY_1') + ['expires_at' => now()->addMinutes(5)->toIso8601String(), 'approved_at' => null];
    Http::fakeSequence('cutluy.com/*')->push($body, 201)->push($body)->push(cutluyPaymentBody('PAY_2'), 201);

    $this->actingAs($this->customer)->followingRedirects()->get(route('frontend.payment.qr', $this->order))
        ->assertOk()->assertSee('data-qrpay-timer', false);

    $payment = CutluyPayment::where('payment_id', 'PAY_1')->first();
    expect($payment->expires_at)->not->toBeNull()->and($payment->isOpen())->toBeTrue();

    $this->travel(6)->minutes();

    // CutLuy still says pending (its expired event is late): we report expired…
    $this->actingAs($this->customer)->getJson(route('frontend.payment.qr.status', $this->order))
        ->assertJson(['status' => 'expired']);

    // …and reopening the page makes a fresh payment instead of the dead QR.
    $this->actingAs($this->customer)->followingRedirects()->get(route('frontend.payment.qr', $this->order))->assertOk();
    expect(CutluyPayment::count())->toBe(2);
});

it('reads the event from the body type and finds the payment by reference when no id is sent', function () {
    CutluyPayment::create([
        'payment_id' => 'PAY_1', 'payable_type' => $this->order->getMorphClass(), 'payable_id' => $this->order->id,
        'reference_id' => $this->order->order_number, 'idempotency_key' => 'k', 'amount' => 47.19, 'status' => 'pending',
    ]);
    Http::fake(['cutluy.com/v1/payments/PAY_1' => Http::response(cutluyPaymentBody('PAY_1', 'paid'))]);

    $raw = json_encode(['type' => 'payment.completed', 'data' => ['payment' => ['reference_id' => $this->order->order_number]]]);

    // No X-CutLuy-Event header: the event name comes from the body.
    $this->call('POST', route('frontend.webhooks.cutluy'), [], [], [], [
        'HTTP_X-CutLuy-Signature' => cutluySignature($raw),
        'CONTENT_TYPE' => 'application/json',
    ], $raw)->assertOk();

    expect($this->order->fresh()->payment_status)->toBe(PaymentStatus::Paid);
});

it('opens the top-up QR as a popup on the wallet page, only for its owner', function () {
    Http::fake(['cutluy.com/v1/payments' => Http::response(cutluyPaymentBody('PAY_T', 'pending', '20.00'), 201)]);

    $this->actingAs($this->customer)
        ->post(route('frontend.account.wallet.topup'), ['amount' => 20, 'payment_method' => 'aba_khqr'])
        ->assertRedirect();
    $topup = WalletTopup::latest('id')->first();

    $this->actingAs($this->customer)->get(route('frontend.payment.topup.qr', $topup))
        ->assertRedirect(route('frontend.account.wallet', ['pay' => $topup->tran_id]));

    $this->actingAs($this->customer)->get(route('frontend.account.wallet', ['pay' => $topup->tran_id]))
        ->assertOk()
        ->assertSee('class="ut-qrmodal"', false)
        ->assertSee('00020101021229TESTQR6304AB12')
        ->assertSee($topup->tran_id);

    // No popup without ?pay, and never for someone else's top-up.
    $this->actingAs($this->customer)->get(route('frontend.account.wallet'))->assertOk()->assertDontSee('ut-qrmodal"', false);
    $this->actingAs(User::factory()->create())->get(route('frontend.account.wallet', ['pay' => $topup->tran_id]))
        ->assertOk()->assertDontSee('ut-qrmodal"', false);
    $this->actingAs(User::factory()->create())->get(route('frontend.payment.topup.qr', $topup))->assertForbidden();
});

it('opens the order QR as a popup on the confirmation page and offers Pay now once closed', function () {
    Http::fake(['cutluy.com/v1/payments' => Http::response(cutluyPaymentBody('PAY_1'), 201)]);

    $this->actingAs($this->customer)->get(route('frontend.payment.qr', $this->order))
        ->assertRedirect(route('frontend.checkout.confirmation', ['pay' => 1]));

    $this->actingAs($this->customer)->withSession(['order_id' => $this->order->id])
        ->get(route('frontend.checkout.confirmation', ['pay' => 1]))
        ->assertOk()->assertSee('class="ut-qrmodal"', false);

    $this->actingAs($this->customer)->withSession(['order_id' => $this->order->id])
        ->get(route('frontend.checkout.confirmation'))
        ->assertOk()
        ->assertDontSee('class="ut-qrmodal"', false)
        ->assertSee(route('frontend.payment.qr', $this->order));
});

it('reads the merchant name from the KHQR string for the card', function () {
    $payment = new CutluyPayment(['qr_string' => '00020101021229170014merchant@abaa520459995303840540520.005802KH5912TSHIRT STORE6010PHNOM PENH6304AB12']);

    expect($payment->merchantName())->toBe('TSHIRT STORE')
        ->and((new CutluyPayment(['qr_string' => 'not-a-khqr']))->merchantName())->toBeNull()
        ->and((new CutluyPayment)->merchantName())->toBeNull();
});

it('uses the order number and top-up code in payment URLs, never the database id', function () {
    $topup = WalletTopup::create([
        'user_id' => $this->customer->id, 'tran_id' => 'WT-URL-1', 'payment_method' => 'aba_khqr',
        'method_type' => 'online', 'amount' => 5, 'status' => 'pending',
    ]);

    expect(route('frontend.payment.topup.qr', $topup))->toEndWith('/wallet/topup/WT-URL-1/qr')
        ->and(route('frontend.payment.qr', $this->order))->toEndWith('/payment/'.$this->order->order_number.'/qr')
        ->and(route('frontend.payment.pay', $this->order))->toEndWith('/payment/'.$this->order->order_number.'/pay');

    // The old numeric addresses no longer resolve.
    $this->actingAs($this->customer)->get('/wallet/topup/'.$topup->id.'/qr')->assertNotFound();
    $this->actingAs($this->customer)->get('/payment/'.$this->order->id.'/qr')->assertNotFound();
    $this->actingAs($this->customer)->get(route('frontend.account.wallet', ['pay' => $topup->id]))
        ->assertOk()->assertDontSee('ut-qrmodal"', false);
});
