# ABA PayWay integration

Online checkout payments via **ABA PayWay** (Purchase flow). Handles cards and the
ABA PAY wallet through **one** integration — the storefront's "ABA" and "Wallet"
methods just pass a different `payment_option`. **Manual** payment is unchanged
(customer transfers, admin confirms).

## Flow

1. Customer places the order → `Order` is created `payment_status = unpaid`, and the
   order id is stored in the session (`pending_order_id`).
2. If the chosen method is **online** and PayWay is configured, `CheckoutController`
   redirects to `GET /payment/{order}/pay`.
3. `PaymentController@pay` builds a signed PayWay purchase form (`PaywayService::purchase`)
   and auto-submits it to PayWay's hosted checkout (card / ABA PAY / KHQR).
4. Customer pays. PayWay:
   - **pushes back** server-to-server to `POST /payment/callback` (CSRF-exempt), and
   - **returns** the customer to `GET /payment/{order}/success`.
5. Both call `PaywayService::confirm()`, which queries PayWay's `check-transaction-2`
   API for the authoritative status and, on `APPROVED`, marks the order **paid**
   (`payment_status = paid`, `paid_at`) and the `payments` row `completed`. It's
   idempotent, so the pushback and the return can't double-charge/double-mark.

## Configuration

`.env` (fill with your sandbox merchant credentials):

```
PAYWAY_BASE_URL=https://checkout-sandbox.payway.com.kh
PAYWAY_MERCHANT_ID=your_merchant_id
PAYWAY_API_KEY=your_api_key
PAYWAY_CURRENCY=USD
```

### Mapping your methods → PayWay options

`config/services.php` → `services.payway.options` maps a storefront payment-method
**code** to a PayWay `payment_option`:

```php
'options' => [
    'aba'    => 'cards',   // "ABA PayWay" method -> card checkout
    'wallet' => 'abapay',  // "Wallet" method     -> ABA PAY app
],
```

Make sure your admin **Settings → Payment Methods** have:
- these **codes** (`aba`, `wallet`) — or update the map to match your codes, and
- **type = online** (that's how the checkout knows to route them to the gateway;
  `manual` methods skip the gateway).

Any online method not in the map defaults to `''` (PayWay shows all options).

## ⚠️ Verify against your PayWay guide

I can't call your sandbox from here, so confirm two provider-specific things
against your official PayWay integration guide (they differ by API version):

1. **Hash field order** — `PaywayService::HASH_FIELDS` is the single source of truth
   for the `purchase` signature (currently ABA's standard PHP-sample order). If your
   guide lists a different order, edit that array.
2. **Endpoints** — `purchase` and `check-transaction-2` paths under
   `/api/payment-gateway/v1/payments/…`. Adjust in `PaywayService` if your version
   differs.

Then run `php artisan serve --port=8000`, place an order with the ABA/Wallet method,
and complete a sandbox payment — the order should flip to **paid** automatically.

## What's tested here

`tests/Feature/PaywayPaymentTest.php` covers the app-side logic (hash signing,
approve → paid, decline → unpaid, the CSRF-exempt callback, and online-method
detection) using a faked HTTP client. The live PayWay round-trip is what you verify
in sandbox.
