<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Services\FrontendCheckoutService;
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

    public function confirmation(): View
    {
        return view('frontend.checkout.confirmation', [
            'orderId' => random_int(8000, 9900),
        ]);
    }
}
