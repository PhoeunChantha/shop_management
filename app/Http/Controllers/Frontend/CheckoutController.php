<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\FrontendCheckoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly FrontendCheckoutService $checkout,
    ) {}

    public function index(): View
    {
        return view('frontend.checkout.index', [
            'shippingMethods' => $this->checkout->shippingMethods(),
            'paymentMethods' => $this->checkout->paymentMethods(),
            'taxRate' => $this->checkout->taxRate(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email', 'max:255'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
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
            // Surface the first message via the shared toast (session flash).
            return back()->withInput()->with('error', $validator->errors()->first());
        }

        $data = $validator->validated();
        $items = json_decode($data['items'], true);
        if (! is_array($items) || $items === []) {
            return back()->with('error', 'Your cart is empty.');
        }

        try {
            $order = $this->checkout->placeOrder([
                'customer' => [
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'email' => $data['email'],
                    'address' => $data['address'],
                    'city' => $data['city'],
                    'zip' => $data['zip'] ?? null,
                    'country' => $data['country'] ?? null,
                ],
                'items' => $items,
                'shipping_id' => $data['del'] ?? null,
                'payment' => $data['payment'] ?? 'card',
            ]);
        } catch (\Throwable $e) {
            Log::error('Checkout order failed: '.$e->getMessage(), ['exception' => $e]);

            return back()->with('error', 'We could not place your order. Please try again.');
        }

        return redirect()->route('frontend.checkout.confirmation')->with('order_id', $order->id);
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
