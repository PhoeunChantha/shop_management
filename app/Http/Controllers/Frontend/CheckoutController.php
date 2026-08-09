<?php

namespace App\Http\Controllers\Frontend;

use App\Exceptions\CheckoutException;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Frontend\CheckoutService;
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
    ) {}

    public function index(): View
    {
        // Wallet is only usable by signed-in customers — hide it from guests.
        $methods = collect($this->checkout->paymentMethods())
            ->reject(fn (array $m): bool => ($m['type'] ?? '') === 'wallet' && ! Auth::check())
            ->values()
            ->all();

        return view('frontend.checkout.index', [
            'shippingMethods' => $this->checkout->shippingMethods(),
            'paymentMethods' => $methods,
            'taxRate' => $this->checkout->taxRate(),
            'prefill' => $this->prefill(),
            'walletBalance' => (float) (Auth::user()?->wallet_balance ?? 0),
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
            ]);
        } catch (CheckoutException $e) {
            // Safe, customer-facing message (e.g. an item ran out of stock).
            return back()->withInput()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('Checkout order failed: '.$e->getMessage(), ['exception' => $e]);

            return back()->with('error', __('We could not place your order. Please try again.'));
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

        return view('frontend.checkout.confirmation', ['order' => $order]);
    }
}
