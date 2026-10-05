<?php

namespace App\Http\Controllers\Frontend;

use App\Exceptions\CheckoutException;
use App\Helpers\ImageManager;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Frontend\CheckoutGuardService;
use App\Services\Frontend\CheckoutService;
use App\Services\Frontend\OrderTrackingService;
use App\Services\Frontend\PaywayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly CheckoutService $checkout,
        private readonly CheckoutGuardService $guard,
    ) {}

    public function index(): View|RedirectResponse
    {
        // Admin â†’ Settings â†’ Checkout & Security can require an account.
        if ($this->guard->requiresLogin()) {
            return redirect()->guest(route('frontend.login'))
                ->with('info', __('Please sign in or create an account to check out.'));
        }

        // Strip any wallet entries from the configurable methods — wallet is a
        // platform feature and is injected separately below so it can never be
        // accidentally removed by editing Settings → Payment Methods.
        $methods = collect($this->checkout->paymentMethods())
            ->reject(fn (array $m): bool => ($m['type'] ?? '') === 'wallet')
            ->values()
            ->all();

        // Always offer wallet to signed-in customers as the first option.
        if (Auth::check()) {
            array_unshift($methods, [
                'code' => 'wallet',
                'name' => __('Pay by Wallet'),
                'type' => 'wallet',
                'description' => __('Pay instantly from your store wallet balance.'),
                'instructions' => '',
                'image' => null,
                'qr_image' => null,
                'bank_name' => '',
                'account_name' => '',
                'account_number' => '',
            ]);
        }

        return view('frontend.checkout.index', [
            'shippingMethods' => $this->checkout->shippingMethods(),
            'paymentMethods' => $methods,
            'taxRate' => $this->checkout->taxRate(),
            'prefill' => $this->prefill(),
            'walletBalance' => (float) (Auth::user()?->wallet_balance ?? 0),
            'recaptchaSiteKey' => $this->guard->recaptchaSiteKey(),
        ]);
    }

    /**
     * Prefill the shipping form from the signed-in customer's default address.
     *
     * @return array<string, string>
     */
    private function prefill(): array
    {
        $user = Auth::user();

        if (! $user) {
            return [];
        }

        $address = $user->addresses()->where('is_default', true)->first()
            ?? $user->addresses()->first();

        $fullName = trim((string) ($address?->name ?: $user->name));
        $first = Str::before($fullName, ' ');
        $last = trim(Str::after($fullName, ' '));

        return [
            'email' => (string) $user->email,
            'first_name' => $first,
            'last_name' => $last !== $first ? $last : '',
            'phone' => (string) ($address?->phone ?? ''),
            'address' => (string) ($address?->street ?? ''),
            'city' => (string) ($address?->city ?? ''),
            'zip' => (string) ($address?->zip ?? ''),
        ];
    }

    public function store(Request $request): RedirectResponse
    {
        if ($this->guard->requiresLogin()) {
            return redirect()->guest(route('frontend.login'))
                ->with('info', __('Please sign in or create an account to check out.'));
        }

        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email', 'max:255'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:40'],
            'address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:120'],
            'zip' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:120'],
            'del' => ['nullable', 'integer'],
            'payment' => ['nullable', 'string', 'max:80'],
            'email_updates' => ['nullable', 'boolean'],
            'payment_reference' => ['nullable', 'string', 'max:100'],
            'payment_proof' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'items' => ['required', 'string'],
        ], [], [
            'del' => 'delivery method',
        ]);

        if ($validator->fails()) {
            // Show the messages under each field (step 1 holds the required inputs).
            return back()->withErrors($validator)->withInput();
        }

        $data = $validator->validated();
        $items = json_decode($data['items'], true);
        if (! is_array($items) || $items === []) {
            return back()->with('error', __('Your cart is empty.'));
        }

        // Manual (bank/QR) payments need proof before the order is accepted —
        // same rule as wallet top-ups. Shown under the field on the Payment step.
        $manual = $this->checkout->isManualMethod($data['payment'] ?? null);

        if ($manual && ! $request->hasFile('payment_proof')) {
            return back()->withInput()->withErrors([
                'payment_proof' => __('Please upload your payment screenshot so we can confirm your payment.'),
            ]);
        }

        // Anti-fraud: honeypot, reCAPTCHA, open-unpaid-order cap.
        if ($blocked = $this->guard->check($request, $data['email'])) {
            return back()->withInput()->with('error', $blocked);
        }

        try {
            $order = $this->checkout->placeOrder([
                'customer' => [
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'email' => $data['email'],
                    'phone' => $data['phone'] ?? null,
                    'address' => $data['address'],
                    'city' => $data['city'],
                    'zip' => $data['zip'] ?? null,
                    'country' => $data['country'] ?? null,
                ],
                'items' => $items,
                'shipping_id' => $data['del'] ?? null,
                'payment' => $data['payment'] ?? 'card',
                // Unticked checkboxes are not submitted at all.
                'email_updates' => (bool) ($data['email_updates'] ?? false),
            ]);
        } catch (CheckoutException $e) {
            // Safe, customer-facing message (e.g. an item ran out of stock).
            return back()->withInput()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('Checkout order failed: '.$e->getMessage(), ['exception' => $e]);

            return back()->with('error', __('We could not place your order. Please try again.'));
        }

        // The order exists now — attach the customer's payment proof. A failed
        // upload must not lose the order (stock is already reserved): it is
        // logged on the order so an admin can ask the customer for the proof.
        if ($manual) {
            try {
                $order->forceFill([
                    'payment_reference' => filled($data['payment_reference'] ?? null) ? trim((string) $data['payment_reference']) : null,
                    'payment_proof' => ImageManager::upload($request->file('payment_proof'), 'payment-proofs'),
                ])->save();
                $order->logEvent('payment', 'Payment proof uploaded by customer', $order->payment_reference ? 'Reference: '.$order->payment_reference : null);
            } catch (\Throwable $e) {
                Log::error('Payment proof upload failed: '.$e->getMessage(), ['order' => $order->order_number]);
                $order->logEvent('payment', 'Payment proof could not be saved', 'Ask the customer to send their payment screenshot.');
            }
        }

        $request->session()->put('pending_order_id', $order->id);

        // Online methods (ABA / wallet) go through the PayWay gateway when it is
        // configured; manual methods jump straight to the confirmation page.
        if ($this->checkout->isOnlineMethod($data['payment'] ?? null) && app(PaywayService::class)->configured()) {
            return redirect()->route('frontend.payment.pay', $order);
        }

        return redirect()->route('frontend.checkout.confirmation')->with('order_id', $order->id);
    }

    public function coupon(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:60'],
            'subtotal' => ['required', 'numeric', 'min:0'],
        ]);

        return response()->json($this->checkout->validateCoupon($data['code'], (float) $data['subtotal']));
    }

    public function confirmation(Request $request): View|RedirectResponse
    {
        $orderId = $request->session()->get('order_id');
        $order = $orderId ? Order::with('details')->find($orderId) : null;

        if (! $order) {
            return redirect()->route('frontend.shop.index');
        }

        // Keep it available on refresh within the session.
        $request->session()->keep('order_id');

        $tracking = app(OrderTrackingService::class);

        return view('frontend.checkout.confirmation', [
            'order' => $order,
            'steps' => $tracking->steps($order),
            'statusLabel' => $tracking->statusLabel($order),
            'isPaid' => $order->isPaid(),
            'eta' => $tracking->estimatedDelivery($order),
            'ownsViaAccount' => $order->user_id !== null && $order->user_id === Auth::id(),
            'trackUrl' => $tracking->guestUrl($order),
        ]);
    }
}
